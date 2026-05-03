<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function scopeVisibleToStorefront(Builder $query): Builder
    {
        return $query
            ->published()
            ->hasVisibleVariant()
            ->whereHas('images')
            ->hasVisibleCategory();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->whereNotNull('published_at');
    }

    public function scopeHasVisibleVariant(Builder $query): Builder
    {
        return $query->whereHas('variants', function (Builder $variantQuery): void {
            $variantQuery->visibleToStorefront();
        });
    }

    public function scopeHasVisibleCategory(Builder $query): Builder
    {
        return $query->where(function (Builder $categoryQuery): void {
            $categoryQuery
                ->whereDoesntHave('categories')
                ->orWhereHas('categories', function (Builder $activeCategoryQuery): void {
                    $activeCategoryQuery->active();
                });
        });
    }
}
