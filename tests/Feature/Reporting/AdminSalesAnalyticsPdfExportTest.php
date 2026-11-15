<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\LaravelPdf\Facades\Pdf;
use Tests\TestCase;

final class AdminSalesAnalyticsPdfExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_export_sales_analytics_pdf(): void
    {
        $this
            ->get(
                route(
                    'admin.reports.sales.analytics.pdf',
                ),
            )
            ->assertRedirect();
    }

    public function test_admin_can_download_sales_analytics_pdf(): void
    {
        Pdf::fake();

        $admin =
            Admin::factory()
                ->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.sales.analytics.pdf',
                    [
                        'preset' => 'custom',

                        'from' => '2026-11-01',

                        'to' => '2026-11-30',

                        'timezone' => 'Asia/Dhaka',
                    ],
                ),
            )
            ->assertOk();

        Pdf::assertRespondedWithPdf(
            static function ($pdf): bool {
                return $pdf->contains(
                    'Sales Analytics Report',
                );
            },
        );
    }

    public function test_pdf_template_contains_all_chart_sections(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'views/admin/reports/sales-analytics-pdf.blade.php',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            "chartSvgs['revenue_trend']",
            $source,
        );

        $this->assertStringContainsString(
            "chartSvgs['orders_trend']",
            $source,
        );

        $this->assertStringContainsString(
            "chartSvgs['status_revenue']",
            $source,
        );

        $this->assertStringContainsString(
            "chartSvgs['payment_status_revenue']",
            $source,
        );

        $this->assertStringContainsString(
            "chartSvgs['payment_method_revenue']",
            $source,
        );

        $this->assertStringContainsString(
            'Daily Sales Summary',
            $source,
        );
    }
}
