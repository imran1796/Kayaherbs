<?php

namespace App\Modules\Customer\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Checkout\Resources\OrderResource;
use App\Modules\Customer\Services\CustomerOrderService;
use Illuminate\Http\Request;

class CustomerOrderController extends Controller
{
    public function __construct(
        private readonly CustomerOrderService $orders
    ) {}

    public function index(Request $request)
    {
        $orders = $this->orders->paginate($request->user(), (int) $request->integer('per_page', 15));

        return ApiResponse::success(
            OrderResource::collection($orders),
            'Customer orders fetched successfully.',
            200,
            [
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                ],
            ]
        );
    }

    public function show(Request $request, int $id)
    {
        return ApiResponse::success(
            new OrderResource($this->orders->findOrFail($request->user(), $id)),
            'Customer order fetched successfully.'
        );
    }
}
