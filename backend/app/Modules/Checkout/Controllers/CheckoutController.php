<?php

namespace App\Modules\Checkout\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
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
}
