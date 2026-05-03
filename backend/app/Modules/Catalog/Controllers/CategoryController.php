<?php

namespace App\Modules\Catalog\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Catalog\Requests\StoreCategoryRequest;
use App\Modules\Catalog\Requests\UpdateCategoryRequest;
use App\Modules\Catalog\Resources\CategoryResource;
use App\Modules\Catalog\Services\CategoryService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    public function index(Request $request)
    {
        $categories = $this->categoryService->paginate((int) $request->integer('per_page', 15));

        return ApiResponse::success(
            CategoryResource::collection($categories),
            'Categories fetched successfully.',
            200,
            [
                'pagination' => [
                    'current_page' => $categories->currentPage(),
                    'last_page' => $categories->lastPage(),
                    'per_page' => $categories->perPage(),
                    'total' => $categories->total(),
                ],
            ]
        );
    }

    public function store(StoreCategoryRequest $request)
    {
        return ApiResponse::success(
            new CategoryResource($this->categoryService->create($request->validated())),
            'Category created successfully.',
            201
        );
    }

    public function show(int $id)
    {
        return ApiResponse::success(
            new CategoryResource($this->categoryService->findOrFail($id)),
            'Category fetched successfully.'
        );
    }

    public function update(UpdateCategoryRequest $request, int $id)
    {
        return ApiResponse::success(
            new CategoryResource($this->categoryService->update($id, $request->validated())),
            'Category updated successfully.'
        );
    }

    public function destroy(int $id)
    {
        $this->categoryService->delete($id);

        return ApiResponse::success(null, 'Category deleted successfully.');
    }
}
