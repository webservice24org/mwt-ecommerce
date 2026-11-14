<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Models\Admin;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminReportExportFeedbackContractTest extends TestCase
{
    use RefreshDatabase;

    private function source(): string
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Components/Admin/Reports/ReportHeader.tsx',
                ),
            );

        $this->assertIsString(
            $source,
        );

        return $source;
    }

    public function test_report_header_has_export_loading_state(): void
    {
        $source =
            $this->source();

        $this->assertStringContainsString(
            'isExporting',
            $source,
        );

        $this->assertStringContainsString(
            'Preparing export...',
            $source,
        );

        $this->assertStringContainsString(
            'disabled={',
            $source,
        );

        $this->assertStringContainsString(
            'aria-busy',
            $source,
        );
    }

    public function test_report_header_prevents_duplicate_export_requests(): void
    {
        $source =
            $this->source();

        $this->assertStringContainsString(
            '!exportUrl ||',
            $source,
        );

        $this->assertStringContainsString(
            'isExporting',
            $source,
        );
    }

    public function test_report_header_surfaces_export_errors(): void
    {
        $source =
            $this->source();

        $this->assertStringContainsString(
            'exportError',
            $source,
        );

        $this->assertStringContainsString(
            'role="alert"',
            $source,
        );

        $this->assertStringContainsString(
            'Export unavailable',
            $source,
        );
    }

    public function test_report_header_handles_oversized_export_response(): void
    {
        $source =
            $this->source();

        $this->assertStringContainsString(
            'response.status ===',
            $source,
        );

        $this->assertStringContainsString(
            '413',
            $source,
        );

        $this->assertStringContainsString(
            'too large to export',
            $source,
        );
    }

    public function test_report_header_downloads_successful_blob(): void
    {
        $source =
            $this->source();

        $this->assertStringContainsString(
            'response.blob()',
            $source,
        );

        $this->assertStringContainsString(
            'URL.createObjectURL',
            $source,
        );

        $this->assertStringContainsString(
            'Content-Disposition',
            $source,
        );

        $this->assertStringContainsString(
            'anchor.download',
            $source,
        );
    }

    public function test_export_request_asks_laravel_for_json_errors(): void
    {
        $source =
            $this->source();

        $this->assertStringContainsString(
            "'application/json'",
            $source,
        );

        $this->assertStringContainsString(
            "'X-Requested-With'",
            $source,
        );
    }

    public function test_oversized_export_returns_readable_json_message_for_ajax_request(): void
    {
        config([
            'reporting.exports.max_rows' => 1,
        ]);

        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        for (
            $index = 1;
            $index <= 2;
            $index++
        ) {
            Order::factory()
                ->for(
                    $customer,
                )
                ->create([
                    'placed_at' => CarbonImmutable::parse(
                        '2026-11-10 10:00:00',
                        'Asia/Dhaka',
                    ),
                ]);
        }

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->withHeaders([
                'Accept' => 'application/json',

                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->get(
                route(
                    'admin.reports.sales.export',
                    [
                        'preset' => 'custom',

                        'from' => '2026-11-01',

                        'to' => '2026-11-30',

                        'timezone' => 'Asia/Dhaka',
                    ],
                ),
            )
            ->assertStatus(
                413,
            )
            ->assertJsonPath(
                'message',
                'This export contains 2 rows and exceeds the maximum allowed export size of 1 rows. Narrow the report filters and try again.',
            );
    }
}
