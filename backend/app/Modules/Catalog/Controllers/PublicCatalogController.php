<?php

namespace App\Modules\Catalog\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Catalog\Repositories\ProductRepository;
use App\Modules\Catalog\Resources\ProductResource;
use Illuminate\Http\Request;

class PublicCatalogController extends Controller
{
    public function __construct(
        private readonly ProductRepository $products
    ) {}

    public function index(Request $request)
    {
        $products = $this->products->paginateVisibleForStorefront((int) $request->integer('per_page', 15));

        return ApiResponse::success(
            ProductResource::collection($products),
            'Visible products fetched successfully.',
            200,
            [
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                ],
            ]
        );
    }

    public function show(string $slug)
    {
        return ApiResponse::success(
            new ProductResource($this->products->findVisibleBySlugOrFail($slug)),
            'Visible product fetched successfully.'
        );
    }
}
