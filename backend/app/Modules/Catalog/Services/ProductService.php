<?php

namespace App\Modules\Catalog\Services;

use App\Models\Product;
use App\Modules\Catalog\Repositories\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductService
{
    public function __construct(
        protected ProductRepository $products
    ) {}

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->products->paginateWithRelations($perPage, $filters);
    }

    public function findOrFail(int $id): Product
    {
        return $this->products->findWithRelationsOrFail($id);
    }

    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data): Product {
            $data['slug'] = $this->uniqueSlug($data['slug']);
            $this->ensureSkuUniqueness($data['variants'] ?? []);

            /** @var Product $product */
            $product = $this->products->create($this->productPayload($data));

            $this->syncRelations($product, $data);

            return $this->findOrFail($product->id);
        });
    }

    public function update(int $id, array $data): Product
    {
        return DB::transaction(function () use ($id, $data): Product {
            $product = $this->findOrFail($id);
            $data['slug'] = $this->uniqueSlug($data['slug'], $product->id);
            $this->ensureSkuUniqueness($data['variants'] ?? [], $product->id);

            $this->products->update($product, $this->productPayload($data));
            $this->syncRelations($product, $data);

            return $this->findOrFail($product->id);
        });
    }

    public function publish(int $id): Product
    {
        return DB::transaction(function () use ($id): Product {
            $product = $this->findOrFail($id);

            if ($product->variants->isEmpty()) {
                throw ValidationException::withMessages([
                    'variants' => ['A product must have at least one variant before it can be published.'],
                ]);
            }

            if ($product->images->isEmpty()) {
                throw ValidationException::withMessages([
                    'images' => ['A product must have at least one image before it can be published.'],
                ]);
            }

            $this->products->update($product, [
                'status' => 'published',
                'published_at' => $product->published_at ?? now(),
            ]);

            return $this->findOrFail($product->id);
        });
    }

    public function unpublish(int $id): Product
    {
        return DB::transaction(function () use ($id): Product {
            $product = $this->findOrFail($id);

            $this->products->update($product, [
                'status' => 'unpublished',
            ]);

            return $this->findOrFail($product->id);
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            return $this->products->delete($this->findOrFail($id));
        });
    }

    private function productPayload(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'name', 'slug', 'description', 'status', 'published_at',
        ]));
    }

    private function syncRelations(Product $product, array $data): void
    {
        $this->products->syncCategories($product, $data['category_ids'] ?? []);

        if (array_key_exists('variants', $data)) {
            $this->products->replaceVariants($product, $data['variants']);
        }

        if (array_key_exists('images', $data)) {
            $this->products->replaceImages($product, $data['images']);
        }
    }

    private function uniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $baseSlug = Str::limit($slug, 190, '');
        $candidate = $baseSlug;
        $suffix = 2;

        while ($this->slugExists($candidate, $ignoreId)) {
            $suffixText = '-'.$suffix;
            $candidate = Str::limit($baseSlug, 200 - strlen($suffixText), '').$suffixText;
            $suffix++;
        }

        return $candidate;
    }

    private function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $existingProduct = $this->products->findBySlug($slug);

        return $existingProduct !== null && $existingProduct->id !== $ignoreId;
    }

    private function ensureSkuUniqueness(array $variants, ?int $ignoreProductId = null): void
    {
        $seen = [];

        foreach ($variants as $variant) {
            $sku = $variant['sku'] ?? null;

            if ($sku === null) {
                continue;
            }

            if (in_array($sku, $seen, true) || $this->products->variantSkuExists($sku, $ignoreProductId)) {
                throw ValidationException::withMessages([
                    'variants' => ['Variant SKUs must be unique.'],
                ]);
            }

            $seen[] = $sku;
        }
    }
}
