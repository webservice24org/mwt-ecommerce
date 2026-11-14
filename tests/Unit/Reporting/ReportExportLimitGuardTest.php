<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use App\Domain\Reporting\Export\ReportExportLimitGuard;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class ReportExportLimitGuardTest extends TestCase
{
    public function test_export_at_limit_is_allowed(): void
    {
        config([
            'reporting.exports.max_rows' => 100,
        ]);

        $guard =
            new ReportExportLimitGuard;

        $guard->assertAllowed(
            100,
        );

        $this->assertTrue(
            true,
        );
    }

    public function test_export_below_limit_is_allowed(): void
    {
        config([
            'reporting.exports.max_rows' => 100,
        ]);

        $guard =
            new ReportExportLimitGuard;

        $guard->assertAllowed(
            99,
        );

        $this->assertTrue(
            true,
        );
    }

    public function test_export_above_limit_is_rejected(): void
    {
        config([
            'reporting.exports.max_rows' => 100,
        ]);

        $guard =
            new ReportExportLimitGuard;

        try {
            $guard->assertAllowed(
                101,
            );

            $this->fail(
                'Expected export limit exception.',
            );
        } catch (
            HttpException $exception
        ) {
            $this->assertSame(
                413,
                $exception->getStatusCode(),
            );

            $this->assertStringContainsString(
                '101 rows',
                $exception->getMessage(),
            );

            $this->assertStringContainsString(
                '100 rows',
                $exception->getMessage(),
            );
        }
    }

    public function test_invalid_config_falls_back_to_default_limit(): void
    {
        config([
            'reporting.exports.max_rows' => 'invalid',
        ]);

        $guard =
            new ReportExportLimitGuard;

        $this->assertSame(
            10_000,
            $guard->maxRows(),
        );
    }
}
