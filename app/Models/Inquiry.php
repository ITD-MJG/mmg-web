<?php

namespace App\Models;

use App\Enums\InquiryStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'company', 'email', 'phone', 'product_id',
        'message', 'locale', 'status', 'ip_hash', 'source_path',
    ];

    protected function casts(): array
    {
        return ['status' => InquiryStatus::class];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
