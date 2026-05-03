<?php

namespace App\Modules\Payment\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'method_name' => $this->method_name,
            'status' => $this->status,
            'cod_status' => $this->cod_status,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'transaction_id' => $this->transaction_id,
            'provider_reference' => $this->provider_reference,
            'metadata' => $this->metadata,
            'paid_at' => $this->paid_at,
            'collected_at' => $this->collected_at,
            'created_at' => $this->created_at,
        ];
    }
}
