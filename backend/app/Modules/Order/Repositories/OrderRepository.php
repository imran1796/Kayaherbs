<?php

namespace App\Modules\Order\Repositories;

use App\Models\Order;
use App\Models\OrderInvoice;
use App\Models\OrderNote;
use App\Models\OrderPackingSlip;
use App\Models\OrderReturnRequest;
use App\Models\OrderShipment;
use App\Models\OrderStatusHistory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class OrderRepository
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForAdmin(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Order::query()
            ->with('customer')
            ->latest();

        $this->applyAdminFilters($query, $filters);

        return $query
            ->paginate($perPage);
    }

    public function findWithDetailsOrFail(int $orderId): Order
    {
        /** @var Order $order */
        $order = Order::query()
            ->with([
                'customer',
                'items',
                'payments',
                'statusHistories.actor',
                'notes.author',
                'returnRequests.requestedBy',
                'invoice.issuedBy',
                'packingSlip.generatedBy',
                'shipments.createdBy',
            ])
            ->findOrFail($orderId);

        return $order;
    }

    public function findInvoicePrintDataOrFail(int $orderId): Order
    {
        /** @var Order $order */
        $order = Order::query()
            ->with([
                'customer',
                'items',
                'invoice.issuedBy',
            ])
            ->whereKey($orderId)
            ->whereHas('invoice')
            ->firstOrFail();

        return $order;
    }

    public function findPackingSlipPrintDataOrFail(int $orderId): Order
    {
        /** @var Order $order */
        $order = Order::query()
            ->with([
                'customer',
                'packingSlip.generatedBy',
            ])
            ->whereKey($orderId)
            ->whereHas('packingSlip')
            ->firstOrFail();

        return $order;
    }

    public function lockForUpdate(int $orderId): Order
    {
        /** @var Order $order */
        $order = Order::query()
            ->whereKey($orderId)
            ->lockForUpdate()
            ->firstOrFail();

        return $order;
    }

    public function lockWithItemsForUpdate(int $orderId): Order
    {
        return $this->lockForUpdate($orderId)->load('items');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Order $order, array $data): Order
    {
        $order->update($data);

        return $order->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createStatusHistory(Order $order, array $data): OrderStatusHistory
    {
        /** @var OrderStatusHistory $history */
        $history = $order->statusHistories()->create($data);

        return $history;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createNote(Order $order, array $data): OrderNote
    {
        /** @var OrderNote $note */
        $note = $order->notes()->create($data);

        return $note->load('author');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createReturnRequest(Order $order, array $data): OrderReturnRequest
    {
        /** @var OrderReturnRequest $returnRequest */
        $returnRequest = $order->returnRequests()->create($data);

        return $returnRequest->load('requestedBy');
    }

    public function findInvoiceForOrder(Order $order): ?OrderInvoice
    {
        /** @var OrderInvoice|null $invoice */
        $invoice = $order->invoice()->with('issuedBy')->first();

        return $invoice;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createInvoice(Order $order, array $data): OrderInvoice
    {
        /** @var OrderInvoice $invoice */
        $invoice = $order->invoice()->create([
            ...$data,
            'invoice_number' => $this->nextInvoiceNumber(),
        ]);

        return $invoice->load('issuedBy');
    }

    public function findPackingSlipForOrder(Order $order): ?OrderPackingSlip
    {
        /** @var OrderPackingSlip|null $packingSlip */
        $packingSlip = $order->packingSlip()->with('generatedBy')->first();

        return $packingSlip;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPackingSlip(Order $order, array $data): OrderPackingSlip
    {
        /** @var OrderPackingSlip $packingSlip */
        $packingSlip = $order->packingSlip()->create([
            ...$data,
            'packing_slip_number' => $this->nextPackingSlipNumber(),
        ]);

        return $packingSlip->load('generatedBy');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createShipment(Order $order, array $data): OrderShipment
    {
        /** @var OrderShipment $shipment */
        $shipment = $order->shipments()->create($data);

        return $shipment->load('createdBy');
    }

    private function nextInvoiceNumber(): string
    {
        do {
            $number = 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
        } while (OrderInvoice::query()->where('invoice_number', $number)->exists());

        return $number;
    }

    private function nextPackingSlipNumber(): string
    {
        do {
            $number = 'PKG-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
        } while (OrderPackingSlip::query()->where('packing_slip_number', $number)->exists());

        return $number;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyAdminFilters(Builder $query, array $filters): void
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $paymentStatus = trim((string) ($filters['payment_status'] ?? ''));
        $fulfillmentStatus = trim((string) ($filters['fulfillment_status'] ?? ''));

        if ($search !== '') {
            $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery
                    ->where('order_number', 'like', '%'.$search.'%')
                    ->orWhereHas('customer', function (Builder $customerQuery) use ($search): void {
                        $customerQuery
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    });
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($paymentStatus !== '') {
            $query->where('payment_status', $paymentStatus);
        }

        if ($fulfillmentStatus !== '') {
            $query->where('fulfillment_status', $fulfillmentStatus);
        }
    }
}
