<?php

namespace App\Modules\Setting\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'legal_name' => ['nullable', 'string', 'max:160'],
            'support_email' => ['required', 'email', 'max:160'],
            'support_phone' => ['nullable', 'string', 'max:40'],
            'address_line_1' => ['nullable', 'string', 'max:180'],
            'address_line_2' => ['nullable', 'string', 'max:180'],
            'city' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:80'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'country' => ['required', 'string', 'size:2'],
            'currency' => ['required', 'string', 'size:3'],
            'timezone' => ['required', 'timezone', 'max:80'],
            'locale' => ['required', 'string', 'max:12'],
            'website_url' => ['nullable', 'url', 'max:180'],
            'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo_path' => ['nullable', 'string', 'max:255'],
            'logo_dark_path' => ['nullable', 'string', 'max:255'],
            'favicon_path' => ['nullable', 'string', 'max:255'],
            'social_share_image_path' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'file', 'image', 'max:2048'],
            'logo_dark' => ['nullable', 'file', 'image', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:ico,png,svg', 'max:1024'],
            'social_share_image' => ['nullable', 'file', 'image', 'max:4096'],
            'seo_title_template' => ['nullable', 'string', 'max:180'],
            'seo_meta_description' => ['nullable', 'string', 'max:320'],
            'seo_meta_keywords' => ['nullable', 'string', 'max:400'],
            'seo_robots' => ['nullable', 'regex:/^(index|noindex),(follow|nofollow)$/'],
            'seo_canonical_base_url' => ['nullable', 'url', 'max:180'],
            'seo_og_image_path' => ['nullable', 'string', 'max:255'],
            'seo_og_image' => ['nullable', 'file', 'image', 'max:4096'],
            'privacy_policy_title' => ['nullable', 'string', 'max:180'],
            'privacy_policy_content' => ['nullable', 'string'],
            'terms_conditions_title' => ['nullable', 'string', 'max:180'],
            'terms_conditions_content' => ['nullable', 'string'],
            'refund_policy_title' => ['nullable', 'string', 'max:180'],
            'refund_policy_content' => ['nullable', 'string'],
            'shipping_policy_title' => ['nullable', 'string', 'max:180'],
            'shipping_policy_content' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'country' => strtoupper((string) $this->input('country')),
            'currency' => strtoupper((string) $this->input('currency')),
            'primary_color' => strtoupper((string) $this->input('primary_color')),
            'secondary_color' => strtoupper((string) $this->input('secondary_color')),
        ]);
    }
}
