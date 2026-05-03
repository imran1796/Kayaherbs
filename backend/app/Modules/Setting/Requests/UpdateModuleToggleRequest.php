<?php

namespace App\Modules\Setting\Requests;

use App\Modules\Setting\Services\ModuleToggleService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateModuleToggleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [];

        foreach (ModuleToggleService::MODULES as $module) {
            $rules[$module] = ['sometimes', 'boolean'];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (ModuleToggleService::MODULES as $module) {
            $normalized[$module] = $this->boolean($module);
        }

        $this->merge($normalized);
    }
}
