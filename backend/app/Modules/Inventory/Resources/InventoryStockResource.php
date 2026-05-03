<?php

namespace App\Modules\Inventory\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryStockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'product_name' => $this->whenLoaded('variant', fn () => $this->variant?->product?->name),
            'variant_name' => $this->whenLoaded('variant', fn () => $this->variant?->name),
            'sku' => $this->whenLoaded('variant', fn () => $this->variant?->sku),
            'quantity_on_hand' => $this->quantity_on_hand,
            'quantity_reserved' => $this->quantity_reserved,
            'available_quantity' => $this->available_quantity,
            'low_stock_threshold' => $this->low_stock_threshold,
            'is_low_stock' => $this->is_low_stock,
            'track_inventory' => $this->track_inventory,
            'allow_backorder' => $this->allow_backorder,
            'updated_at' => $this->updated_at,
        ];
    }
}
