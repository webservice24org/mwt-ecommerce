<?php

declare(strict_types=1);

namespace App\Domain\Seo\Services;

final readonly class StorefrontIndexationPolicy
{
    /**
     * Query parameters that create alternative listing
     * representations and therefore should not produce
     * independently indexable URLs.
     *
     * @var array<int, string>
     */
    private const LISTING_VARIATION_KEYS = [
        'q',
        'sort',
        'brand',
        'category',
        'min_price',
        'max_price',
        'attributes',
    ];

    /**
     * Base storefront listing URLs may be indexed.
     *
     * Search/filter/sort URLs and pagination beyond
     * page 1 are forced to noindex.
     *
     * Tracking/analytics parameters are intentionally
     * ignored here because they do not alter the
     * storefront resource itself.
     *
     * @param  array<string, mixed>  $query
     */
    public function allowsListingIndexing(
        array $query,
    ): bool {
        foreach (
            self::LISTING_VARIATION_KEYS as $key
        ) {
            if (
                $this->hasMeaningfulValue(
                    $query[$key] ?? null,
                )
            ) {
                return false;
            }
        }

        if (
            $this->isNonFirstPage(
                $query['page'] ?? null,
            )
        ) {
            return false;
        }

        return true;
    }

    private function isNonFirstPage(
        mixed $value,
    ): bool {
        if (
            ! $this->hasMeaningfulValue(
                $value,
            )
        ) {
            return false;
        }

        if (
            is_int(
                $value,
            )
        ) {
            return $value !== 1;
        }

        if (
            is_string(
                $value,
            )
            && ctype_digit(
                trim(
                    $value,
                ),
            )
        ) {
            return (int) trim(
                $value,
            ) !== 1;
        }

        return true;
    }

    private function hasMeaningfulValue(
        mixed $value,
    ): bool {
        if ($value === null) {
            return false;
        }

        if (
            is_string(
                $value,
            )
        ) {
            return trim(
                $value,
            ) !== '';
        }

        if (
            is_array(
                $value,
            )
        ) {
            foreach (
                $value as $item
            ) {
                if (
                    $this->hasMeaningfulValue(
                        $item,
                    )
                ) {
                    return true;
                }
            }

            return false;
        }

        if (
            is_bool(
                $value,
            )
        ) {
            return $value;
        }

        return true;
    }
}
