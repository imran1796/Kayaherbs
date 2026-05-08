<?php

namespace App\Modules\Order\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublicOrderLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_number' => ['required', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'order_number' => strtoupper(trim((string) $this->input('order_number'))),
            'phone' => trim((string) $this->input('phone')),
        ]);
    }
}
