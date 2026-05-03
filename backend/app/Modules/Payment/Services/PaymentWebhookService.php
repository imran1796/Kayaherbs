<?php

namespace App\Modules\Payment\Services;

use App\Core\Services\BaseService;
use App\Models\PaymentWebhookLog;
use App\Modules\Payment\Repositories\PaymentRepository;
use App\Modules\Payment\Repositories\PaymentWebhookRepository;
use App\Modules\Payment\Support\PaymentStatus;
use Illuminate\Validation\ValidationException;

class PaymentWebhookService extends BaseService
{
    public function __construct(
        private readonly PaymentWebhookRepository $webhooks,
        private readonly PaymentRepository $payments,
        private readonly PaymentService $paymentService
    ) {}

    /**
     * @param  array<string, string>  $headers
     * @return array{log: PaymentWebhookLog, duplicate: bool}
     */
    public function handle(string $provider, array $headers, string $payload): array
    {
        return $this->transaction('payment.webhook.handle', function () use ($provider, $headers, $payload): array {
            $data = json_decode($payload, true);

            if (! is_array($data)) {
                throw ValidationException::withMessages([
                    'payload' => ['Webhook payload must be valid JSON.'],
                ]);
            }

            $eventId = isset($data['event_id']) ? (string) $data['event_id'] : null;
            $transactionId = isset($data['transaction_id']) ? (string) $data['transaction_id'] : null;
            $payloadHash = hash('sha256', $payload);

            $duplicate = $this->webhooks->findDuplicate($provider, $eventId, $payloadHash);

            if ($duplicate !== null) {
                return ['log' => $duplicate, 'duplicate' => true];
            }

            if (! $this->signatureIsValid($provider, $headers, $payload)) {
                $log = $this->webhooks->create([
                    'provider' => $provider,
                    'event_id' => $eventId,
                    'transaction_id' => $transactionId,
                    'payload_hash' => $payloadHash,
                    'payload' => $data,
                    'status' => 'rejected',
                    'failure_reason' => 'invalid_signature',
                ]);

                return ['log' => $log, 'duplicate' => false];
            }

            $log = $this->webhooks->create([
                'provider' => $provider,
                'event_id' => $eventId,
                'transaction_id' => $transactionId,
                'payload_hash' => $payloadHash,
                'payload' => $data,
                'status' => 'received',
            ]);

            $payment = $transactionId !== null
                ? $this->payments->findByProviderTransaction($provider, $transactionId)
                : null;

            if ($payment === null) {
                return [
                    'log' => $this->webhooks->update($log, [
                        'status' => 'ignored',
                        'failure_reason' => 'payment_not_found',
                        'processed_at' => now(),
                    ]),
                    'duplicate' => false,
                ];
            }

            $status = (string) ($data['status'] ?? '');

            if (! in_array($status, [PaymentStatus::PAID, PaymentStatus::FAILED], true)) {
                return [
                    'log' => $this->webhooks->update($log, [
                        'status' => 'ignored',
                        'failure_reason' => 'unsupported_status',
                        'processed_at' => now(),
                    ]),
                    'duplicate' => false,
                ];
            }

            $this->paymentService->transitionStatus($payment, $status, metadata: [
                'source' => 'webhook',
                'event_id' => $eventId,
                'payload_hash' => $payloadHash,
            ]);

            return [
                'log' => $this->webhooks->update($log, [
                    'status' => 'processed',
                    'processed_at' => now(),
                ]),
                'duplicate' => false,
            ];
        }, 3);
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function signatureIsValid(string $provider, array $headers, string $payload): bool
    {
        $secret = config("services.payment_webhooks.providers.{$provider}.secret");

        if (! is_string($secret) || $secret === '') {
            return false;
        }

        $signatureHeader = (string) config('services.payment_webhooks.signature_header', 'X-Payment-Signature');
        $signature = $headers[strtolower($signatureHeader)] ?? $headers[$signatureHeader] ?? null;

        if (! is_string($signature) || $signature === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $payload, $secret), $signature);
    }
}
