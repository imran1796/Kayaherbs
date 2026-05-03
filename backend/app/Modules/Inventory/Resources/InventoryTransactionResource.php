<?php

namespace App\Modules\Inventory\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inventory_stock_id' => $this->inventory_stock_id,
            'product_variant_id' => $this->product_variant_id,
            'product_name' => $this->whenLoaded('variant', fn () => $this->variant?->product?->name),
            'variant_name' => $this->whenLoaded('variant', fn () => $this->variant?->name),
            'sku' => $this->whenLoaded('variant', fn () => $this->variant?->sku),
            'actor_name' => $this->whenLoaded('actor', fn () => $this->actor?->name),
            'type' => $this->type,
            'quantity_delta' => $this->quantity_delta,
            'quantity_on_hand_after' => $this->quantity_on_hand_after,
            'quantity_reserved_after' => $this->quantity_reserved_after,
            'note' => $this->note,
            'created_at' => $this->created_at,
        ];
    }
}
