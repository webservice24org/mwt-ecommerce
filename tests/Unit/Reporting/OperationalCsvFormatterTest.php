<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use App\Domain\Reporting\Operations\OperationalCsvFormatter;
use Tests\TestCase;

final class OperationalCsvFormatterTest extends TestCase
{
    public function test_bom_is_enabled_by_default(): void
    {
        config([
            'reporting.operational_exports.utf8_bom' => true,
        ]);

        $formatter =
            new OperationalCsvFormatter;

        $this->assertSame(
            "\xEF\xBB\xBF",
            $formatter->bom(),
        );
    }

    public function test_bom_can_be_disabled(): void
    {
        config([
            'reporting.operational_exports.utf8_bom' => false,
        ]);

        $formatter =
            new OperationalCsvFormatter;

        $this->assertSame(
            '',
            $formatter->bom(),
        );
    }

    public function test_plain_text_is_not_modified(): void
    {
        $formatter =
            new OperationalCsvFormatter;

        $this->assertSame(
            'Normal Product',
            $formatter->safe(
                'Normal Product',
            ),
        );
    }

    public function test_formula_prefixes_are_escaped(): void
    {
        $formatter =
            new OperationalCsvFormatter;

        $this->assertSame(
            "'=SUM(A1:A2)",
            $formatter->safe(
                '=SUM(A1:A2)',
            ),
        );

        $this->assertSame(
            "'+123",
            $formatter->safe(
                '+123',
            ),
        );

        $this->assertSame(
            "'-123",
            $formatter->safe(
                '-123',
            ),
        );

        $this->assertSame(
            "'@command",
            $formatter->safe(
                '@command',
            ),
        );
    }

    public function test_leading_whitespace_does_not_bypass_formula_protection(): void
    {
        $formatter =
            new OperationalCsvFormatter;

        $this->assertSame(
            "'   =FORMULA",
            $formatter->safe(
                '   =FORMULA',
            ),
        );
    }

    public function test_safe_row_only_modifies_strings(): void
    {
        $formatter =
            new OperationalCsvFormatter;

        $row =
            $formatter->safeRow(
                [
                    '=Formula',
                    123,
                    true,
                    null,
                    'Safe',
                ],
            );

        $this->assertSame(
            [
                "'=Formula",
                123,
                true,
                null,
                'Safe',
            ],
            $row,
        );
    }
}
