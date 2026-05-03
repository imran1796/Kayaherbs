<?php

namespace App\Modules\Customer\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Customer\Requests\StoreCustomerAddressRequest;
use App\Modules\Customer\Requests\UpdateCustomerAddressRequest;
use App\Modules\Customer\Resources\CustomerAddressResource;
use App\Modules\Customer\Services\CustomerAddressService;
use Illuminate\Http\Request;

class CustomerAddressController extends Controller
{
    public function __construct(
        protected CustomerAddressService $addresses
    ) {}

    public function index(Request $request)
    {
        return ApiResponse::success(
            CustomerAddressResource::collection($this->addresses->list($request->user())),
            'Customer addresses fetched successfully.'
        );
    }

    public function store(StoreCustomerAddressRequest $request)
    {
        $address = $this->addresses->create($request->user(), $request->validated());

        return ApiResponse::success(
            new CustomerAddressResource($address),
            'Customer address created successfully.',
            201
        );
    }

    public function show(Request $request, int $id)
    {
        return ApiResponse::success(
            new CustomerAddressResource($this->addresses->findOrFail($request->user(), $id)),
            'Customer address fetched successfully.'
        );
    }

    public function update(UpdateCustomerAddressRequest $request, int $id)
    {
        $address = $this->addresses->update(
            $this->addresses->findOrFail($request->user(), $id),
            $request->validated()
        );

        return ApiResponse::success(
            new CustomerAddressResource($address),
            'Customer address updated successfully.'
        );
    }

    public function destroy(Request $request, int $id)
    {
        $this->addresses->delete($this->addresses->findOrFail($request->user(), $id));

        return ApiResponse::success(null, 'Customer address deleted successfully.');
    }

    public function adminStore(StoreCustomerAddressRequest $request, int $customerId)
    {
        return ApiResponse::success(
            new CustomerAddressResource($this->addresses->createForCustomer($customerId, $request->validated())),
            'Customer address created successfully.',
            201
        );
    }

    public function adminUpdate(UpdateCustomerAddressRequest $request, int $customerId, int $addressId)
    {
        $address = $this->addresses->update(
            $this->addresses->findForCustomerOrFail($customerId, $addressId),
            $request->validated()
        );

        return ApiResponse::success(
            new CustomerAddressResource($address),
            'Customer address updated successfully.'
        );
    }

    public function adminDestroy(int $customerId, int $addressId)
    {
        $this->addresses->delete($this->addresses->findForCustomerOrFail($customerId, $addressId));

        return ApiResponse::success(null, 'Customer address deleted successfully.');
    }
}
