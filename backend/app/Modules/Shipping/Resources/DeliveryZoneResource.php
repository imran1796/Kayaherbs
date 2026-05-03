<?php

namespace App\Modules\Shipping\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryZoneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'country' => $this->country,
            'states' => $this->states,
            'cities' => $this->cities,
            'postal_codes' => $this->postal_codes,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'rates' => DeliveryRateResource::collection($this->whenLoaded('rates')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
