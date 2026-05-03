<?php

namespace App\Modules\Checkout\Resources;

use App\Modules\Payment\Resources\PaymentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'fulfillment_status' => $this->fulfillment_status,
            'shipping_method' => [
                'code' => $this->shipping_method_code,
                'name' => $this->shipping_method_name,
            ],
            'payment_method' => [
                'code' => $this->payment_method_code,
                'name' => $this->payment_method_name,
            ],
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
            'lifecycle' => [
                'placed_at' => $this->placed_at,
                'confirmed_at' => $this->confirmed_at,
                'processing_at' => $this->processing_at,
                'packed_at' => $this->packed_at,
                'shipped_at' => $this->shipped_at,
                'delivered_at' => $this->delivered_at,
                'cancelled_at' => $this->cancelled_at,
            ],
            'placed_at' => $this->placed_at,
        ];
    }
}
