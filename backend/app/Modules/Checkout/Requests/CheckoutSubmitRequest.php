<?php

namespace App\Modules\Checkout\Requests;

class CheckoutSubmitRequest extends CheckoutValidationRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'idempotency_key' => ['required', 'string', 'max:120'],
        ];
    }
}
