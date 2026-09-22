<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;

class ProductImage extends Model
{
    use HasFactory;
    use HasTranslations;

    public array $translatable = ['alt'];

    protected $fillable = ['product_id', 'is_cover', 'path', 'alt', 'sort_order'];

    protected function casts(): array
    {
        return ['is_cover' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $image) {
            if (! $image->is_cover) {
                return;
            }

            // Demote any existing cover for this product first, otherwise the
            // uniq_cover_per_product index rejects the write. Last cover set
            // wins: the generated column stays the guarantee for writes that
            // bypass the model, but it must never surface to an editor, who
            // toggles this through a plain switch in the admin.
            //
            // The self-exclusion is conditional because whereKeyNot(null)
            // compiles to `id is not null`, which matches every row — on a
            // create that would be harmless today but would silently demote
            // for the wrong reason and break the moment the intent changes.
            $query = static::query()
                ->where('product_id', $image->product_id)
                ->where('is_cover', true);

            if ($image->exists) {
                $query->whereKeyNot($image->getKey());
            }

            $query->update(['is_cover' => false]);
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Public URL for the image, or null when there is no path.
     *
     * Two provenances reach this column and they resolve differently, the same
     * split `Principal::logoUrl()` handles:
     *
     * - Seeded images are committed public assets stored public-root-relative
     *   (`images/products/tecan-spark.jpg`) and resolved with `asset()`.
     * - An upload through the admin panel lands on the `public` disk under
     *   `products/`, so it resolves through the disk's own URL.
     *
     * Seeded files have to be assets rather than uploads because
     * `storage/app/public` is gitignored and this project deploys by pushing
     * the repository, so a seeded upload would never reach the server.
     */
    public function url(): ?string
    {
        if (blank($this->path)) {
            return null;
        }

        return str_starts_with($this->path, 'images/')
            ? asset($this->path)
            : Storage::disk('public')->url($this->path);
    }
}
