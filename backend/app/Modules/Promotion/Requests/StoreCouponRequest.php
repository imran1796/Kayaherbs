<?php

namespace App\Modules\Promotion\Requests;

use App\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'code' => ['required', 'string', 'max:80', 'regex:/^[A-Z0-9_-]+$/', 'unique:coupons,code'],
            'discount_type' => ['required', Rule::in([
                Coupon::DISCOUNT_FIXED,
                Coupon::DISCOUNT_PERCENTAGE,
                Coupon::DISCOUNT_FREE_DELIVERY,
            ])],
            'discount_value' => [
                Rule::requiredIf(fn (): bool => $this->input('discount_type') !== Coupon::DISCOUNT_FREE_DELIVERY),
                'nullable',
                'numeric',
                'min:0',
                Rule::when(
                    $this->input('discount_type') === Coupon::DISCOUNT_PERCENTAGE,
                    ['max:100']
                ),
            ],
            'minimum_order_value' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in([Coupon::STATUS_ACTIVE, Coupon::STATUS_INACTIVE])],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => [
                'nullable',
                'date',
                Rule::when($this->filled('starts_at'), ['after_or_equal:starts_at']),
            ],
            'eligible_product_ids' => ['nullable', 'array'],
            'eligible_product_ids.*' => ['integer', 'exists:products,id', 'distinct'],
            'eligible_category_ids' => ['nullable', 'array'],
            'eligible_category_ids.*' => ['integer', 'exists:categories,id', 'distinct'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_customer_usage_limit' => ['nullable', 'integer', 'min:1'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
            'status' => $this->input('status', Coupon::STATUS_INACTIVE),
            'eligible_product_ids' => $this->integerList('eligible_product_ids'),
            'eligible_category_ids' => $this->integerList('eligible_category_ids'),
            'starts_at' => $this->normalizeDatetimeField($this->input('starts_at')),
            'ends_at' => $this->normalizeDatetimeField($this->input('ends_at')),
        ]);
    }

    private function normalizeDatetimeField(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value)) {
            return $value;
        }

        $raw = trim($value);

        if ($raw === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $raw)) {
            return str_replace('T', ' ', $raw).':00';
        }

        return $raw;
    }

    /**
     * @return array<int, int>|null
     */
    private function integerList(string $key): ?array
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

        $items = array_values(array_unique(array_filter(array_map(
            fn ($item): int => (int) $item,
            $value
        ))));

        return $items === [] ? null : $items;
    }
}
