<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    /**
     * Read a setting. Values are stored as JSON so scalars and arrays share
     * one column; unwrap a single-element array back to the scalar callers
     * expect.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = static::query()->find($key)?->value;

        if ($value === null) {
            return $default;
        }

        if (is_array($value) && array_key_exists('v', $value)) {
            return $value['v'];
        }

        return $value;
    }

    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) ? $value : ['v' => $value]],
        );
    }
}
