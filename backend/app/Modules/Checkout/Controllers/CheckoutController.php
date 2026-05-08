<?php

namespace App\Modules\Checkout\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\DeliveryRate;
use App\Modules\Checkout\Requests\GuestCheckoutSubmitRequest;
use App\Modules\Checkout\Requests\CheckoutSubmitRequest;
use App\Modules\Checkout\Requests\CheckoutValidationRequest;
use App\Modules\Checkout\Resources\CheckoutValidationResource;
use App\Modules\Checkout\Resources\OrderResource;
use App\Modules\Checkout\Services\CheckoutService;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkout
    ) {}

    public function shippingMethods()
    {
        $rates = DeliveryRate::query()
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('amount')
            ->get();

        if ($rates->isNotEmpty()) {
            return ApiResponse::success(
                $rates->map(fn (DeliveryRate $rate): array => [
                    'code' => $rate->code,
                    'name' => $rate->name,
                    'amount' => number_format((float) $rate->amount, 2, '.', ''),
                ])->values(),
                'Shipping methods fetched successfully.'
            );
        }

        $methods = collect(config('checkout.shipping.methods', []))
            ->filter(fn (array $method): bool => (bool) ($method['active'] ?? false))
            ->map(fn (array $method, string $code): array => [
                'code' => $code,
                'name' => $method['name'],
                'amount' => number_format((float) $method['amount'], 2, '.', ''),
            ])
            ->values();

        return ApiResponse::success($methods, 'Shipping methods fetched successfully.');
    }

    public function validate(CheckoutValidationRequest $request)
    {
        $result = $this->checkout->validateCheckout(
            $request->user('sanctum'),
            $request->validated()
        );

        return ApiResponse::success(
            new CheckoutValidationResource($result),
            'Checkout validation completed successfully.'
        );
    }

    public function submit(CheckoutSubmitRequest $request)
    {
        $order = $this->checkout->submitCheckout(
            $request->user('sanctum'),
            $request->validated()
        );

        return ApiResponse::success(
            new OrderResource($order),
            'Order created successfully.',
            $order->wasRecentlyCreated ? 201 : 200
        );
    }

    public function submitGuest(GuestCheckoutSubmitRequest $request)
    {
        $order = $this->checkout->submitGuestCheckout($request->validated());

        return ApiResponse::success(
            new OrderResource($order),
            'Order created successfully.',
            $order->wasRecentlyCreated ? 201 : 200
        );
    }
}
