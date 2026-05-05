<?php

namespace App\Modules\Cart\Resources;

use App\Modules\Promotion\Services\CouponDiscountService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = $this->whenLoaded('items');
        $totals = app(CouponDiscountService::class)->cartTotals($this->resource);

        return [
            'id' => $this->id,
            'cart_token' => $this->cart_token,
            'status' => $this->status,
            'expires_at' => $this->expires_at,
            'items_count' => $this->items->where('is_available', true)->sum('quantity'),
            'unavailable_items_count' => $this->items->where('is_available', false)->count(),
            'subtotal' => $totals['subtotal'],
            'discount_total' => $totals['discount_total'],
            'grand_total' => $totals['grand_total'],
            'coupon' => $totals['coupon'],
            'items' => CartItemResource::collection($items),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
