<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasFactory;
    use HasTranslations;

    public array $translatable = ['name', 'short_description', 'description'];

    protected $fillable = [
        'category_id', 'principal_id', 'slug', 'sku',
        'name', 'short_description', 'description',
        'specs', 'certifications',
        'is_published', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'specs' => 'array',
            'certifications' => 'array',
            'is_published' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function principal(): BelongsTo
    {
        return $this->belongsTo(Principal::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * The flagged cover image, or the first gallery image as a fallback.
     *
     * Products with no images at all return null; callers must render a
     * placeholder rather than assume a cover exists.
     */
    public function coverImage(): ?ProductImage
    {
        return $this->images->firstWhere('is_cover', true)
            ?? $this->images->first();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
