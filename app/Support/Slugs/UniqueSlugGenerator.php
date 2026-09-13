<?php

declare(strict_types=1);

namespace App\Support\Slugs;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class UniqueSlugGenerator
{
    public function generate(
        string $table,
        string $value,
        ?int $ignoreId = null,
        string $column = 'slug',
    ): string {
        $baseSlug = Str::slug($value);

        if ($baseSlug === '') {
            $baseSlug = 'item';
        }

        $slug = $baseSlug;
        $suffix = 2;

        while ($this->exists(
            table: $table,
            column: $column,
            slug: $slug,
            ignoreId: $ignoreId,
        )) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function exists(
        string $table,
        string $column,
        string $slug,
        ?int $ignoreId,
    ): bool {
        $query = DB::table($table)
            ->where($column, $slug);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}
