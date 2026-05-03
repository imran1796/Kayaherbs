<?php

namespace App\Modules\Customer\Resources;

use App\Modules\Checkout\Resources\OrderResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerSupportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'orders_count' => $this->whenCounted('orders'),
            'addresses' => CustomerAddressResource::collection($this->whenLoaded('customerAddresses')),
            'orders' => OrderResource::collection($this->whenLoaded('orders')),
            'notes' => CustomerNoteResource::collection($this->whenLoaded('customerNotes')),
            'tags' => CustomerTagResource::collection($this->whenLoaded('customerTags')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
