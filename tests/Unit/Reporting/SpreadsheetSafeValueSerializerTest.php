<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use App\Domain\Reporting\Support\SpreadsheetSafeValueSerializer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SpreadsheetSafeValueSerializerTest extends TestCase
{
    private SpreadsheetSafeValueSerializer $serializer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serializer =
            new SpreadsheetSafeValueSerializer;
    }

    public function test_null_becomes_empty_string(): void
    {
        $this->assertSame(
            '',
            $this->serializer
                ->serialize(
                    null,
                ),
        );
    }

    public function test_integer_remains_exact_integer_string(): void
    {
        $this->assertSame(
            '3160000',
            $this->serializer
                ->serialize(
                    3_160_000,
                ),
        );
    }

    public function test_normal_text_is_unchanged(): void
    {
        $this->assertSame(
            'Dhaka',
            $this->serializer
                ->serialize(
                    'Dhaka',
                ),
        );
    }

    #[DataProvider('dangerousValues')]
    public function test_formula_like_values_are_neutralized(
        string $value,
    ): void {
        $this->assertSame(
            "'".$value,
            $this->serializer
                ->serialize(
                    $value,
                ),
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function dangerousValues(): array
    {
        return [
            'equals' => [
                '=SUM(A1:A5)',
            ],

            'plus' => [
                '+1+1',
            ],

            'minus' => [
                '-2+3',
            ],

            'at-sign' => [
                '@SUM(A1:A5)',
            ],

            'leading whitespace' => [
                '   =SUM(A1:A5)',
            ],
        ];
    }

    public function test_plain_numeric_string_is_not_changed(): void
    {
        $this->assertSame(
            '12345',
            $this->serializer
                ->serialize(
                    '12345',
                ),
        );
    }
}
