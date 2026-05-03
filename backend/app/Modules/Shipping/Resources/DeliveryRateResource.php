<?php

namespace App\Modules\Shipping\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryRateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'delivery_zone_id' => $this->delivery_zone_id,
            'zone' => $this->whenLoaded('zone', fn (): ?array => $this->zone ? [
                'id' => $this->zone->id,
                'name' => $this->zone->name,
                'code' => $this->zone->code,
                'country' => $this->zone->country,
            ] : null),
            'name' => $this->name,
            'code' => $this->code,
            'amount' => $this->amount,
            'min_order_amount' => $this->min_order_amount,
            'max_order_amount' => $this->max_order_amount,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
