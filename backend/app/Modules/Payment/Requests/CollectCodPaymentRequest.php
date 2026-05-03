<?php

namespace App\Modules\Payment\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CollectCodPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'metadata' => ['nullable', 'array'],
        ];
    }
}
