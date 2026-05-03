<?php

namespace App\Modules\Catalog\Repositories;

use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class ProductRepository
{
    public function paginateWithRelations(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(['categories', 'variants', 'images'])
            ->latest('id');

        $this->applyFilters($query, $filters);

        return $query
            ->paginate($perPage);
    }

    public function paginateVisibleForStorefront(int $perPage = 15): LengthAwarePaginator
    {
        return Product::query()
            ->visibleToStorefront()
            ->with($this->storefrontRelations())
            ->latest('published_at')
            ->paginate($perPage);
    }

    public function findWithRelationsOrFail(int $id): Product
    {
        /** @var Product $product */
        $product = Product::query()
            ->with(['categories', 'variants', 'images'])
            ->findOrFail($id);

        return $product;
    }

    public function findBySlug(string $slug): ?Product
    {
        /** @var Product|null $product */
        $product = Product::query()->where('slug', $slug)->first();

        return $product;
    }

    public function findVisibleBySlugOrFail(string $slug): Product
    {
        /** @var Product $product */
        $product = Product::query()
            ->visibleToStorefront()
            ->with($this->storefrontRelations())
            ->where('slug', $slug)
            ->firstOrFail();

        return $product;
    }

    public function variantSkuExists(string $sku, ?int $ignoreProductId = null): bool
    {
        $query = ProductVariant::query();

        if ($ignoreProductId !== null) {
            $query->where('product_id', '!=', $ignoreProductId);
        }

        return $query
            ->get()
            ->contains(fn (ProductVariant $variant): bool => strcasecmp((string) $variant->sku, $sku) === 0);
    }

    public function create(array $data): Product
    {
        return Product::query()->create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->refresh();
    }

    public function delete(Product $product): bool
    {
        return (bool) $product->delete();
    }

    public function syncCategories(Product $product, array $categoryIds): void
    {
        $product->categories()->sync($categoryIds);
    }

    public function replaceVariants(Product $product, array $variants): void
    {
        $product->variants()->delete();

        foreach ($variants as $variant) {
            $createdVariant = $product->variants()->create(Arr::only($variant, [
                'name',
                'sku',
                'price',
                'compare_at_price',
                'sort_order',
                'is_default',
                'status',
            ]));

            InventoryStock::query()->firstOrCreate([
                'product_variant_id' => $createdVariant->id,
            ]);
        }
    }

    public function replaceImages(Product $product, array $images): void
    {
        $product->images()->delete();

        foreach ($images as $image) {
            $product->images()->create(Arr::only($image, [
                'path',
                'alt_text',
                'sort_order',
                'is_primary',
            ]));
        }
    }

    private function storefrontRelations(): array
    {
        return [
            'categories' => fn ($query) => $query->active(),
            'variants' => fn ($query) => $query->visibleToStorefront(),
            'variants.stock',
            'images',
        ];
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $categoryId = (int) ($filters['category_id'] ?? 0);

        if ($search !== '') {
            $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%');
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($categoryId > 0) {
            $query->whereHas('categories', function (Builder $categoryQuery) use ($categoryId): void {
                $categoryQuery->whereKey($categoryId);
            });
        }
    }
}
