<?php

namespace App\Modules\Order\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderShipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'carrier_name' => $this->carrier_name,
            'tracking_number' => $this->tracking_number,
            'tracking_url' => $this->tracking_url,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'created_by' => $this->whenLoaded('createdBy', fn (): ?array => $this->createdBy === null ? null : [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
                'email' => $this->createdBy->email,
            ]),
            'shipped_at' => $this->shipped_at,
            'created_at' => $this->created_at,
        ];
    }
}
