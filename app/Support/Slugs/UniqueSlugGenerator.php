<?php

declare(strict_types=1);

namespace App\Support\Slugs;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

        while (
            $this->exists(
                table: $table,
                column: $column,
                slug: $slug,
                ignoreId: $ignoreId,
            )
            || $this->isReservedHistoricalSlug(
                table: $table,
                slug: $slug,
            )
        ) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function isReservedHistoricalSlug(
        string $table,
        string $slug,
    ): bool {
        if (! Schema::hasTable('seo_slug_redirects')) {
            return false;
        }

        $entityType = match ($table) {
            'products' => 'product',
            'categories' => 'category',
            'brands' => 'brand',
            default => null,
        };

        if ($entityType === null) {
            return false;
        }

        return DB::table('seo_slug_redirects')
            ->where('entity_type', $entityType)
            ->where('old_slug', $slug)
            ->exists();
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
