<?php

namespace App\Modules\Customer\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncCustomerTagsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tags' => ['present', 'array', 'max:20'],
            'tags.*' => ['required', 'string', 'max:80', 'distinct'],
        ];
    }
}
