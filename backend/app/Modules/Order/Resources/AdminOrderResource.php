<?php

namespace App\Modules\Order\Resources;

use App\Modules\Checkout\Resources\OrderItemResource;
use App\Modules\Payment\Resources\PaymentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'customer' => $this->whenLoaded('customer', fn (): array => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'email' => $this->customer->email,
            ]),
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'fulfillment_status' => $this->fulfillment_status,
            'totals' => [
                'subtotal' => $this->subtotal,
                'shipping_total' => $this->shipping_total,
                'grand_total' => $this->grand_total,
                'currency' => $this->currency,
            ],
            'shipping_address' => $this->shipping_address,
            'billing_address' => $this->billing_address,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'status_history' => OrderStatusHistoryResource::collection($this->whenLoaded('statusHistories')),
            'notes' => OrderNoteResource::collection($this->whenLoaded('notes')),
            'return_requests' => OrderReturnRequestResource::collection($this->whenLoaded('returnRequests')),
            'invoice' => $this->whenLoaded('invoice', fn () => $this->invoice === null ? null : new OrderInvoiceResource($this->invoice)),
            'packing_slip' => $this->whenLoaded('packingSlip', fn () => $this->packingSlip === null ? null : new OrderPackingSlipResource($this->packingSlip)),
            'shipments' => OrderShipmentResource::collection($this->whenLoaded('shipments')),
            'lifecycle' => [
                'placed_at' => $this->placed_at,
                'confirmed_at' => $this->confirmed_at,
                'processing_at' => $this->processing_at,
                'packed_at' => $this->packed_at,
                'shipped_at' => $this->shipped_at,
                'delivered_at' => $this->delivered_at,
                'failed_delivery_at' => $this->failed_delivery_at,
                'return_requested_at' => $this->return_requested_at,
                'returned_at' => $this->returned_at,
                'refunded_at' => $this->refunded_at,
                'cancelled_at' => $this->cancelled_at,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
