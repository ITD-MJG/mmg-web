<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
            // uniq_cover_per_product index rejects the write. The generated
            // column is the real guarantee; this keeps the common path from
            // surfacing a constraint violation to the editor.
            //
            // whereKeyNot() is a no-op when the key is null (a create), which
            // is correct: there is no row to exclude, so every sibling cover
            // is demoted.
            static::query()
                ->where('product_id', $image->product_id)
                ->whereKeyNot($image->getKey())
                ->where('is_cover', true)
                ->update(['is_cover' => false]);
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
