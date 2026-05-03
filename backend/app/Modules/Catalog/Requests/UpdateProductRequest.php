<?php

namespace App\Modules\Catalog\Requests;

class UpdateProductRequest extends StoreProductRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['status'] = ['nullable', 'in:draft,unpublished,published'];

        return $rules;
    }
}
