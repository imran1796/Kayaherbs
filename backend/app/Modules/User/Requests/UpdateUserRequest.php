<?php

namespace App\Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:8'],
            'status' => ['nullable', 'in:active,inactive'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::in($this->assignableRoleNames())],
        ];
    }

    /**
     * @return list<string>
     */
    private function assignableRoleNames(): array
    {
        $roles = array_keys(config('rbac.roles', []));

        if (! $this->user()?->hasRole('super_admin')) {
            $roles = array_values(array_diff($roles, ['super_admin']));
        }

        return $roles;
    }
}
