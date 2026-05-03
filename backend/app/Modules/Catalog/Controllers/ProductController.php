<?php

namespace App\Modules\Catalog\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Catalog\Requests\StoreProductRequest;
use App\Modules\Catalog\Requests\UpdateProductRequest;
use App\Modules\Catalog\Resources\ProductResource;
use App\Modules\Catalog\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    public function index(Request $request)
    {
        $products = $this->productService->paginate(
            (int) $request->integer('per_page', 15),
            $request->only(['search', 'status', 'category_id'])
        );

        return ApiResponse::success(
            ProductResource::collection($products),
            'Products fetched successfully.',
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

    public function store(StoreProductRequest $request)
    {
        return ApiResponse::success(
            new ProductResource($this->productService->create($this->payloadWithUploadedImage($request))),
            'Product created successfully.',
            201
        );
    }

    public function show(int $id)
    {
        return ApiResponse::success(
            new ProductResource($this->productService->findOrFail($id)),
            'Product fetched successfully.'
        );
    }

    public function update(UpdateProductRequest $request, int $id)
    {
        return ApiResponse::success(
            new ProductResource($this->productService->update($id, $this->payloadWithUploadedImage($request))),
            'Product updated successfully.'
        );
    }

    public function publish(int $id)
    {
        return ApiResponse::success(
            new ProductResource($this->productService->publish($id)),
            'Product published successfully.'
        );
    }

    public function unpublish(int $id)
    {
        return ApiResponse::success(
            new ProductResource($this->productService->unpublish($id)),
            'Product unpublished successfully.'
        );
    }

    public function destroy(int $id)
    {
        $this->productService->delete($id);

        return ApiResponse::success(null, 'Product deleted successfully.');
    }

    private function payloadWithUploadedImage(Request $request): array
    {
        $payload = $request->validated();

        if (! $request->hasFile('primary_image')) {
            return $payload;
        }

        $image = $request->file('primary_image');

        if (! $image instanceof UploadedFile) {
            return $payload;
        }

        $storedPath = $image->store('products', 'public');

        $payload['images'] = [[
            'path' => '/storage/'.$storedPath,
            'alt_text' => $request->string('image_alt_text')->toString() ?: $request->string('name')->toString(),
            'sort_order' => 0,
            'is_primary' => true,
        ]];

        return $payload;
    }
}
