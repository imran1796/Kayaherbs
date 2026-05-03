<?php

namespace App\Modules\Checkout\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutValidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipping_address_id' => ['nullable', 'integer', 'exists:customer_addresses,id'],
            'shipping_address' => ['nullable', 'array'],
            'shipping_address.label' => ['nullable', 'string', 'max:80'],
            'shipping_address.recipient_name' => ['required_with:shipping_address', 'string', 'max:255'],
            'shipping_address.phone' => ['required_with:shipping_address', 'string', 'max:30'],
            'shipping_address.address_line_1' => ['required_with:shipping_address', 'string', 'max:255'],
            'shipping_address.address_line_2' => ['nullable', 'string', 'max:255'],
            'shipping_address.city' => ['required_with:shipping_address', 'string', 'max:120'],
            'shipping_address.state' => ['nullable', 'string', 'max:120'],
            'shipping_address.postal_code' => ['nullable', 'string', 'max:30'],
            'shipping_address.country' => ['nullable', 'string', 'size:2'],

            'billing_same_as_shipping' => ['nullable', 'boolean'],
            'billing_address_id' => ['nullable', 'integer', 'exists:customer_addresses,id'],
            'billing_address' => ['nullable', 'array'],
            'billing_address.label' => ['nullable', 'string', 'max:80'],
            'billing_address.recipient_name' => ['required_with:billing_address', 'string', 'max:255'],
            'billing_address.phone' => ['required_with:billing_address', 'string', 'max:30'],
            'billing_address.address_line_1' => ['required_with:billing_address', 'string', 'max:255'],
            'billing_address.address_line_2' => ['nullable', 'string', 'max:255'],
            'billing_address.city' => ['required_with:billing_address', 'string', 'max:120'],
            'billing_address.state' => ['nullable', 'string', 'max:120'],
            'billing_address.postal_code' => ['nullable', 'string', 'max:30'],
            'billing_address.country' => ['nullable', 'string', 'size:2'],

            'shipping_method' => ['nullable', 'string', 'max:80'],
            'payment_method' => ['nullable', 'string', 'max:80'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $shippingAddress = $this->input('shipping_address');
        $billingAddress = $this->input('billing_address');

        if (is_array($shippingAddress)) {
            $shippingAddress['country'] = strtoupper((string) ($shippingAddress['country'] ?? 'BD'));
        }

        if (is_array($billingAddress)) {
            $billingAddress['country'] = strtoupper((string) ($billingAddress['country'] ?? 'BD'));
        }

        $this->merge([
            'billing_same_as_shipping' => filter_var($this->input('billing_same_as_shipping', true), FILTER_VALIDATE_BOOL),
            'shipping_address' => $shippingAddress,
            'billing_address' => $billingAddress,
        ]);
    }
}
