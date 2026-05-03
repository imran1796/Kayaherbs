<?php

namespace App\Modules\Shipping\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Shipping\Requests\StoreDeliveryRateRequest;
use App\Modules\Shipping\Requests\UpdateDeliveryRateRequest;
use App\Modules\Shipping\Resources\DeliveryRateResource;
use App\Modules\Shipping\Services\DeliveryRateService;
use Illuminate\Http\Request;

class DeliveryRateController extends Controller
{
    public function __construct(
        private readonly DeliveryRateService $rates
    ) {}

    public function index(Request $request)
    {
        $rates = $this->rates->paginate((int) $request->integer('per_page', 15));

        return ApiResponse::success(
            DeliveryRateResource::collection($rates),
            'Delivery rates fetched successfully.',
            200,
            [
                'pagination' => [
                    'current_page' => $rates->currentPage(),
                    'last_page' => $rates->lastPage(),
                    'per_page' => $rates->perPage(),
                    'total' => $rates->total(),
                ],
            ]
        );
    }

    public function store(StoreDeliveryRateRequest $request)
    {
        return ApiResponse::success(
            new DeliveryRateResource($this->rates->create($request->validated())),
            'Delivery rate created successfully.',
            201
        );
    }

    public function show(int $id)
    {
        return ApiResponse::success(
            new DeliveryRateResource($this->rates->findOrFail($id)),
            'Delivery rate fetched successfully.'
        );
    }

    public function update(UpdateDeliveryRateRequest $request, int $id)
    {
        return ApiResponse::success(
            new DeliveryRateResource($this->rates->update($id, $request->validated())),
            'Delivery rate updated successfully.'
        );
    }

    public function destroy(int $id)
    {
        $this->rates->delete($id);

        return ApiResponse::success(null, 'Delivery rate deleted successfully.');
    }
}
