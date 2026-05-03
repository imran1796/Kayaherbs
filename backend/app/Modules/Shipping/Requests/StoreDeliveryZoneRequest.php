<?php

namespace App\Modules\Shipping\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreDeliveryZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'code' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:delivery_zones,code'],
            'country' => ['nullable', 'string', 'size:2'],
            'states' => ['nullable', 'array'],
            'states.*' => ['required', 'string', 'max:120'],
            'cities' => ['nullable', 'array'],
            'cities.*' => ['required', 'string', 'max:120'],
            'postal_codes' => ['nullable', 'array'],
            'postal_codes.*' => ['required', 'string', 'max:30'],
            'status' => ['nullable', 'in:active,inactive'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $code = (string) ($this->input('code') ?: $this->input('name'));

        $this->merge([
            'code' => Str::slug($code),
            'country' => strtoupper((string) $this->input('country', 'BD')),
            'states' => $this->normalizedList('states'),
            'cities' => $this->normalizedList('cities'),
            'postal_codes' => $this->normalizedList('postal_codes'),
            'status' => $this->input('status', 'active'),
            'sort_order' => (int) $this->input('sort_order', 0),
        ]);
    }

    /**
     * @return array<int, string>|null
     */
    private function normalizedList(string $key): ?array
    {
        $value = $this->input($key);

        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = array_map('trim', explode(',', $value));
        }

        if (! is_array($value)) {
            return null;
        }

        $items = array_values(array_filter(array_map(
            fn ($item): string => trim((string) $item),
            $value
        )));

        return $items === [] ? null : $items;
    }
}
