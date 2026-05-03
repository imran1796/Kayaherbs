<?php

namespace App\Modules\Reporting\Controllers;

use App\Http\Controllers\Controller;

class ReportManagementController extends Controller
{
    public function sales()
    {
        return view('reporting::reports.sales');
    }

    public function orders()
    {
        return view('reporting::reports.orders');
    }

    public function inventory()
    {
        return view('reporting::reports.inventory');
    }

    public function customers()
    {
        return view('reporting::reports.customers');
    }
}
