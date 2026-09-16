<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Services;

use App\Models\Product;
use Illuminate\Support\Str;

final class ProductSeoService
{
    public function title(Product $product): string
    {
        $metaTitle = $this->clean(
            $product->meta_title,
        );

        return $metaTitle
            ?? $product->name;
    }

    public function description(
        Product $product,
        int $limit = 160,
    ): ?string {
        $description = $this->clean(
            $product->meta_description,
        );

        if ($description !== null) {
            return Str::limit(
                $description,
                $limit,
                '',
            );
        }

        $description = $this->clean(
            $product->short_description,
        );

        if ($description !== null) {
            return Str::limit(
                $this->plainText($description),
                $limit,
                '',
            );
        }

        $description = $this->clean(
            $product->description,
        );

        if ($description === null) {
            return null;
        }

        return Str::limit(
            $this->plainText($description),
            $limit,
            '',
        );
    }

    private function clean(
        ?string $value,
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === ''
            ? null
            : $value;
    }

    private function plainText(
        string $value,
    ): string {
        return trim(
            preg_replace(
                '/\s+/u',
                ' ',
                strip_tags($value),
            ) ?? '',
        );
    }
}
