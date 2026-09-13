<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Services;

use App\Models\AttributeValue;
use Illuminate\Support\Str;

final class AttributeValueSlugGenerator
{
    public function generate(
        int $attributeId,
        string $value,
        ?int $ignoreId = null,
    ): string {
        $baseSlug = Str::slug($value);

        if ($baseSlug === '') {
            $baseSlug = 'value';
        }

        $slug = $baseSlug;
        $suffix = 2;

        while (
            $this->exists(
                attributeId: $attributeId,
                slug: $slug,
                ignoreId: $ignoreId,
            )
        ) {
            $slug = $baseSlug.'-'.$suffix;

            $suffix++;
        }

        return $slug;
    }

    private function exists(
        int $attributeId,
        string $slug,
        ?int $ignoreId,
    ): bool {
        return AttributeValue::query()
            ->where(
                'attribute_id',
                $attributeId,
            )
            ->where(
                'slug',
                $slug,
            )
            ->when(
                $ignoreId !== null,
                static fn ($query) => $query->whereKeyNot(
                    $ignoreId,
                ),
            )
            ->exists();
    }
}
