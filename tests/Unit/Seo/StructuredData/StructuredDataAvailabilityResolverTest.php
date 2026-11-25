<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\StructuredData;

use App\Domain\Seo\StructuredData\Enums\StructuredDataAvailability;
use App\Domain\Seo\StructuredData\Services\StructuredDataAvailabilityResolver;
use PHPUnit\Framework\TestCase;

final class StructuredDataAvailabilityResolverTest extends TestCase
{
    public function test_untracked_inventory_is_in_stock_even_when_quantity_is_zero(): void
    {
        $availability =
            (new StructuredDataAvailabilityResolver)
                ->resolve(
                    trackInventory: false,

                    stockQuantity: 0,
                );

        self::assertSame(
            StructuredDataAvailability::InStock,
            $availability,
        );
    }

    public function test_tracked_positive_quantity_is_in_stock(): void
    {
        $availability =
            (new StructuredDataAvailabilityResolver)
                ->resolve(
                    trackInventory: true,

                    stockQuantity: 10,
                );

        self::assertSame(
            StructuredDataAvailability::InStock,
            $availability,
        );
    }

    public function test_tracked_zero_quantity_is_out_of_stock(): void
    {
        $availability =
            (new StructuredDataAvailabilityResolver)
                ->resolve(
                    trackInventory: true,

                    stockQuantity: 0,
                );

        self::assertSame(
            StructuredDataAvailability::OutOfStock,
            $availability,
        );
    }

    public function test_tracked_negative_quantity_is_out_of_stock(): void
    {
        $availability =
            (new StructuredDataAvailabilityResolver)
                ->resolve(
                    trackInventory: true,

                    stockQuantity: -2,
                );

        self::assertSame(
            StructuredDataAvailability::OutOfStock,
            $availability,
        );
    }
}
