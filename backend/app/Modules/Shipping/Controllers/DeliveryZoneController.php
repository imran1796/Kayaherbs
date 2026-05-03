<?php

namespace App\Modules\Shipping\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Shipping\Requests\StoreDeliveryZoneRequest;
use App\Modules\Shipping\Requests\UpdateDeliveryZoneRequest;
use App\Modules\Shipping\Resources\DeliveryZoneResource;
use App\Modules\Shipping\Services\DeliveryZoneService;
use Illuminate\Http\Request;

class DeliveryZoneController extends Controller
{
    public function __construct(
        private readonly DeliveryZoneService $zones
    ) {}

    public function index(Request $request)
    {
        $zones = $this->zones->paginate((int) $request->integer('per_page', 15));

        return ApiResponse::success(
            DeliveryZoneResource::collection($zones),
            'Delivery zones fetched successfully.',
            200,
            [
                'pagination' => [
                    'current_page' => $zones->currentPage(),
                    'last_page' => $zones->lastPage(),
                    'per_page' => $zones->perPage(),
                    'total' => $zones->total(),
                ],
            ]
        );
    }

    public function store(StoreDeliveryZoneRequest $request)
    {
        return ApiResponse::success(
            new DeliveryZoneResource($this->zones->create($request->validated())),
            'Delivery zone created successfully.',
            201
        );
    }

    public function show(int $id)
    {
        return ApiResponse::success(
            new DeliveryZoneResource($this->zones->findOrFail($id)),
            'Delivery zone fetched successfully.'
        );
    }

    public function update(UpdateDeliveryZoneRequest $request, int $id)
    {
        return ApiResponse::success(
            new DeliveryZoneResource($this->zones->update($id, $request->validated())),
            'Delivery zone updated successfully.'
        );
    }

    public function destroy(int $id)
    {
        $this->zones->delete($id);

        return ApiResponse::success(null, 'Delivery zone deleted successfully.');
    }
}
