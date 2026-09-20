<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
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

    /**
     * Public URL for the mark, or null when the principal has none.
     *
     * Two provenances reach this column and they resolve differently:
     *
     * - Seeded logos are shipped public assets stored public-root-relative
     *   (`images/principals/mindray.png`) and resolved with `asset()`, the
     *   same way the company logo setting is.
     * - An upload through the admin panel lands on the `public` disk under
     *   `principals/`, so it resolves through the disk's own URL.
     *
     * Seeded files have to be assets rather than uploads because
     * `storage/app/public` is gitignored and this project deploys by pushing
     * the repository, so a seeded upload would never reach the server.
     */
    public function logoUrl(): ?string
    {
        if (blank($this->logo)) {
            return null;
        }

        return str_starts_with($this->logo, 'images/')
            ? asset($this->logo)
            : Storage::disk('public')->url($this->logo);
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
