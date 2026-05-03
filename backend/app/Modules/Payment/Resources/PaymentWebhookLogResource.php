<?php

namespace App\Modules\Payment\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentWebhookLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'event_id' => $this->event_id,
            'transaction_id' => $this->transaction_id,
            'status' => $this->status,
            'failure_reason' => $this->failure_reason,
            'processed_at' => $this->processed_at,
            'created_at' => $this->created_at,
        ];
    }
}
