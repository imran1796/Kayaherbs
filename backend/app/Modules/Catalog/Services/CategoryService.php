<?php

namespace App\Modules\Catalog\Services;

use App\Models\Category;
use App\Modules\Catalog\Repositories\CategoryRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    public function __construct(
        protected CategoryRepository $categories
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->categories->paginateWithParent($perPage);
    }

    public function rootCategories(): Collection
    {
        return $this->categories->rootCategories();
    }

    public function findOrFail(int $id): Category
    {
        return $this->categories->findWithRelationsOrFail($id);
    }

    public function create(array $data): Category
    {
        return DB::transaction(function () use ($data): Category {
            $this->ensureSlugIsUnique($data['slug']);

            /** @var Category $category */
            $category = $this->categories->create($this->sanitizePayload($data));

            return $category->refresh();
        });
    }

    public function update(int $id, array $data): Category
    {
        return DB::transaction(function () use ($id, $data): Category {
            $category = $this->findOrFail($id);
            $this->ensureSlugIsUnique($data['slug'], $category->id);
            $this->ensureParentIsValid($category, $data['parent_id'] ?? null);

            /** @var Category $updatedCategory */
            $updatedCategory = $this->categories->update($category, $this->sanitizePayload($data));

            return $updatedCategory;
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            $category = $this->findOrFail($id);

            if ($this->categories->hasChildren($category)) {
                throw ValidationException::withMessages([
                    'category' => ['A category with child categories cannot be deleted.'],
                ]);
            }

            return $this->categories->delete($category);
        });
    }

    protected function sanitizePayload(array $data): array
    {
        return Arr::only($data, [
            'parent_id',
            'name',
            'slug',
            'description',
            'image_path',
            'sort_order',
            'status',
        ]);
    }

    private function ensureSlugIsUnique(string $slug, ?int $ignoreId = null): void
    {
        $existingCategory = $this->categories->findBySlug($slug);

        if ($existingCategory !== null && $existingCategory->id !== $ignoreId) {
            throw ValidationException::withMessages([
                'slug' => ['A category already exists with this slug.'],
            ]);
        }
    }

    private function ensureParentIsValid(Category $category, ?int $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        if ($category->id === $parentId) {
            throw ValidationException::withMessages([
                'parent_id' => ['A category cannot be its own parent.'],
            ]);
        }
    }
}
