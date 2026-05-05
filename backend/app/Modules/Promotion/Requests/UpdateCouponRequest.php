<?php

namespace App\Modules\Promotion\Requests;

use Illuminate\Validation\Rule;

class UpdateCouponRequest extends StoreCouponRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['code'] = ['required', 'string', 'max:80', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('coupons', 'code')->ignore($this->route('id'))];

        return $rules;
    }
}
