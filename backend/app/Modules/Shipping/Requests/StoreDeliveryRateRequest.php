<?php

namespace App\Modules\Shipping\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreDeliveryRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delivery_zone_id' => ['required', 'integer', 'exists:delivery_zones,id'],
            'name' => ['required', 'string', 'max:160'],
            'code' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:delivery_rates,code'],
            'amount' => ['required', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_order_amount' => ['nullable', 'numeric', 'min:0', 'gte:min_order_amount'],
            'status' => ['nullable', 'in:active,inactive'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $code = (string) ($this->input('code') ?: $this->input('name'));

        $this->merge([
            'code' => Str::slug($code),
            'status' => $this->input('status', 'active'),
            'sort_order' => (int) $this->input('sort_order', 0),
        ]);
    }
}
