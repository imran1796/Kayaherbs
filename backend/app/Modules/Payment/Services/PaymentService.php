<?php

namespace App\Modules\Payment\Services;

use App\Core\Services\AuditLogger;
use App\Core\Services\BaseService;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Modules\Payment\Repositories\PaymentRepository;
use App\Modules\Payment\Support\CodStatus;
use App\Modules\Payment\Support\PaymentStatus;
use Illuminate\Validation\ValidationException;

class PaymentService extends BaseService
{
    public function __construct(
        private readonly PaymentRepository $payments,
        private readonly AuditLogger $auditLogger
    ) {}

    public function createInitialPaymentForOrder(Order $order, ?User $actor = null): Payment
    {
        return $this->transaction('payment.initial.create', function () use ($order, $actor): Payment {
            $payment = $this->payments->createForOrder($order, [
                'provider' => $order->payment_method_code,
                'method_name' => $order->payment_method_name,
                'status' => $order->payment_status ?: PaymentStatus::PENDING,
                'cod_status' => $order->payment_method_code === 'cod' ? CodStatus::PENDING : CodStatus::NOT_APPLICABLE,
                'amount' => $order->grand_total,
                'currency' => $order->currency,
                'metadata' => [
                    'source' => 'checkout',
                    'order_number' => $order->order_number,
                ],
            ]);

            $this->syncOrderPaymentStatus($order);

            $this->auditLogger->record(
                'payment.created',
                actor: $actor,
                auditable: $payment,
                metadata: [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $payment->status,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                ],
                guard: 'sanctum'
            );

            return $payment;
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function transitionStatusById(
        int $paymentId,
        string $toStatus,
        ?User $actor = null,
        array $metadata = [],
        ?string $transactionId = null,
        ?string $providerReference = null
    ): Payment {
        return $this->transitionStatus(
            $this->payments->findOrFail($paymentId),
            $toStatus,
            $actor,
            $metadata,
            $transactionId,
            $providerReference
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function transitionStatus(
        Payment $payment,
        string $toStatus,
        ?User $actor = null,
        array $metadata = [],
        ?string $transactionId = null,
        ?string $providerReference = null
    ): Payment
    {
        return $this->transaction('payment.status.transition', function () use ($payment, $toStatus, $actor, $metadata, $transactionId, $providerReference): Payment {
            $lockedPayment = $this->payments->lockForUpdate($payment->id);
            $fromStatus = (string) $lockedPayment->status;

            if (! in_array($toStatus, PaymentStatus::values(), true)) {
                throw ValidationException::withMessages([
                    'status' => ['Selected payment status is not supported.'],
                ]);
            }

            if ($fromStatus === $toStatus) {
                return $lockedPayment;
            }

            if (! PaymentStatus::canTransition($fromStatus, $toStatus)) {
                throw ValidationException::withMessages([
                    'status' => ["Payment cannot transition from {$fromStatus} to {$toStatus}."],
                ]);
            }

            $lockedPayment = $this->payments->update($lockedPayment, [
                'status' => $toStatus,
                'paid_at' => $toStatus === PaymentStatus::PAID ? ($lockedPayment->paid_at ?? now()) : $lockedPayment->paid_at,
                'transaction_id' => $transactionId ?? $lockedPayment->transaction_id,
                'provider_reference' => $providerReference ?? $lockedPayment->provider_reference,
                'metadata' => array_filter([
                    ...($lockedPayment->metadata ?? []),
                    ...$metadata,
                ]),
            ]);

            $order = $this->syncOrderPaymentStatus($lockedPayment->order);

            $this->auditLogger->record(
                'payment.status.changed',
                actor: $actor,
                auditable: $lockedPayment,
                metadata: [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'from_status' => $fromStatus,
                    'to_status' => $toStatus,
                    ...$metadata,
                ],
                guard: 'sanctum'
            );

            return $lockedPayment;
        }, 3);
    }

    public function markCodCollected(Payment $payment, ?User $actor = null, array $metadata = []): Payment
    {
        if ($payment->provider !== 'cod') {
            throw ValidationException::withMessages([
                'provider' => ['Only COD payments can be collected through this workflow.'],
            ]);
        }

        return $this->transaction('payment.cod.collect', function () use ($payment, $actor, $metadata): Payment {
            $lockedPayment = $this->payments->lockForUpdate($payment->id);

            if ($lockedPayment->provider !== 'cod') {
                throw ValidationException::withMessages([
                    'provider' => ['Only COD payments can be collected through this workflow.'],
                ]);
            }

            $updatedPayment = $this->transitionStatus($lockedPayment, PaymentStatus::PAID, $actor, [
                ...$metadata,
                'cod_status' => CodStatus::COLLECTED,
            ]);

            $updatedPayment = $this->payments->update($updatedPayment, [
                'cod_status' => CodStatus::COLLECTED,
                'collected_at' => $updatedPayment->collected_at ?? now(),
            ]);

            $this->auditLogger->record(
                'payment.cod.collected',
                actor: $actor,
                auditable: $updatedPayment,
                metadata: [
                    'order_id' => $updatedPayment->order_id,
                    'amount' => $updatedPayment->amount,
                    'currency' => $updatedPayment->currency,
                    ...$metadata,
                ],
                guard: 'sanctum'
            );

            return $updatedPayment;
        }, 3);
    }

    public function markCodCollectedById(int $paymentId, ?User $actor = null, array $metadata = []): Payment
    {
        return $this->markCodCollected(
            $this->payments->findOrFail($paymentId),
            $actor,
            $metadata
        );
    }

    public function syncOrderPaymentStatus(Order $order): Order
    {
        $lockedOrder = $this->payments->lockOrder($order->id);
        $payments = $this->payments->paymentsForOrder($lockedOrder);
        $status = PaymentStatus::UNPAID;

        if ($payments->contains('status', PaymentStatus::REFUNDED)) {
            $status = PaymentStatus::REFUNDED;
        } elseif ($payments->contains('status', PaymentStatus::PARTIALLY_REFUNDED)) {
            $status = PaymentStatus::PARTIALLY_REFUNDED;
        } elseif ($payments->contains('status', PaymentStatus::PAID)) {
            $status = PaymentStatus::PAID;
        } elseif ($payments->contains('status', PaymentStatus::PENDING)) {
            $status = PaymentStatus::PENDING;
        } elseif ($payments->contains('status', PaymentStatus::FAILED)) {
            $status = PaymentStatus::FAILED;
        }

        if ($lockedOrder->payment_status === $status) {
            return $lockedOrder;
        }

        return $this->payments->updateOrder($lockedOrder, [
            'payment_status' => $status,
        ]);
    }
}
