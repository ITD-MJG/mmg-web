<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Principal extends Model
{
    use HasFactory;
    use HasTranslations;

    /** `name` is a plain string: principal names are not translated. */
    public array $translatable = ['description'];

    protected $fillable = [
        'slug', 'name', 'logo', 'description', 'certifications',
        'sort_order', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'certifications' => 'array',
            'is_published' => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
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
