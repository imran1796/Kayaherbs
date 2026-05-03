<?php

namespace App\Modules\Cart\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = $this->whenLoaded('items');

        return [
            'id' => $this->id,
            'cart_token' => $this->cart_token,
            'status' => $this->status,
            'expires_at' => $this->expires_at,
            'items_count' => $this->items->where('is_available', true)->sum('quantity'),
            'unavailable_items_count' => $this->items->where('is_available', false)->count(),
            'subtotal' => number_format((float) $this->items->where('is_available', true)->sum('line_total'), 2, '.', ''),
            'items' => CartItemResource::collection($items),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
