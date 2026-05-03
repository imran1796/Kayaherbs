<?php

namespace App\Modules\Order\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderReturnRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'reason' => $this->reason,
            'metadata' => $this->metadata,
            'requested_by' => $this->whenLoaded('requestedBy', fn (): ?array => $this->requestedBy === null ? null : [
                'id' => $this->requestedBy->id,
                'name' => $this->requestedBy->name,
                'email' => $this->requestedBy->email,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
