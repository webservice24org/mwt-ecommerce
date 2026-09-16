<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Services;

use App\Models\Product;
use App\Models\ProductImage;

final class ProductMediaIntegrityService
{
    public function nextImagePosition(
        Product $product,
    ): int {
        $maximum = $product
            ->images()
            ->max('position');

        if ($maximum === null) {
            return 0;
        }

        return (int) $maximum + 1;
    }

    public function clearOtherPrimaryImages(
        Product $product,
        ?ProductImage $except = null,
    ): void {
        $product
            ->images()
            ->when(
                $except !== null,
                static fn ($query) => $query->where(
                    'id',
                    '!=',
                    $except->id,
                ),
            )
            ->where('is_primary', true)
            ->update([
                'is_primary' => false,
            ]);
    }

    public function promotePrimaryImage(
        Product $product,
    ): void {
        $primaryExists = $product
            ->images()
            ->where('is_primary', true)
            ->exists();

        if ($primaryExists) {
            return;
        }

        $replacement = $product
            ->images()
            ->orderBy('position')
            ->orderBy('id')
            ->first();

        if ($replacement === null) {
            return;
        }

        $replacement->update([
            'is_primary' => true,
        ]);
    }

    public function normalizePositions(
        Product $product,
    ): void {
        $images = $product
            ->images()
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        foreach (
            $images as $position => $image
        ) {
            if (
                $image->position === $position
            ) {
                continue;
            }

            $image->update([
                'position' => $position,
            ]);
        }
    }
}
