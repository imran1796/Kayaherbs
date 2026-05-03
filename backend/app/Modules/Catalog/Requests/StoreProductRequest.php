<?php

namespace App\Modules\Catalog\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'in:draft,unpublished'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'primary_image' => ['nullable', 'image', 'max:4096'],
            'image_alt_text' => ['nullable', 'string', 'max:180'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.name' => ['required', 'string', 'max:160'],
            'variants.*.sku' => ['required', 'string', 'max:120'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'variants.*.is_default' => ['nullable', 'boolean'],
            'variants.*.status' => ['nullable', 'in:active,inactive'],
            'images' => ['nullable', 'array'],
            'images.*.path' => ['required_with:images', 'string', 'max:255'],
            'images.*.alt_text' => ['nullable', 'string', 'max:180'],
            'images.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'images.*.is_primary' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $slug = (string) ($this->input('slug') ?: $this->input('name'));

        $this->merge([
            'slug' => Str::slug($slug) ?: 'product',
            'status' => $this->input('status', 'draft'),
            'variants' => $this->normalizedVariants(),
            'images' => $this->normalizedImages(),
        ]);
    }

    private function normalizedVariants(): array
    {
        $variants = collect($this->input('variants', []))
            ->map(function (array $variant, int $index): array {
                $variant['sku'] = str($variant['sku'] ?? '')->trim()->upper()->toString();
                $variant['sort_order'] = (int) ($variant['sort_order'] ?? $index);
                $variant['is_default'] = filter_var($variant['is_default'] ?? false, FILTER_VALIDATE_BOOL);
                $variant['status'] = $variant['status'] ?? 'active';

                return $variant;
            })
            ->all();

        $defaultIndex = collect($variants)->search(fn (array $variant): bool => (bool) $variant['is_default']);
        $defaultIndex = $defaultIndex === false ? 0 : $defaultIndex;

        return collect($variants)
            ->map(function (array $variant, int $index) use ($defaultIndex): array {
                $variant['is_default'] = $index === $defaultIndex;

                return $variant;
            })
            ->all();
    }

    private function normalizedImages(): array
    {
        return collect($this->input('images', []))
            ->filter(fn (array $image): bool => filled($image['path'] ?? null))
            ->map(function (array $image, int $index): array {
                $image['sort_order'] = (int) ($image['sort_order'] ?? $index);
                $image['is_primary'] = filter_var($image['is_primary'] ?? $index === 0, FILTER_VALIDATE_BOOL);

                return $image;
            })
            ->values()
            ->all();
    }
}
