<?php

declare(strict_types=1);

namespace App\Domain\Seo\StructuredData\Services;

use App\Domain\Seo\StructuredData\Enums\StructuredDataAvailability;

final class StructuredDataAvailabilityResolver
{
    public function resolve(
        bool $trackInventory,
        int $stockQuantity,
    ): StructuredDataAvailability {
        /*
         * This mirrors checkout stock semantics.
         *
         * Products which do not track inventory are
         * purchasable without a finite stock balance.
         */
        if (! $trackInventory) {
            return StructuredDataAvailability::InStock;
        }

        return $stockQuantity > 0
            ? StructuredDataAvailability::InStock
            : StructuredDataAvailability::OutOfStock;
    }
}
