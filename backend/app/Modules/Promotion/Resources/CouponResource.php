<?php

namespace App\Modules\Promotion\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'minimum_order_value' => $this->minimum_order_value,
            'status' => $this->status,
            'lifecycle_status' => $this->lifecycle_status,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'eligible_product_ids' => $this->eligible_product_ids,
            'eligible_category_ids' => $this->eligible_category_ids,
            'usage_limit' => $this->usage_limit,
            'per_customer_usage_limit' => $this->per_customer_usage_limit,
            'used_count' => $this->used_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
