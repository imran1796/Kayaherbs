<?php

namespace App\Modules\Payment\Contracts;

use App\Models\Order;
use App\Models\Payment;

interface PaymentGatewayAdapter
{
    public function provider(): string;

    /**
     * @return array{
     *     provider: string,
     *     payment_id: string|null,
     *     redirect_url?: string|null,
     *     status: string,
     *     raw?: array<string, mixed>
     * }
     */
    public function createPayment(Order $order, Payment $payment): array;

    /**
     * @param  array<string, string>  $headers
     */
    public function verifyWebhook(array $headers, string $payload): bool;

    /**
     * @param  array<string, string>  $headers
     * @return array{
     *     event_id: string|null,
     *     transaction_id: string|null,
     *     provider_reference: string|null,
     *     status: string,
     *     amount?: string|float|int|null,
     *     currency?: string|null,
     *     raw: array<string, mixed>
     * }
     */
    public function parseWebhook(array $headers, string $payload): array;

    /**
     * @return array{
     *     provider: string,
     *     refund_id: string|null,
     *     status: string,
     *     amount: string|float|int,
     *     raw?: array<string, mixed>
     * }
     */
    public function refund(Payment $payment, string|float|int $amount, array $metadata = []): array;
}
