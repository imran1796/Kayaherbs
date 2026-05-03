<?php

namespace App\Modules\Order\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderPackingSlipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'packing_slip_number' => $this->packing_slip_number,
            'status' => $this->status,
            'shipping_address' => $this->shipping_address,
            'items' => $this->items,
            'metadata' => $this->metadata,
            'generated_by' => $this->whenLoaded('generatedBy', fn (): ?array => $this->generatedBy === null ? null : [
                'id' => $this->generatedBy->id,
                'name' => $this->generatedBy->name,
                'email' => $this->generatedBy->email,
            ]),
            'generated_at' => $this->generated_at,
        ];
    }
}
