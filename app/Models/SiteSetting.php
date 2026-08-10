<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
    ];

    public static function allSettings(): array
    {
        return Cache::rememberForever('site_settings.all', function () {
            return self::query()->pluck('value', 'key')->all();
        });
    }

    public static function getValue(string $key, ?string $default = null): ?string
    {
        return self::allSettings()[$key] ?? $default;
    }

    public static function setValue(string $key, ?string $value, string $type = 'text', string $group = 'general'): void
    {
        self::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type, 'group' => $group]
        );

        Cache::forget('site_settings.all');
    }
}
