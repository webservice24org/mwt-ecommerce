<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Reporting\Enums\ReportDatePreset;
use App\Domain\Reporting\Services\ReportDateRangeFactory;
use App\Http\Requests\Admin\ProductPerformanceReportRequest;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class ProductPerformanceReportRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(
            'web',
        )->get(
            '/_test/product-performance-report',
            static function (ProductPerformanceReportRequest $request, ReportDateRangeFactory $dateRanges) {
                return response()->json([
                    'filters' => $request
                        ->filters(
                            $dateRanges,
                        )
                        ->toArray(),

                    'query' => $request
                        ->queryParameters(),

                    'page' => $request
                        ->pageNumber(),
                ]);
            },
        );
    }

    public function test_defaults_to_this_month_and_twenty_five_rows(): void
    {
        $this
            ->get(
                '/_test/product-performance-report',
            )
            ->assertOk()
            ->assertJsonPath(
                'filters.range.preset',
                ReportDatePreset::ThisMonth->value,
            )
            ->assertJsonPath(
                'filters.per_page',
                25,
            )
            ->assertJsonPath(
                'filters.statuses',
                [],
            )
            ->assertJsonPath(
                'filters.payment_statuses',
                [],
            )
            ->assertJsonPath(
                'filters.search',
                null,
            )
            ->assertJsonPath(
                'query.preset',
                'this_month',
            )
            ->assertJsonPath(
                'query.per_page',
                25,
            )
            ->assertJsonPath(
                'page',
                1,
            );
    }

    public function test_custom_range_is_parsed(): void
    {
        $this
            ->get(
                '/_test/product-performance-report?'
                .http_build_query([
                    'preset' => 'custom',

                    'from' => '2026-11-01',

                    'to' => '2026-11-30',

                    'timezone' => 'Asia/Dhaka',
                ]),
            )
            ->assertOk()
            ->assertJsonPath(
                'filters.range.preset',
                'custom',
            )
            ->assertJsonPath(
                'filters.range.from',
                '2026-11-01',
            )
            ->assertJsonPath(
                'filters.range.to',
                '2026-11-30',
            )
            ->assertJsonPath(
                'query.from',
                '2026-11-01',
            )
            ->assertJsonPath(
                'query.to',
                '2026-11-30',
            )
            ->assertJsonPath(
                'query.timezone',
                'Asia/Dhaka',
            );
    }

    public function test_status_filters_are_parsed(): void
    {
        $this
            ->get(
                '/_test/product-performance-report?'
                .http_build_query([
                    'status' => [
                        'processing',
                        'completed',
                    ],
                ]),
            )
            ->assertOk()
            ->assertJsonPath(
                'filters.statuses.0',
                'processing',
            )
            ->assertJsonPath(
                'filters.statuses.1',
                'completed',
            )
            ->assertJsonPath(
                'query.status.0',
                'processing',
            );
    }

    public function test_payment_filters_are_parsed(): void
    {
        $this
            ->get(
                '/_test/product-performance-report?'
                .http_build_query([
                    'payment_status' => [
                        'paid',
                        'due',
                    ],
                ]),
            )
            ->assertOk()
            ->assertJsonPath(
                'filters.payment_statuses.0',
                'paid',
            )
            ->assertJsonPath(
                'filters.payment_statuses.1',
                'due',
            );
    }

    public function test_search_is_trimmed(): void
    {
        $this
            ->get(
                '/_test/product-performance-report?'
                .http_build_query([
                    'search' => '  SKU-123  ',
                ]),
            )
            ->assertOk()
            ->assertJsonPath(
                'filters.search',
                'SKU-123',
            )
            ->assertJsonPath(
                'query.search',
                'SKU-123',
            );
    }

    public function test_blank_search_is_removed_from_query(): void
    {
        $response =
            $this->get(
                '/_test/product-performance-report?'
                .http_build_query([
                    'search' => '   ',
                ]),
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'filters.search',
                null,
            )
            ->assertJsonMissingPath(
                'query.search',
            );
    }

    public function test_page_and_per_page_are_parsed(): void
    {
        $this
            ->get(
                '/_test/product-performance-report?'
                .http_build_query([
                    'page' => 3,

                    'per_page' => 50,
                ]),
            )
            ->assertOk()
            ->assertJsonPath(
                'page',
                3,
            )
            ->assertJsonPath(
                'filters.per_page',
                50,
            )
            ->assertJsonPath(
                'query.per_page',
                50,
            );
    }

    public function test_invalid_order_status_is_rejected(): void
    {
        $this
            ->from(
                '/_test/product-performance-report',
            )
            ->get(
                '/_test/product-performance-report?status[]=invalid',
            )
            ->assertRedirect(
                '/_test/product-performance-report',
            )
            ->assertSessionHasErrors(
                'status.0',
            );
    }

    public function test_invalid_payment_status_is_rejected(): void
    {
        $this
            ->from(
                '/_test/product-performance-report',
            )
            ->get(
                '/_test/product-performance-report?payment_status[]=invalid',
            )
            ->assertRedirect(
                '/_test/product-performance-report',
            )
            ->assertSessionHasErrors(
                'payment_status.0',
            );
    }

    public function test_invalid_preset_is_rejected(): void
    {
        $this
            ->from(
                '/_test/product-performance-report',
            )
            ->get(
                '/_test/product-performance-report?preset=invalid',
            )
            ->assertRedirect(
                '/_test/product-performance-report',
            )
            ->assertSessionHasErrors(
                'preset',
            );
    }

    public function test_custom_range_requires_dates(): void
    {
        $this
            ->from(
                '/_test/product-performance-report',
            )
            ->get(
                '/_test/product-performance-report?preset=custom',
            )
            ->assertRedirect(
                '/_test/product-performance-report',
            )
            ->assertSessionHasErrors([
                'from',
                'to',
            ]);
    }

    public function test_reverse_range_is_rejected(): void
    {
        $this
            ->from(
                '/_test/product-performance-report',
            )
            ->get(
                '/_test/product-performance-report?'
                .http_build_query([
                    'preset' => 'custom',

                    'from' => '2026-11-30',

                    'to' => '2026-11-01',
                ]),
            )
            ->assertRedirect(
                '/_test/product-performance-report',
            )
            ->assertSessionHasErrors(
                'to',
            );
    }

    public function test_invalid_timezone_is_rejected(): void
    {
        $this
            ->from(
                '/_test/product-performance-report',
            )
            ->get(
                '/_test/product-performance-report?timezone=Invalid%2FZone',
            )
            ->assertRedirect(
                '/_test/product-performance-report',
            )
            ->assertSessionHasErrors(
                'timezone',
            );
    }

    public function test_per_page_outside_allowed_range_is_rejected(): void
    {
        $this
            ->from(
                '/_test/product-performance-report',
            )
            ->get(
                '/_test/product-performance-report?per_page=500',
            )
            ->assertRedirect(
                '/_test/product-performance-report',
            )
            ->assertSessionHasErrors(
                'per_page',
            );
    }

    public function test_page_must_be_positive(): void
    {
        $this
            ->from(
                '/_test/product-performance-report',
            )
            ->get(
                '/_test/product-performance-report?page=0',
            )
            ->assertRedirect(
                '/_test/product-performance-report',
            )
            ->assertSessionHasErrors(
                'page',
            );
    }

    public function test_non_custom_preset_does_not_serialize_stale_dates(): void
    {
        $response =
            $this->get(
                '/_test/product-performance-report?'
                .http_build_query([
                    'preset' => 'this_month',

                    'from' => '2020-01-01',

                    'to' => '2020-01-31',
                ]),
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'query.preset',
                'this_month',
            )
            ->assertJsonMissingPath(
                'query.from',
            )
            ->assertJsonMissingPath(
                'query.to',
            );
    }
}
