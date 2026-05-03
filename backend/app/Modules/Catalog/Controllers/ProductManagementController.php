<?php

namespace App\Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Services\CategoryService;

class ProductManagementController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    public function index()
    {
        return view('catalog::products.manage', [
            'categories' => $this->categoryService->rootCategories(),
        ]);
    }
}
