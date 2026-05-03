<?php

namespace App\Modules\Payment\Requests;

use App\Modules\Payment\Support\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(PaymentStatus::values())],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'provider_reference' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
