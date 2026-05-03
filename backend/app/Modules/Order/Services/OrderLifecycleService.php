<?php

namespace App\Modules\Order\Services;

use App\Core\Services\AuditLogger;
use App\Core\Services\BaseService;
use App\Models\Order;
use App\Models\OrderInvoice;
use App\Models\OrderNote;
use App\Models\OrderPackingSlip;
use App\Models\OrderReturnRequest;
use App\Models\OrderShipment;
use App\Models\OrderStatusHistory;
use App\Models\User;
use App\Modules\Inventory\Services\InventoryStockService;
use App\Modules\Order\Repositories\OrderRepository;
use App\Modules\Order\Support\OrderStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class OrderLifecycleService extends BaseService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly OrderRepository $orders,
        private readonly InventoryStockService $inventory
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->orders->paginateForAdmin($perPage, $filters);
    }

    public function findOrFail(int $orderId): Order
    {
        return $this->orders->findWithDetailsOrFail($orderId);
    }

    public function findInvoicePrintDataOrFail(int $orderId): Order
    {
        return $this->orders->findInvoicePrintDataOrFail($orderId);
    }

    public function findPackingSlipPrintDataOrFail(int $orderId): Order
    {
        return $this->orders->findPackingSlipPrintDataOrFail($orderId);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function recordCreation(Order $order, ?User $actor = null, ?string $note = null, array $metadata = []): OrderStatusHistory
    {
        return $this->recordStatusHistory(
            $order,
            null,
            $order->status,
            $actor,
            $note ?? 'Order created.',
            $metadata
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function transitionStatus(Order $order, string $toStatus, ?User $actor = null, ?string $note = null, array $metadata = []): Order
    {
        return $this->transaction('order.status.transition', function () use ($order, $toStatus, $actor, $note, $metadata): Order {
            $lockedOrder = $this->orders->lockForUpdate($order->id);

            $fromStatus = (string) $lockedOrder->status;

            if (! in_array($toStatus, OrderStatus::values(), true)) {
                throw ValidationException::withMessages([
                    'status' => ['Selected order status is not supported.'],
                ]);
            }

            if ($fromStatus === $toStatus) {
                return $this->findOrFail($lockedOrder->id);
            }

            if (! OrderStatus::canTransition($fromStatus, $toStatus)) {
                throw ValidationException::withMessages([
                    'status' => ["Order cannot transition from {$fromStatus} to {$toStatus}."],
                ]);
            }

            $updates = [
                'status' => $toStatus,
                'fulfillment_status' => OrderStatus::fulfillmentStatus($toStatus),
            ];
            $timestampColumn = OrderStatus::timestampColumn($toStatus);

            if ($timestampColumn !== null && $lockedOrder->{$timestampColumn} === null) {
                $updates[$timestampColumn] = now();
            }

            $lockedOrder = $this->orders->update($lockedOrder, $updates);

            $this->recordStatusHistory($lockedOrder, $fromStatus, $toStatus, $actor, $note, $metadata);
            $this->auditLogger->record(
                'order.status.changed',
                actor: $actor,
                auditable: $lockedOrder,
                metadata: [
                    'order_number' => $lockedOrder->order_number,
                    'from_status' => $fromStatus,
                    'to_status' => $toStatus,
                    'note' => $note,
                    ...$metadata,
                ],
                guard: 'sanctum'
            );

            return $this->orders->findWithDetailsOrFail($lockedOrder->id);
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function addInternalNote(Order $order, string $note, ?User $actor = null, array $metadata = []): OrderNote
    {
        $orderNote = $this->orders->createNote($order, [
            'author_id' => $actor?->id,
            'note' => $note,
            'metadata' => $metadata ?: null,
        ]);

        $this->auditLogger->record(
            'order.note.created',
            actor: $actor,
            auditable: $order,
            metadata: [
                'order_number' => $order->order_number,
                'note_id' => $orderNote->id,
            ],
            guard: 'sanctum'
        );

        return $orderNote;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function cancel(Order $order, string $reason, ?User $actor = null, array $metadata = []): Order
    {
        return $this->transaction('order.cancel', function () use ($order, $reason, $actor, $metadata): Order {
            $lockedOrder = $this->orders->lockWithItemsForUpdate($order->id);
            $fromStatus = (string) $lockedOrder->status;
            $items = $lockedOrder->items;

            if ($fromStatus === OrderStatus::CANCELLED) {
                throw ValidationException::withMessages([
                    'status' => ['Order is already cancelled.'],
                ]);
            }

            if (! OrderStatus::canTransition($fromStatus, OrderStatus::CANCELLED)) {
                throw ValidationException::withMessages([
                    'status' => ["Order cannot transition from {$fromStatus} to cancelled."],
                ]);
            }

            $lockedOrder = $this->orders->update($lockedOrder, [
                'status' => OrderStatus::CANCELLED,
                'cancelled_at' => $lockedOrder->cancelled_at ?? now(),
                'fulfillment_status' => 'cancelled',
            ]);

            $this->recordStatusHistory($lockedOrder, $fromStatus, OrderStatus::CANCELLED, $actor, $reason, $metadata);

            foreach ($items as $item) {
                if ($item->product_variant_id === null) {
                    continue;
                }

                $this->inventory->release(
                    $item->product_variant_id,
                    $item->quantity,
                    $actor,
                    'Order cancelled.',
                    [
                        'order_id' => $lockedOrder->id,
                        'order_number' => $lockedOrder->order_number,
                        'order_item_id' => $item->id,
                        'reason' => $reason,
                        ...$metadata,
                    ]
                );
            }

            $this->auditLogger->record(
                'order.cancelled',
                actor: $actor,
                auditable: $lockedOrder,
                metadata: [
                    'order_number' => $lockedOrder->order_number,
                    'from_status' => $fromStatus,
                    'reason' => $reason,
                    ...$metadata,
                ],
                guard: 'sanctum'
            );

            return $this->orders->findWithDetailsOrFail($lockedOrder->id);
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function requestReturn(Order $order, string $reason, ?User $actor = null, array $metadata = []): OrderReturnRequest
    {
        return $this->transaction('order.return.request', function () use ($order, $reason, $actor, $metadata): OrderReturnRequest {
            $lockedOrder = $this->orders->lockForUpdate($order->id);

            if ($lockedOrder->status !== OrderStatus::DELIVERED) {
                throw ValidationException::withMessages([
                    'status' => ['Only delivered orders can receive a return request.'],
                ]);
            }

            $returnRequest = $this->orders->createReturnRequest($lockedOrder, [
                'requested_by_id' => $actor?->id,
                'status' => 'requested',
                'reason' => $reason,
                'metadata' => $metadata ?: null,
            ]);

            $lockedOrder = $this->orders->update($lockedOrder, [
                'status' => OrderStatus::RETURN_REQUESTED,
                'return_requested_at' => $lockedOrder->return_requested_at ?? now(),
            ]);

            $this->recordStatusHistory($lockedOrder, OrderStatus::DELIVERED, OrderStatus::RETURN_REQUESTED, $actor, $reason, $metadata);
            $this->auditLogger->record(
                'order.return.requested',
                actor: $actor,
                auditable: $lockedOrder,
                metadata: [
                    'order_number' => $lockedOrder->order_number,
                    'return_request_id' => $returnRequest->id,
                    'reason' => $reason,
                    ...$metadata,
                ],
                guard: 'sanctum'
            );

            return $returnRequest;
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function generateInvoice(Order $order, ?User $actor = null, array $metadata = []): OrderInvoice
    {
        return $this->transaction('order.invoice.generate', function () use ($order, $actor, $metadata): OrderInvoice {
            $lockedOrder = $this->orders->lockForUpdate($order->id);
            $existingInvoice = $this->orders->findInvoiceForOrder($lockedOrder);

            if ($existingInvoice !== null) {
                return $existingInvoice;
            }

            if ($lockedOrder->status === OrderStatus::CANCELLED) {
                throw ValidationException::withMessages([
                    'status' => ['Cancelled orders cannot receive an invoice.'],
                ]);
            }

            $invoice = $this->orders->createInvoice($lockedOrder, [
                'issued_by_id' => $actor?->id,
                'status' => 'issued',
                'subtotal' => $lockedOrder->subtotal,
                'shipping_total' => $lockedOrder->shipping_total,
                'grand_total' => $lockedOrder->grand_total,
                'currency' => $lockedOrder->currency,
                'billing_address' => $lockedOrder->billing_address,
                'shipping_address' => $lockedOrder->shipping_address,
                'metadata' => $metadata ?: null,
                'issued_at' => now(),
            ]);

            $this->auditLogger->record(
                'order.invoice.generated',
                actor: $actor,
                auditable: $lockedOrder,
                metadata: [
                    'order_number' => $lockedOrder->order_number,
                    'invoice_number' => $invoice->invoice_number,
                    ...$metadata,
                ],
                guard: 'sanctum'
            );

            return $invoice;
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function generatePackingSlip(Order $order, ?User $actor = null, array $metadata = []): OrderPackingSlip
    {
        return $this->transaction('order.packing_slip.generate', function () use ($order, $actor, $metadata): OrderPackingSlip {
            $lockedOrder = $this->orders->lockWithItemsForUpdate($order->id);
            $existingPackingSlip = $this->orders->findPackingSlipForOrder($lockedOrder);

            if ($existingPackingSlip !== null) {
                return $existingPackingSlip;
            }

            if ($lockedOrder->status === OrderStatus::CANCELLED) {
                throw ValidationException::withMessages([
                    'status' => ['Cancelled orders cannot receive a packing slip.'],
                ]);
            }

            $packingSlip = $this->orders->createPackingSlip($lockedOrder, [
                'generated_by_id' => $actor?->id,
                'status' => 'generated',
                'shipping_address' => $lockedOrder->shipping_address,
                'items' => $lockedOrder->items->map(fn ($item): array => [
                    'order_item_id' => $item->id,
                    'product_name' => $item->product_name,
                    'variant_name' => $item->variant_name,
                    'sku' => $item->sku,
                    'quantity' => $item->quantity,
                ])->values()->all(),
                'metadata' => $metadata ?: null,
                'generated_at' => now(),
            ]);

            $this->auditLogger->record(
                'order.packing_slip.generated',
                actor: $actor,
                auditable: $lockedOrder,
                metadata: [
                    'order_number' => $lockedOrder->order_number,
                    'packing_slip_number' => $packingSlip->packing_slip_number,
                    ...$metadata,
                ],
                guard: 'sanctum'
            );

            return $packingSlip;
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createShipment(Order $order, array $data, ?User $actor = null): OrderShipment
    {
        return $this->transaction('order.shipment.create', function () use ($order, $data, $actor): OrderShipment {
            $lockedOrder = $this->orders->lockForUpdate($order->id);

            if (in_array($lockedOrder->status, [OrderStatus::CANCELLED, OrderStatus::RETURNED, OrderStatus::REFUNDED], true)) {
                throw ValidationException::withMessages([
                    'status' => ['This order cannot receive a shipment.'],
                ]);
            }

            $shipmentStatus = $data['status'] ?? 'pending';
            $shipment = $this->orders->createShipment($lockedOrder, [
                'created_by_id' => $actor?->id,
                'carrier_name' => $data['carrier_name'],
                'tracking_number' => $data['tracking_number'] ?? null,
                'tracking_url' => $data['tracking_url'] ?? null,
                'status' => $shipmentStatus,
                'metadata' => $data['metadata'] ?? null,
                'shipped_at' => $shipmentStatus === OrderStatus::SHIPPED ? now() : null,
            ]);

            if ($shipmentStatus === OrderStatus::SHIPPED && $lockedOrder->status === OrderStatus::PACKED) {
                $lockedOrder = $this->orders->update($lockedOrder, [
                    'status' => OrderStatus::SHIPPED,
                    'fulfillment_status' => 'shipped',
                    'shipped_at' => $lockedOrder->shipped_at ?? now(),
                ]);

                $this->recordStatusHistory($lockedOrder, OrderStatus::PACKED, OrderStatus::SHIPPED, $actor, 'Shipment linked.', [
                    'shipment_id' => $shipment->id,
                ]);
            }

            $this->auditLogger->record(
                'order.shipment.linked',
                actor: $actor,
                auditable: $lockedOrder,
                metadata: [
                    'order_number' => $lockedOrder->order_number,
                    'shipment_id' => $shipment->id,
                    'carrier_name' => $shipment->carrier_name,
                    'tracking_number' => $shipment->tracking_number,
                    'shipment_status' => $shipment->status,
                ],
                guard: 'sanctum'
            );

            return $shipment;
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordStatusHistory(
        Order $order,
        ?string $fromStatus,
        string $toStatus,
        ?User $actor,
        ?string $note,
        array $metadata
    ): OrderStatusHistory {
        return $this->orders->createStatusHistory($order, [
            'actor_id' => $actor?->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'note' => $note,
            'metadata' => $metadata ?: null,
        ]);
    }
}
