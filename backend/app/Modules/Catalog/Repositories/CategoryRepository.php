<?php

namespace App\Modules\Catalog\Repositories;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository
{
    public function paginateWithParent(int $perPage = 15): LengthAwarePaginator
    {
        return Category::query()
            ->with('parent')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function findWithRelationsOrFail(int $id): Category
    {
        /** @var Category $category */
        $category = Category::query()
            ->with(['parent', 'children'])
            ->findOrFail($id);

        return $category;
    }

    public function findBySlug(string $slug): ?Category
    {
        /** @var Category|null $category */
        $category = Category::query()->where('slug', $slug)->first();

        return $category;
    }

    public function rootCategories(): Collection
    {
        return Category::query()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): Category
    {
        return Category::query()->create($data);
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        return $category->refresh();
    }

    public function delete(Category $category): bool
    {
        return (bool) $category->delete();
    }

    public function hasChildren(Category $category): bool
    {
        return $category->children()->exists();
    }
}
