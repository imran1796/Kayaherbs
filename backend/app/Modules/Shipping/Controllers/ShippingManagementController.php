<?php

namespace App\Modules\Shipping\Controllers;

use App\Http\Controllers\Controller;

class ShippingManagementController extends Controller
{
    public function index()
    {
        return view('shipping::index');
    }
}
