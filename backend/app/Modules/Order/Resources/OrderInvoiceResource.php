<?php

namespace App\Modules\Order\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderInvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'status' => $this->status,
            'totals' => [
                'subtotal' => $this->subtotal,
                'shipping_total' => $this->shipping_total,
                'grand_total' => $this->grand_total,
                'currency' => $this->currency,
            ],
            'billing_address' => $this->billing_address,
            'shipping_address' => $this->shipping_address,
            'metadata' => $this->metadata,
            'issued_by' => $this->whenLoaded('issuedBy', fn (): ?array => $this->issuedBy === null ? null : [
                'id' => $this->issuedBy->id,
                'name' => $this->issuedBy->name,
                'email' => $this->issuedBy->email,
            ]),
            'issued_at' => $this->issued_at,
        ];
    }
}
