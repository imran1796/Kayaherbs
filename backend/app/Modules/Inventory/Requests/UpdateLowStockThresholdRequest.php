<?php

namespace App\Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLowStockThresholdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
