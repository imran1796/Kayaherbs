<?php

namespace App\Modules\Checkout\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuestCheckoutSubmitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cart_token' => ['required', 'string', 'max:128'],
            'customer' => ['required', 'array'],
            'customer.name' => ['required', 'string', 'max:255'],
            'customer.phone' => ['required', 'string', 'max:30'],
            'customer.email' => ['nullable', 'email', 'max:255'],

            'shipping_address' => ['required', 'array'],
            'shipping_address.label' => ['nullable', 'string', 'max:80'],
            'shipping_address.recipient_name' => ['required', 'string', 'max:255'],
            'shipping_address.phone' => ['required', 'string', 'max:30'],
            'shipping_address.address_line_1' => ['required', 'string', 'max:255'],
            'shipping_address.address_line_2' => ['nullable', 'string', 'max:255'],
            'shipping_address.city' => ['required', 'string', 'max:120'],
            'shipping_address.state' => ['nullable', 'string', 'max:120'],
            'shipping_address.postal_code' => ['nullable', 'string', 'max:30'],
            'shipping_address.country' => ['nullable', 'string', 'size:2'],

            'billing_same_as_shipping' => ['nullable', 'boolean'],
            'shipping_method' => ['nullable', 'string', 'max:80'],
            'payment_method' => ['nullable', 'string', 'max:80'],
            'idempotency_key' => ['required', 'string', 'max:120'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $customer = $this->input('customer', []);
        $shippingAddress = $this->input('shipping_address', []);

        if (is_array($customer)) {
            $customer['phone'] = trim((string) ($customer['phone'] ?? ''));
            $customer['email'] = trim((string) ($customer['email'] ?? '')) ?: null;
        }

        if (is_array($shippingAddress)) {
            $shippingAddress['country'] = strtoupper((string) ($shippingAddress['country'] ?? 'BD'));
        }

        $this->merge([
            'customer' => $customer,
            'shipping_address' => $shippingAddress,
            'billing_same_as_shipping' => filter_var($this->input('billing_same_as_shipping', true), FILTER_VALIDATE_BOOL),
        ]);
    }
}
