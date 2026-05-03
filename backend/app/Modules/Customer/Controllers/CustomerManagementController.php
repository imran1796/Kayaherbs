<?php

namespace App\Modules\Customer\Controllers;

use App\Http\Controllers\Controller;

class CustomerManagementController extends Controller
{
    public function index()
    {
        return view('customer::customers.index');
    }

    public function show(int $id)
    {
        return view('customer::customers.show', [
            'customerId' => $id,
        ]);
    }
}
