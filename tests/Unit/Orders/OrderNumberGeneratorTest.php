<?php

declare(strict_types=1);

namespace Tests\Unit\Orders;

use App\Domain\Orders\Services\OrderNumberGenerator;
use PHPUnit\Framework\TestCase;

final class OrderNumberGeneratorTest extends TestCase
{
    public function test_it_generates_order_number_with_expected_prefix(): void
    {
        $number = (
            new OrderNumberGenerator
        )->generate();

        $this->assertStringStartsWith(
            'ORD-',
            $number,
        );

        $this->assertLessThanOrEqual(
            50,
            strlen(
                $number,
            ),
        );
    }

    public function test_generated_order_numbers_are_distinct(): void
    {
        $generator =
            new OrderNumberGenerator;

        $first =
            $generator->generate();

        $second =
            $generator->generate();

        $this->assertNotSame(
            $first,
            $second,
        );
    }
}
