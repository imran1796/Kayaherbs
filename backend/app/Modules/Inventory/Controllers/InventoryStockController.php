<?php

namespace App\Modules\Inventory\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Inventory\Requests\AdjustStockRequest;
use App\Modules\Inventory\Requests\ReleaseStockRequest;
use App\Modules\Inventory\Requests\ReserveStockRequest;
use App\Modules\Inventory\Resources\InventoryStockResource;
use App\Modules\Inventory\Resources\InventoryTransactionResource;
use App\Modules\Inventory\Services\InventoryStockService;
use Illuminate\Http\Request;

class InventoryStockController extends Controller
{
    public function __construct(
        protected InventoryStockService $inventory
    ) {}

    public function index(Request $request)
    {
        $stocks = $this->inventory->paginate((int) $request->integer('per_page', 15));

        return ApiResponse::success(
            InventoryStockResource::collection($stocks),
            'Inventory stocks fetched successfully.',
            200,
            [
                'pagination' => [
                    'current_page' => $stocks->currentPage(),
                    'last_page' => $stocks->lastPage(),
                    'per_page' => $stocks->perPage(),
                    'total' => $stocks->total(),
                ],
            ]
        );
    }

    public function history(Request $request)
    {
        $transactions = $this->inventory->paginateHistory((int) $request->integer('per_page', 15));

        return ApiResponse::success(
            InventoryTransactionResource::collection($transactions),
            'Inventory history fetched successfully.',
            200,
            [
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                ],
            ]
        );
    }

    public function lowStock(Request $request)
    {
        $stocks = $this->inventory->paginateLowStock((int) $request->integer('per_page', 10));

        return ApiResponse::success(
            InventoryStockResource::collection($stocks),
            'Low-stock inventory fetched successfully.',
            200,
            [
                'pagination' => [
                    'current_page' => $stocks->currentPage(),
                    'last_page' => $stocks->lastPage(),
                    'per_page' => $stocks->perPage(),
                    'total' => $stocks->total(),
                ],
            ]
        );
    }

    public function adjust(AdjustStockRequest $request, int $variantId)
    {
        $stock = $this->inventory->adjust(
            $variantId,
            (int) $request->validated('quantity_delta'),
            $request->user(),
            $request->validated('note'),
            $request->validated('metadata', [])
        );

        return ApiResponse::success(
            new InventoryStockResource($stock),
            'Stock adjusted successfully.'
        );
    }

    public function reserve(ReserveStockRequest $request, int $variantId)
    {
        $stock = $this->inventory->reserve(
            $variantId,
            (int) $request->validated('quantity'),
            $request->user(),
            $request->validated('note'),
            $request->validated('metadata', [])
        );

        return ApiResponse::success(
            new InventoryStockResource($stock),
            'Stock reserved successfully.'
        );
    }

    public function release(ReleaseStockRequest $request, int $variantId)
    {
        $stock = $this->inventory->release(
            $variantId,
            (int) $request->validated('quantity'),
            $request->user(),
            $request->validated('note'),
            $request->validated('metadata', [])
        );

        return ApiResponse::success(
            new InventoryStockResource($stock),
            'Stock released successfully.'
        );
    }
}
