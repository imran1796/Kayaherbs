<?php

namespace App\Modules\Reporting\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardKpiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_orders' => $this['total_orders'],
            'pending_orders' => $this['pending_orders'],
            'cancelled_orders' => $this['cancelled_orders'],
            'delivered_orders' => $this['delivered_orders'],
            'gross_sales' => $this['gross_sales'],
            'paid_sales' => $this['paid_sales'],
            'average_order_value' => $this['average_order_value'],
        ];
    }
}
