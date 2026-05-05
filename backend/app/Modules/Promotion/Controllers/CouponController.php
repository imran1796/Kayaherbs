<?php

namespace App\Modules\Promotion\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Promotion\Requests\StoreCouponRequest;
use App\Modules\Promotion\Requests\UpdateCouponRequest;
use App\Modules\Promotion\Resources\CouponResource;
use App\Modules\Promotion\Services\CouponService;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function __construct(
        private readonly CouponService $coupons
    ) {}

    public function index(Request $request)
    {
        $coupons = $this->coupons->paginate((int) $request->integer('per_page', 15));

        return ApiResponse::success(
            CouponResource::collection($coupons),
            'Coupons fetched successfully.',
            200,
            [
                'pagination' => [
                    'current_page' => $coupons->currentPage(),
                    'last_page' => $coupons->lastPage(),
                    'per_page' => $coupons->perPage(),
                    'total' => $coupons->total(),
                ],
            ]
        );
    }

    public function store(StoreCouponRequest $request)
    {
        return ApiResponse::success(
            new CouponResource($this->coupons->create($request->validated(), $request->user('sanctum') ?? $request->user())),
            'Coupon created successfully.',
            201
        );
    }

    public function show(int $id)
    {
        return ApiResponse::success(
            new CouponResource($this->coupons->findOrFail($id)),
            'Coupon fetched successfully.'
        );
    }

    public function update(UpdateCouponRequest $request, int $id)
    {
        return ApiResponse::success(
            new CouponResource($this->coupons->update($id, $request->validated(), $request->user('sanctum') ?? $request->user())),
            'Coupon updated successfully.'
        );
    }

    public function activate(Request $request, int $id)
    {
        return ApiResponse::success(
            new CouponResource($this->coupons->activate($id, $request->user('sanctum') ?? $request->user())),
            'Coupon activated successfully.'
        );
    }

    public function deactivate(Request $request, int $id)
    {
        return ApiResponse::success(
            new CouponResource($this->coupons->deactivate($id, $request->user('sanctum') ?? $request->user())),
            'Coupon deactivated successfully.'
        );
    }

    public function destroy(Request $request, int $id)
    {
        $this->coupons->delete($id, $request->user('sanctum') ?? $request->user());

        return ApiResponse::success(null, 'Coupon deleted successfully.');
    }
}
