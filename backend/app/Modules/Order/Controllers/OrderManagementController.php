<?php

namespace App\Modules\Order\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Order\Services\OrderLifecycleService;

class OrderManagementController extends Controller
{
    public function __construct(
        private readonly OrderLifecycleService $orders
    ) {}

    public function index()
    {
        return view('order::orders.index');
    }

    public function show(int $id)
    {
        return view('order::orders.show', [
            'orderId' => $id,
        ]);
    }

    public function invoice(int $id)
    {
        return view('order::documents.invoice', [
            'order' => $this->orders->findInvoicePrintDataOrFail($id),
        ]);
    }

    public function packingSlip(int $id)
    {
        return view('order::documents.packing-slip', [
            'order' => $this->orders->findPackingSlipPrintDataOrFail($id),
        ]);
    }
}
