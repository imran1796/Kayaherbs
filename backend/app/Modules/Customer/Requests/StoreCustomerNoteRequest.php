<?php

namespace App\Modules\Customer\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'max:5000'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
