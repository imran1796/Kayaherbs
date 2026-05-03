<?php

namespace App\Modules\Cart\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Cart\Requests\StoreGuestCartItemRequest;
use App\Modules\Cart\Requests\UpdateGuestCartItemRequest;
use App\Modules\Cart\Resources\CartResource;
use App\Modules\Cart\Services\CartService;
use Illuminate\Http\Request;

class CustomerCartController extends Controller
{
    public function __construct(
        protected CartService $carts
    ) {}

    public function show(Request $request)
    {
        return ApiResponse::success(
            new CartResource($this->carts->getCustomerCart($request->user('sanctum'))),
            'Customer cart loaded successfully.'
        );
    }

    public function addItem(StoreGuestCartItemRequest $request)
    {
        $cart = $this->carts->addCustomerItem(
            $request->user('sanctum'),
            (int) $request->validated('product_variant_id'),
            (int) $request->validated('quantity')
        );

        return ApiResponse::success(new CartResource($cart), 'Cart item added successfully.');
    }

    public function updateItem(UpdateGuestCartItemRequest $request, int $itemId)
    {
        $cart = $this->carts->updateCustomerItem(
            $request->user('sanctum'),
            $itemId,
            (int) $request->validated('quantity')
        );

        return ApiResponse::success(new CartResource($cart), 'Cart item updated successfully.');
    }

    public function removeItem(Request $request, int $itemId)
    {
        return ApiResponse::success(
            new CartResource($this->carts->removeCustomerItem($request->user('sanctum'), $itemId)),
            'Cart item removed successfully.'
        );
    }

    public function clear(Request $request)
    {
        return ApiResponse::success(
            new CartResource($this->carts->clearCustomerCart($request->user('sanctum'))),
            'Customer cart cleared successfully.'
        );
    }
}
