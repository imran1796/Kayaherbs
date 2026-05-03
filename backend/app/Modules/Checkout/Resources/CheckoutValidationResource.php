<?php

namespace App\Modules\Checkout\Resources;

use App\Modules\Cart\Resources\CartResource;
use App\Modules\Customer\Resources\CustomerAddressResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckoutValidationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'checkout_ready' => $this->resource['checkout_ready'],
            'steps' => $this->resource['steps'],
            'cart' => new CartResource($this->resource['cart']),
            'shipping_address' => new CustomerAddressResource($this->resource['shipping_address']),
            'billing_address' => new CustomerAddressResource($this->resource['billing_address']),
            'shipping_method' => $this->resource['shipping_method'],
            'payment_method' => $this->resource['payment_method'],
            'totals' => $this->resource['totals'],
        ];
    }
}
