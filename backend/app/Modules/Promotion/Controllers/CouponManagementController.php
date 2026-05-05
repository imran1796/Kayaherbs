<?php

namespace App\Modules\Promotion\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Services\CategoryService;
use App\Modules\Catalog\Services\ProductService;
use App\Modules\Promotion\Services\CouponAuditService;
use App\Modules\Reporting\Services\ReportingService;

class CouponManagementController extends Controller
{
    public function __construct(
        private readonly ProductService $products,
        private readonly CategoryService $categories,
        private readonly ReportingService $reports,
        private readonly CouponAuditService $audits
    ) {}

    public function index()
    {
        return view('promotion::coupons.index', [
            'products' => $this->products->paginate(100)->items(),
            'categories' => $this->categories->paginate(100)->items(),
            'couponReport' => $this->reports->couponReport(),
            'couponAudits' => $this->audits->recent(),
        ]);
    }
}
