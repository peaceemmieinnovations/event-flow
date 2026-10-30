<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class PaystackGateway
{
    /**
     * Create a unique, unguessable payment reference.
     */
    public function newReference(): string
    {
        return 'PAY-'.Str::upper(Str::random(12));
    }

    /**
     * Initialize a transaction on the gateway and return the authorization URL.
     */
    public function initialize(Payment $payment, string $email): string
    {
        $response = $this->client()->post(rtrim($this->baseUrl(), '/').'/transaction/initialize', [
            'email' => $email,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'reference' => $payment->reference,
            'callback_url' => route('payments.callback'),
        ])->throw()->json();

        $url = data_get($response, 'data.authorization_url');

        if (! is_string($url) || $url === '') {
            Log::warning('Paystack initialization returned no authorization URL.', [
                'payment_reference' => $payment->reference,
            ]);

            throw new RuntimeException('The payment gateway did not return a checkout URL.');
        }

        return $url;
    }

    /**
     * Verify a transaction server-side. The gateway is the source of truth.
     *
     * @return array<string, mixed>
     */
    public function verify(string $reference): array
    {
        return $this->client()->get($this->verifyUrl($reference))->throw()->json('data') ?? [];
    }

    /**
     * Compute the HMAC signature Paystack sends with webhook deliveries.
     *
     * Paystack signs the raw request body with the account *secret key*, not a
     * separate webhook key, so verification and signing must use the same value.
     */
    public function signatureFor(string $rawBody): string
    {
        return hash_hmac('sha512', $rawBody, $this->secretKey());
    }

    public function verifyWebhookSignature(?string $signature, string $rawBody): bool
    {
        if (blank($signature)) {
            return false;
        }

        try {
            $expected = $this->signatureFor($rawBody);
        } catch (RuntimeException) {
            // Without a secret key the webhook must refuse rather than trust blindly.
            return false;
        }

        return hash_equals($expected, $signature);
    }

    /**
     * Whether webhooks can be validated at all.
     *
     * Without a configured secret key there is nothing to verify against, so the
     * endpoint refuses everything and the admin UI warns before a go-live.
     */
    public function hasWebhookSecret(): bool
    {
        return filled(config('services.paystack.secret_key'));
    }

    private function client(): PendingRequest
    {
        return Http::withToken($this->secretKey())
            ->acceptJson()
            ->asJson()
            ->timeout(20);
    }

    private function secretKey(): string
    {
        $secret = config('services.paystack.secret_key');

        if (! is_string($secret) || $secret === '') {
            throw new RuntimeException('PAYSTACK_SECRET_KEY is not configured.');
        }

        return $secret;
    }

    private function baseUrl(): string
    {
        return (string) config('services.paystack.base_url', 'https://api.paystack.co');
    }

    private function verifyUrl(string $reference): string
    {
        return rtrim($this->baseUrl(), '/').'/transaction/verify/'.urlencode($reference);
    }

    /**
     * Only a small, non-sensitive subset of the gateway payload is persisted.
     * Authorization URLs and raw provider payloads are intentionally dropped.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function safeMetadata(array $data): array
    {
        return array_filter([
            'gateway_status' => $data['status'] ?? null,
            'gateway_reference' => $data['reference'] ?? null,
            'paid_at' => $data['paid_at'] ?? null,
            'channel' => $data['channel'] ?? null,
            'gateway_amount' => $data['amount'] ?? null,
            'gateway_currency' => $data['currency'] ?? null,
        ], static fn ($value): bool => $value !== null);
    }

    /**
     * Determine whether a verification payload matches the expected payment.
     *
     * @param  array<string, mixed>  $data
     */
    public function verificationMatches(Payment $payment, array $data): bool
    {
        return ($data['status'] ?? null) === 'success'
            && isset($data['amount']) && (int) $data['amount'] === $payment->amount
            && ($data['currency'] ?? null) === $payment->currency
            && ($data['reference'] ?? $payment->reference) === $payment->reference;
    }
}
