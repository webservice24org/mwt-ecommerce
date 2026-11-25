<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\StructuredData;

use App\Domain\Seo\StructuredData\Services\StructuredDataMoneyFormatter;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class StructuredDataMoneyFormatterTest extends TestCase
{
    public function test_it_formats_integer_minor_units(): void
    {
        $formatter =
            new StructuredDataMoneyFormatter;

        self::assertSame(
            '0.00',
            $formatter->fromMinorUnits(
                0,
            ),
        );

        self::assertSame(
            '0.01',
            $formatter->fromMinorUnits(
                1,
            ),
        );

        self::assertSame(
            '4850.00',
            $formatter->fromMinorUnits(
                485000,
            ),
        );

        self::assertSame(
            '4850.99',
            $formatter->fromMinorUnits(
                485099,
            ),
        );
    }

    public function test_negative_money_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        (new StructuredDataMoneyFormatter)
            ->fromMinorUnits(
                -1,
            );
    }
}
