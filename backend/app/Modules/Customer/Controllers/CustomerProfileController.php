<?php

namespace App\Modules\Customer\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Customer\Requests\UpdateCustomerProfileRequest;
use App\Modules\Customer\Resources\CustomerProfileResource;
use Illuminate\Http\Request;

class CustomerProfileController extends Controller
{
    public function show(Request $request)
    {
        return ApiResponse::success(
            new CustomerProfileResource($request->user()),
            'Customer profile fetched successfully.'
        );
    }

    public function update(UpdateCustomerProfileRequest $request)
    {
        $customer = $request->user();
        $customer->update($request->validated());

        return ApiResponse::success(
            new CustomerProfileResource($customer->refresh()),
            'Customer profile updated successfully.'
        );
    }
}
