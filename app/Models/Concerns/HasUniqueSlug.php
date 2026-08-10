<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Gera slugs unicos a partir de um campo de origem, ignorando o proprio
 * registro quando ele ja existe. Evita o erro de chave duplicada que
 * acontecia ao cadastrar dois registros com o mesmo nome.
 */
trait HasUniqueSlug
{
    public static function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($value) ?: 'item';
        $slug = $baseSlug;
        $counter = 2;

        while (static::slugExists($slug, $ignoreId)) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    protected static function slugExists(string $slug, ?int $ignoreId): bool
    {
        $query = static::query()->where('slug', $slug);

        if (method_exists(static::class, 'bootSoftDeletes')) {
            $query->withTrashed();
        }

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }
}
