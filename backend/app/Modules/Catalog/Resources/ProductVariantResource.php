<?php

namespace App\Modules\Catalog\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'price' => $this->price,
            'compare_at_price' => $this->compare_at_price,
            'sort_order' => $this->sort_order,
            'is_default' => $this->is_default,
            'status' => $this->status,
            'stock' => $this->whenLoaded('stock', fn (): array => [
                'quantity_on_hand' => $this->stock->quantity_on_hand,
                'quantity_reserved' => $this->stock->quantity_reserved,
                'available_quantity' => $this->stock->available_quantity,
                'low_stock_threshold' => $this->stock->low_stock_threshold,
                'is_low_stock' => $this->stock->is_low_stock,
                'track_inventory' => $this->stock->track_inventory,
                'allow_backorder' => $this->stock->allow_backorder,
            ]),
        ];
    }
}
