<?php

namespace App\Modules\Cart\Requests;

use App\Modules\Cart\Services\CartService;
use Illuminate\Foundation\Http\FormRequest;

class StoreGuestCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.CartService::MAX_LINE_QUANTITY],
        ];
    }
}
