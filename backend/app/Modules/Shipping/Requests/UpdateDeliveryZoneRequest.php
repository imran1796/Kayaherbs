<?php

namespace App\Modules\Shipping\Requests;

use Illuminate\Validation\Rule;

class UpdateDeliveryZoneRequest extends StoreDeliveryZoneRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'code' => [
                'nullable',
                'string',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('delivery_zones', 'code')->ignore($this->route('id')),
            ],
        ];
    }
}
