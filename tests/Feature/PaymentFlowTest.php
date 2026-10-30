<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\Ticket;
use App\Services\PaystackGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.paystack.secret_key' => 'sk_test_eventflow',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Grace',
            'last_name' => 'Hopper',
            'email' => 'grace@example.test',
            'phone' => '+2348012345678',
            'organization' => '',
            'notes' => '',
        ], $overrides);
    }

    public function test_a_paid_registration_creates_a_pending_payment_for_the_exact_event_amount(): void
    {
        Notification::fake();
        Queue::fake();

        $event = Event::factory()->paid(45_000)->published()->create();

        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'data' => ['authorization_url' => 'https://checkout.paystack.com/abc'],
            ]),
        ]);

        $this->post(route('registrations.store', $event), $this->payload())
            ->assertRedirect('https://checkout.paystack.com/abc');

        $registration = Registration::sole();
        $this->assertSame(Registration::STATUS_PENDING, $registration->status);

        // A pending registration must never already hold a ticket.
        $this->assertDatabaseCount('tickets', 0);

        $payment = Payment::sole();
        $this->assertSame(45_000, $payment->amount);
        $this->assertSame('NGN', $payment->currency);
        $this->assertSame(Payment::STATUS_PENDING, $payment->status);

        Http::assertSent(fn (Request $request): bool => $request['amount'] === 45_000
            && $request['currency'] === 'NGN'
            && $request['reference'] === $payment->reference
            && $request['email'] === 'grace@example.test');
    }

    public function test_a_tampered_price_in_the_request_is_ignored(): void
    {
        Notification::fake();
        Queue::fake();

        $event = Event::factory()->paid(45_000)->published()->create();

        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'data' => ['authorization_url' => 'https://checkout.paystack.com/abc'],
            ]),
        ]);

        $this->post(route('registrations.store', $event), $this->payload([
            'price_amount' => 1,
            'event_type' => 'free',
        ]))->assertRedirect('https://checkout.paystack.com/abc');

        $this->assertSame(45_000, Payment::sole()->amount);
    }

    /**
     * Post a webhook with a signature computed over the exact raw body, which is
     * what the gateway signs and what the application compares against.
     *
     * @param  array<string, mixed>  $payload
     */
    private function postWebhook(array $payload, ?string $signature = null): TestResponse
    {
        $raw = json_encode($payload, JSON_THROW_ON_ERROR);

        return $this->call(
            'POST',
            route('payments.webhook'),
            [],
            [],
            [],
            array_filter([
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_PAYSTACK_SIGNATURE' => $signature ?? hash_hmac('sha512', $raw, (string) config('services.paystack.secret_key')),
            ]),
            $raw,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function signatureFor(array $payload): string
    {
        return hash_hmac('sha512', json_encode($payload, JSON_THROW_ON_ERROR), (string) config('services.paystack.secret_key'));
    }

    public function test_a_webhook_without_a_valid_signature_is_rejected(): void
    {
        $payment = Payment::factory()->create();

        $this->postWebhook([
            'event' => 'charge.success',
            'data' => ['reference' => $payment->reference, 'status' => 'success', 'amount' => $payment->amount, 'currency' => $payment->currency],
        ], 'forged')->assertUnauthorized();

        $this->assertSame(Payment::STATUS_PENDING, $payment->fresh()->status);
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_a_webhook_without_a_configured_secret_is_rejected(): void
    {
        config(['services.paystack.secret_key' => null]);

        $payment = Payment::factory()->create();

        $this->postWebhook([
            'event' => 'charge.success',
            'data' => ['reference' => $payment->reference],
        ])->assertUnauthorized();

        $this->assertSame(Payment::STATUS_PENDING, $payment->fresh()->status);
    }

    public function test_a_matching_webhook_settles_the_payment_and_issues_exactly_one_ticket(): void
    {
        Notification::fake();
        Queue::fake();

        $event = Event::factory()->paid(45_000)->published()->create();
        $registration = Registration::factory()->forEvent($event)->pending()->create();
        $payment = Payment::factory()->forRegistration($registration)->create([
            'amount' => 45_000,
            'currency' => 'NGN',
        ]);

        $body = [
            'event' => 'charge.success',
            'data' => [
                'id' => 999,
                'reference' => $payment->reference,
                'status' => 'success',
                'amount' => 45_000,
                'currency' => 'NGN',
                'paid_at' => now()->toIso8601String(),
            ],
        ];

        $this->postWebhook($body)->assertOk();

        $payment->refresh();
        $this->assertSame(Payment::STATUS_SUCCESSFUL, $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame(Registration::STATUS_CONFIRMED, $registration->fresh()->status);
        $this->assertDatabaseCount('tickets', 1);

        // Replaying the same delivery must not double-issue or double-count.
        $this->postWebhook($body)->assertOk();

        $this->assertDatabaseCount('tickets', 1);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_a_webhook_for_a_different_amount_is_never_settled(): void
    {
        Notification::fake();
        Queue::fake();

        $event = Event::factory()->paid(45_000)->published()->create();
        $registration = Registration::factory()->forEvent($event)->pending()->create();
        $payment = Payment::factory()->forRegistration($registration)->create([
            'amount' => 45_000,
            'currency' => 'NGN',
        ]);

        $this->postWebhook([
            'event' => 'charge.success',
            'data' => [
                'reference' => $payment->reference,
                'status' => 'success',
                'amount' => 45_000 - 1, // underpaid by one kobo
                'currency' => 'NGN',
            ],
        ])->assertOk();

        $this->assertNotSame(Payment::STATUS_SUCCESSFUL, $payment->fresh()->status);
        $this->assertSame(Registration::STATUS_PENDING, $registration->fresh()->status);
        $this->assertDatabaseCount('tickets', 0);

        // The disputed payload is retained for reconciliation, not discarded.
        $this->assertSame(45_000 - 1, $payment->fresh()->metadata['gateway_amount']);
    }

    public function test_a_webhook_for_a_different_currency_is_never_settled(): void
    {
        Notification::fake();
        Queue::fake();

        $event = Event::factory()->paid(45_000)->published()->create();
        $registration = Registration::factory()->forEvent($event)->pending()->create();
        $payment = Payment::factory()->forRegistration($registration)->create([
            'amount' => 45_000,
            'currency' => 'NGN',
        ]);

        $this->postWebhook([
            'event' => 'charge.success',
            'data' => [
                'reference' => $payment->reference,
                'status' => 'success',
                'amount' => 45_000,
                'currency' => 'USD',
            ],
        ])->assertOk();

        $this->assertNotSame(Payment::STATUS_SUCCESSFUL, $payment->fresh()->status);
        $this->assertSame(Registration::STATUS_PENDING, $registration->fresh()->status);
        $this->assertDatabaseCount('tickets', 0);
        $this->assertSame('USD', $payment->fresh()->metadata['gateway_currency']);
    }

    public function test_a_failed_charge_marks_the_payment_failed_and_keeps_the_registration_pending(): void
    {
        Notification::fake();
        Queue::fake();

        $event = Event::factory()->paid(45_000)->published()->create();
        $registration = Registration::factory()->forEvent($event)->pending()->create();
        $payment = Payment::factory()->forRegistration($registration)->create();

        $this->postWebhook([
            'event' => 'charge.failed',
            'data' => [
                'reference' => $payment->reference,
                'status' => 'failed',
                'gateway_reason' => 'insufficient_funds',
            ],
        ])->assertOk();

        $this->assertSame(Payment::STATUS_FAILED, $payment->fresh()->status);
        $this->assertSame(Registration::STATUS_PENDING, $registration->fresh()->status);
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_an_unknown_reference_is_acknowledged_so_the_gateway_stops_retrying(): void
    {
        $this->postWebhook([
            'event' => 'charge.success',
            'data' => ['reference' => 'PAY-DOESNOTEXIST'],
        ])->assertOk();
    }

    public function test_a_visitor_cannot_retry_somebody_elses_payment(): void
    {
        $event = Event::factory()->paid()->published()->create();
        $registration = Registration::factory()->forEvent($event)->pending()->create();
        $payment = Payment::factory()->forRegistration($registration)->create();

        $this->post(route('payments.retry', $payment))->assertNotFound();
    }

    public function test_the_owning_browser_can_resume_an_unpaid_transaction(): void
    {
        Notification::fake();
        Queue::fake();

        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::sequence()
                ->push(['data' => ['authorization_url' => 'https://checkout.paystack.com/started']])
                ->push(['data' => ['authorization_url' => 'https://checkout.paystack.com/resumed']]),
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'ongoing',
                    'amount' => 45_000,
                    'currency' => 'NGN',
                ],
            ]),
        ]);

        $event = Event::factory()->paid(45_000)->published()->create();
        $this->post(route('registrations.store', $event), $this->payload())
            ->assertRedirect('https://checkout.paystack.com/started');

        $payment = Payment::sole();

        // An unpaid transaction must stay pending so the guest can finish it.
        $this->assertSame(Payment::STATUS_PENDING, $payment->fresh()->status);

        $this->post(route('payments.retry', $payment))
            ->assertRedirect('https://checkout.paystack.com/resumed');
    }

    public function test_the_gateway_helper_is_used_for_signatures(): void
    {
        $this->assertTrue(app(PaystackGateway::class)->hasWebhookSecret());
        $this->assertSame(
            hash_hmac('sha512', 'raw-body', 'sk_test_eventflow'),
            app(PaystackGateway::class)->signatureFor('raw-body'),
        );
    }
}
