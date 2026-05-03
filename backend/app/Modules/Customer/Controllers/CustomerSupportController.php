<?php

namespace App\Modules\Customer\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Customer\Requests\StoreCustomerNoteRequest;
use App\Modules\Customer\Requests\SyncCustomerTagsRequest;
use App\Modules\Customer\Requests\UpdateCustomerStatusRequest;
use App\Modules\Customer\Resources\CustomerNoteResource;
use App\Modules\Customer\Resources\CustomerSupportResource;
use App\Modules\Customer\Resources\CustomerTagResource;
use App\Modules\Customer\Services\CustomerSupportService;
use Illuminate\Http\Request;

class CustomerSupportController extends Controller
{
    public function __construct(
        private readonly CustomerSupportService $customers
    ) {}

    public function index(Request $request)
    {
        $customers = $this->customers->paginate(
            (int) $request->integer('per_page', 15),
            $request->only(['search', 'status'])
        );

        return ApiResponse::success(
            CustomerSupportResource::collection($customers),
            'Customers fetched successfully.',
            200,
            [
                'pagination' => [
                    'current_page' => $customers->currentPage(),
                    'last_page' => $customers->lastPage(),
                    'per_page' => $customers->perPage(),
                    'total' => $customers->total(),
                ],
            ]
        );
    }

    public function show(int $id)
    {
        return ApiResponse::success(
            new CustomerSupportResource($this->customers->findForSupport($id)),
            'Customer fetched successfully.'
        );
    }

    public function updateStatus(UpdateCustomerStatusRequest $request, int $id)
    {
        return ApiResponse::success(
            new CustomerSupportResource($this->customers->updateStatus(
                $id,
                (string) $request->validated('status'),
                $request->user('sanctum') ?? $request->user()
            )),
            'Customer status updated successfully.'
        );
    }

    public function storeNote(StoreCustomerNoteRequest $request, int $id)
    {
        return ApiResponse::success(
            new CustomerNoteResource($this->customers->addNote(
                $id,
                (string) $request->validated('note'),
                $request->user('sanctum') ?? $request->user(),
                $request->validated('metadata', [])
            )),
            'Customer note added successfully.',
            201
        );
    }

    public function syncTags(SyncCustomerTagsRequest $request, int $id)
    {
        return ApiResponse::success(
            CustomerTagResource::collection($this->customers->syncTags(
                $id,
                $request->validated('tags'),
                $request->user('sanctum') ?? $request->user()
            )),
            'Customer tags updated successfully.'
        );
    }
}
