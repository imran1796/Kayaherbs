<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;

class InventoryManagementController extends Controller
{
    public function index()
    {
        return view('inventory::stocks.index');
    }

    public function history()
    {
        return view('inventory::stocks.history');
    }
}
