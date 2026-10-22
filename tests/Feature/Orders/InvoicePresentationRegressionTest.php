<?php

declare(strict_types=1);

namespace Tests\Feature\Orders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class InvoicePresentationRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_invoice_document_keeps_print_only_invoice_boundary(): void
    {
        $source =
            $this->source(
                'resources/js/Components/Invoice/InvoiceDocument.tsx',
            );

        $this->assertStringContainsString(
            'body * {',
            $source,
        );

        $this->assertStringContainsString(
            'visibility: hidden !important;',
            $source,
        );

        $this->assertStringContainsString(
            '.invoice-print-root,',
            $source,
        );

        $this->assertStringContainsString(
            'visibility: visible !important;',
            $source,
        );

        $this->assertStringContainsString(
            'className="invoice-print-root',
            $source,
        );

        $this->assertStringContainsString(
            'className="invoice-screen-only',
            $source,
        );
    }

    public function test_invoice_keeps_a4_print_contract(): void
    {
        $source =
            $this->source(
                'resources/js/Components/Invoice/InvoiceDocument.tsx',
            );

        $this->assertStringContainsString(
            '@page {',
            $source,
        );

        $this->assertStringContainsString(
            'size: A4 portrait;',
            $source,
        );

        $this->assertStringContainsString(
            'margin: 0;',
            $source,
        );

        $this->assertStringContainsString(
            'min-height: 297mm !important;',
            $source,
        );
    }

    public function test_invoice_footer_is_pushed_to_bottom_of_printed_page(): void
    {
        $source =
            $this->source(
                'resources/js/Components/Invoice/InvoiceDocument.tsx',
            );

        $this->assertStringContainsString(
            'display: flex !important;',
            $source,
        );

        $this->assertStringContainsString(
            'flex-direction: column !important;',
            $source,
        );

        $this->assertStringContainsString(
            '.invoice-print-footer {',
            $source,
        );

        $this->assertStringContainsString(
            'margin-top: auto !important;',
            $source,
        );

        $this->assertStringContainsString(
            'page-break-inside: avoid !important;',
            $source,
        );
    }

    public function test_invoice_table_is_hardened_for_print_width(): void
    {
        $source =
            $this->source(
                'resources/js/Components/Invoice/InvoiceDocument.tsx',
            );

        $this->assertStringContainsString(
            'invoice-print-table-wrap',
            $source,
        );

        $this->assertStringContainsString(
            'invoice-print-table',
            $source,
        );

        $this->assertStringContainsString(
            'min-width: 0 !important;',
            $source,
        );

        $this->assertStringContainsString(
            'table-layout: fixed !important;',
            $source,
        );

        $this->assertStringContainsString(
            'display: table-header-group !important;',
            $source,
        );
    }

    public function test_invoice_rows_avoid_breaking_across_print_pages(): void
    {
        $source =
            $this->source(
                'resources/js/Components/Invoice/InvoiceDocument.tsx',
            );

        $this->assertStringContainsString(
            '.invoice-print-table tr {',
            $source,
        );

        $this->assertStringContainsString(
            'break-inside: avoid !important;',
            $source,
        );

        $this->assertStringContainsString(
            'page-break-inside: avoid !important;',
            $source,
        );
    }

    public function test_invoice_preserves_print_colors(): void
    {
        $source =
            $this->source(
                'resources/js/Components/Invoice/InvoiceDocument.tsx',
            );

        $this->assertStringContainsString(
            '-webkit-print-color-adjust: exact !important;',
            $source,
        );

        $this->assertStringContainsString(
            'print-color-adjust: exact !important;',
            $source,
        );
    }

    public function test_print_button_is_excluded_from_saved_invoice(): void
    {
        $source =
            $this->source(
                'resources/js/Components/Invoice/InvoiceDocument.tsx',
            );

        $this->assertStringContainsString(
            '.invoice-screen-only {',
            $source,
        );

        $this->assertStringContainsString(
            'display: none !important;',
            $source,
        );

        $this->assertStringContainsString(
            'Print Invoice',
            $source,
        );
    }

    public function test_invoice_branding_remains_settings_driven(): void
    {
        $source =
            $this->source(
                'resources/js/Components/Invoice/InvoiceDocument.tsx',
            );

        $this->assertStringContainsString(
            'branding.logo_url',
            $source,
        );

        $this->assertStringContainsString(
            'branding.company_name',
            $source,
        );

        $this->assertStringContainsString(
            'branding.company_tagline',
            $source,
        );

        $this->assertStringContainsString(
            'branding.header_start_color',
            $source,
        );

        $this->assertStringContainsString(
            'branding.header_end_color',
            $source,
        );

        $this->assertStringContainsString(
            'branding.footer_color',
            $source,
        );

        $this->assertStringContainsString(
            'branding.footer_credit',
            $source,
        );
    }

    public function test_invoice_does_not_render_live_catalog_dependencies(): void
    {
        $source =
            $this->source(
                'resources/js/Components/Invoice/InvoiceDocument.tsx',
            );

        $this->assertStringNotContainsString(
            'product_id',
            $source,
        );

        $this->assertStringNotContainsString(
            'variant_id',
            $source,
        );

        $this->assertStringNotContainsString(
            'route(\'admin.products',
            $source,
        );

        $this->assertStringNotContainsString(
            'route(\'products.',
            $source,
        );
    }

    public function test_admin_and_customer_invoice_pages_share_same_invoice_document(): void
    {
        $admin =
            $this->source(
                'resources/js/Pages/Admin/Invoices/Show.tsx',
            );

        $customer =
            $this->source(
                'resources/js/Pages/Customer/Invoices/Show.tsx',
            );

        $this->assertStringContainsString(
            "import InvoiceDocument from '@/Components/Invoice/InvoiceDocument'",
            $admin,
        );

        $this->assertStringContainsString(
            "import InvoiceDocument from '@/Components/Invoice/InvoiceDocument'",
            $customer,
        );

        $this->assertStringContainsString(
            '<InvoiceDocument',
            $admin,
        );

        $this->assertStringContainsString(
            '<InvoiceDocument',
            $customer,
        );
    }

    private function source(
        string $relativePath,
    ): string {
        $path =
            base_path(
                $relativePath,
            );

        $contents =
            file_get_contents(
                $path,
            );

        if ($contents === false) {
            $this->fail(
                sprintf(
                    'Unable to read regression source file [%s].',
                    $relativePath,
                ),
            );
        }

        return $contents;
    }
}
