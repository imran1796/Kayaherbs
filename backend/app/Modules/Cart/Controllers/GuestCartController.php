<?php

namespace App\Modules\Cart\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Cart\Requests\StoreGuestCartItemRequest;
use App\Modules\Cart\Requests\UpdateGuestCartItemRequest;
use App\Modules\Cart\Resources\CartResource;
use App\Modules\Cart\Services\CartService;

class GuestCartController extends Controller
{
    public function __construct(
        protected CartService $carts
    ) {}

    public function store()
    {
        return ApiResponse::success(
            new CartResource($this->carts->createGuestCart()),
            'Guest cart created successfully.',
            201
        );
    }

    public function show(string $cartToken)
    {
        return ApiResponse::success(
            new CartResource($this->carts->getGuestCart($cartToken)),
            'Guest cart loaded successfully.'
        );
    }

    public function addItem(StoreGuestCartItemRequest $request, string $cartToken)
    {
        $cart = $this->carts->addGuestItem(
            $cartToken,
            (int) $request->validated('product_variant_id'),
            (int) $request->validated('quantity')
        );

        return ApiResponse::success(new CartResource($cart), 'Cart item added successfully.');
    }

    public function updateItem(UpdateGuestCartItemRequest $request, string $cartToken, int $itemId)
    {
        $cart = $this->carts->updateGuestItem(
            $cartToken,
            $itemId,
            (int) $request->validated('quantity')
        );

        return ApiResponse::success(new CartResource($cart), 'Cart item updated successfully.');
    }

    public function removeItem(string $cartToken, int $itemId)
    {
        return ApiResponse::success(
            new CartResource($this->carts->removeGuestItem($cartToken, $itemId)),
            'Cart item removed successfully.'
        );
    }

    public function clear(string $cartToken)
    {
        return ApiResponse::success(
            new CartResource($this->carts->clearGuestCart($cartToken)),
            'Guest cart cleared successfully.'
        );
    }
}
