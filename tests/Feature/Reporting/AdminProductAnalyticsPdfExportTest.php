<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\LaravelPdf\Facades\Pdf;
use Tests\TestCase;

final class AdminProductAnalyticsPdfExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_export_product_analytics_pdf(): void
    {
        $this
            ->get(
                route(
                    'admin.reports.products.analytics.pdf',
                ),
            )
            ->assertRedirect();
    }

    public function test_admin_can_download_product_analytics_pdf(): void
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
                    'admin.reports.products.analytics.pdf',
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
                    'Product Analytics Report',
                );
            },
        );
    }

    public function test_product_analytics_pdf_contains_all_chart_sections(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'views/admin/reports/product-analytics-pdf.blade.php',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            "chartSvgs['sales_trend']",
            $source,
        );

        $this->assertStringContainsString(
            "chartSvgs['top_products_by_revenue']",
            $source,
        );

        $this->assertStringContainsString(
            "chartSvgs['top_products_by_quantity']",
            $source,
        );

        $this->assertStringContainsString(
            "chartSvgs['current_vs_historical_revenue']",
            $source,
        );

        $this->assertStringContainsString(
            "chartSvgs['sku_performance']",
            $source,
        );

        $this->assertStringContainsString(
            "chartSvgs['revenue_share_by_product']",
            $source,
        );

        $this->assertStringContainsString(
            'Product Performance Summary',
            $source,
        );
    }

    public function test_product_analytics_pdf_preserves_historical_product_contract(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'views/admin/reports/product-analytics-pdf.blade.php',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            '$row->historical',
            $source,
        );

        $this->assertStringContainsString(
            'Historical',
            $source,
        );

        $this->assertStringContainsString(
            '$row->productName',
            $source,
        );

        $this->assertStringContainsString(
            '$row->sku',
            $source,
        );
    }

    public function test_product_analytics_frontend_exposes_pdf_download_contract(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Pages/Admin/Reports/ProductAnalytics/Index.tsx',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            "'admin.reports.products.analytics.pdf'",
            $source,
        );

        $this->assertStringContainsString(
            'buildPdfExportUrl',
            $source,
        );

        $this->assertStringContainsString(
            'Download PDF',
            $source,
        );

        $this->assertStringContainsString(
            'status[]',
            $source,
        );

        $this->assertStringContainsString(
            'payment_status[]',
            $source,
        );
    }

    public function test_pdf_controller_rebuilds_authoritative_report_and_charts_server_side(): void
    {
        $source =
            file_get_contents(
                app_path(
                    'Http/Controllers/Admin/Reports/ProductAnalyticsPdfExportController.php',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            'ProductPerformanceReportRequest $request',
            $source,
        );

        $this->assertStringContainsString(
            '$request->filters(',
            $source,
        );

        $this->assertStringContainsString(
            '$reportQuery->get(',
            $source,
        );

        $this->assertStringContainsString(
            '$chartBuilder->build(',
            $source,
        );

        $this->assertStringNotContainsString(
            '$request->input(\'charts\'',
            $source,
        );

        $this->assertStringNotContainsString(
            '$request->input(\'report\'',
            $source,
        );
    }
}
