<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function allPairs(): array
    {
        return static::query()->pluck('value', 'key')->all();
    }

    public static function putPairs(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
