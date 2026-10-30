<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\CommunicationService;
use App\Services\PaymentProcessor;
use App\Services\PaystackGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentProcessor $processor,
        private readonly CommunicationService $communications,
    ) {}

    /**
     * Browser return URL from the gateway. The gateway redirects here before it
     * calls the webhook, so the result here is provisional and the webhook is
     * still treated as the source of truth.
     */
    public function callback(Request $request): View|RedirectResponse
    {
        $reference = $request->string('reference')->trim()->toString();

        if ($reference === '') {
            return redirect()->route('events.index')->with('status', 'That payment link is incomplete.');
        }

        $payment = Payment::query()->where('reference', $reference)->first();

        if (! $payment) {
            return redirect()->route('events.index')->with('status', 'We could not find that payment.');
        }

        $registration = $payment->registration;

        // The gateway hands the attendee back to this browser, so bind the result
        // to the session the same way the registration flow does. The reference
        // itself is an unguessable, single-purpose token.
        $request->session()->put('registration_reference', $registration->reference);

        if ($payment->isSuccessful()) {
            return redirect()->route('registrations.confirmation', $registration);
        }

        $result = $this->processor->verify($payment);

        if ($result['ok']) {
            $this->communications->resend($registration);

            return redirect()->route('registrations.confirmation', $registration);
        }

        return view('payments.pending', [
            'payment' => $payment,
            'reason' => $result['reason'],
        ]);
    }

    /**
     * Server-to-server notification from the gateway.
     */
    public function webhook(Request $request, PaystackGateway $gateway): JsonResponse
    {
        $payload = $request->json()->all();

        if (! $gateway->verifyWebhookSignature($request->header('x-paystack-signature'), $request->getContent())) {
            Log::warning('Rejected Paystack webhook with an invalid signature.', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $event = (string) ($payload['event'] ?? '');
        $data = $payload['data'] ?? [];
        $reference = (string) ($data['reference'] ?? '');

        if ($reference === '') {
            return response()->json(['message' => 'Missing reference.'], 422);
        }

        $payment = Payment::query()->where('reference', $reference)->first();

        if (! $payment) {
            // Answer 200 so the gateway stops retrying an unknown reference, but
            // leave a trace for the team.
            Log::warning('Paystack webhook referenced an unknown payment.', [
                'reference' => $reference,
                'event' => $event,
            ]);

            return response()->json(['message' => 'Unknown reference.']);
        }

        if (str_starts_with($event, 'charge.failed')) {
            $this->processor->fail($payment, $data);
        } elseif (in_array($event, ['charge.success', 'payment.corrected'], true)) {
            if ($gateway->verificationMatches($payment, $data)) {
                $this->processor->settle($payment, $data);
                $this->communications->resend($payment->registration);
            } else {
                // Keep the payload for reconciliation, but never confirm: the
                // amount, currency or reference disagrees with what we stored.
                $this->processor->flagForReconciliation($payment, $data);

                Log::warning('Paystack webhook payload did not match the stored payment.', [
                    'reference' => $reference,
                    'event' => $event,
                    'expected_amount' => $payment->amount,
                    'gateway_amount' => $data['amount'] ?? null,
                    'expected_currency' => $payment->currency,
                    'gateway_currency' => $data['currency'] ?? null,
                ]);
            }
        }

        return response()->json(['message' => 'ok']);
    }

    /**
     * Let the attendee who owns the registration resume or retry a payment that
     * never completed. Pending payments resume the same reference; failed or
     * abandoned payments get a fresh one.
     */
    public function retry(Request $request, Payment $payment): RedirectResponse
    {
        $payment->loadMissing('registration');

        $registration = $payment->registration;

        if ($request->session()->get('registration_reference') !== $registration->reference) {
            $userId = $request->user()?->id;

            // A guest registration has no account, so both sides must be present
            // before they are allowed to match.
            abort_unless(
                $userId !== null && $registration->user_id !== null && $userId === $registration->user_id,
                404,
            );
        }

        if ($payment->isSuccessful()) {
            $this->communications->resend($registration);

            return to_route('registrations.confirmation', $registration);
        }

        $result = $this->processor->verify($payment);

        if ($result['ok']) {
            $this->communications->resend($registration);

            return to_route('registrations.confirmation', $registration);
        }

        if ($result['reason'] === 'mismatch') {
            return back()->with('error', 'This payment could not be confirmed. Please contact the organizer.');
        }

        if ($result['reason'] === 'unavailable') {
            return back()->with('error', 'We could not reach the payment provider. Please try again shortly.');
        }

        // Unpaid: reuse the pending reference, or start a new one after a failure.
        $fresh = $this->processor->initiate($registration);

        return redirect()->away(app(PaystackGateway::class)->initialize($fresh, $registration->email));
    }
}
