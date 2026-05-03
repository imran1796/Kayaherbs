<?php

namespace App\Modules\Cart\Requests;

use App\Modules\Cart\Services\CartService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGuestCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:'.CartService::MAX_LINE_QUANTITY],
        ];
    }
}
