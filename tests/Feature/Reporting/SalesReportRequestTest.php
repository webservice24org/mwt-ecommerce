<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Enums\ReportDatePreset;
use App\Domain\Reporting\Services\ReportDateRangeFactory;
use App\Http\Requests\Admin\SalesReportRequest;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class SalesReportRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(
            'web',
        )
            ->get(
                '/_test/sales-report',
                function (SalesReportRequest $request, ReportDateRangeFactory $dateRanges) {
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
        $response =
            $this->get(
                '/_test/sales-report',
            );

        $response
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

    public function test_custom_range_is_parsed_into_filter_contract(): void
    {
        $response =
            $this->get(
                '/_test/sales-report?'
                .http_build_query(
                    [
                        'preset' => 'custom',

                        'from' => '2026-11-01',

                        'to' => '2026-11-30',

                        'timezone' => 'Asia/Dhaka',
                    ],
                ),
            );

        $response
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
                'filters.range.timezone',
                'Asia/Dhaka',
            )
            ->assertJsonPath(
                'query.from',
                '2026-11-01',
            )
            ->assertJsonPath(
                'query.to',
                '2026-11-30',
            );
    }

    public function test_status_filters_are_parsed_into_enums(): void
    {
        $response =
            $this->get(
                '/_test/sales-report?'
                .http_build_query(
                    [
                        'status' => [
                            OrderStatus::Processing
                                ->value,

                            OrderStatus::Completed
                                ->value,
                        ],
                    ],
                ),
            );

        $response
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
            )
            ->assertJsonPath(
                'query.status.1',
                'completed',
            );
    }

    public function test_payment_status_filters_are_parsed_into_enums(): void
    {
        $response =
            $this->get(
                '/_test/sales-report?'
                .http_build_query(
                    [
                        'payment_status' => [
                            PaymentStatus::Paid
                                ->value,

                            PaymentStatus::Due
                                ->value,
                        ],
                    ],
                ),
            );

        $response
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
        $response =
            $this->get(
                '/_test/sales-report?'
                .http_build_query(
                    [
                        'search' => '   ORD-123   ',
                    ],
                ),
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'filters.search',
                'ORD-123',
            )
            ->assertJsonPath(
                'query.search',
                'ORD-123',
            );
    }

    public function test_blank_search_is_removed_from_normalized_query(): void
    {
        $response =
            $this->get(
                '/_test/sales-report?'
                .http_build_query(
                    [
                        'search' => '   ',
                    ],
                ),
            );

        $response
            ->assertOk()
            ->assertJsonMissingPath(
                'query.search',
            )
            ->assertJsonPath(
                'filters.search',
                null,
            );
    }

    public function test_page_and_per_page_are_parsed(): void
    {
        $response =
            $this->get(
                '/_test/sales-report?'
                .http_build_query(
                    [
                        'page' => 3,

                        'per_page' => 50,
                    ],
                ),
            );

        $response
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
                '/_test/sales-report',
            )
            ->get(
                '/_test/sales-report?'
                .http_build_query(
                    [
                        'status' => [
                            'not-real',
                        ],
                    ],
                ),
            )
            ->assertRedirect(
                '/_test/sales-report',
            )
            ->assertSessionHasErrors([
                'status.0',
            ]);
    }

    public function test_invalid_payment_status_is_rejected(): void
    {
        $this
            ->from(
                '/_test/sales-report',
            )
            ->get(
                '/_test/sales-report?'
                .http_build_query(
                    [
                        'payment_status' => [
                            'not-real',
                        ],
                    ],
                ),
            )
            ->assertRedirect(
                '/_test/sales-report',
            )
            ->assertSessionHasErrors([
                'payment_status.0',
            ]);
    }

    public function test_invalid_preset_is_rejected(): void
    {
        $this
            ->from(
                '/_test/sales-report',
            )
            ->get(
                '/_test/sales-report?preset=nope',
            )
            ->assertRedirect(
                '/_test/sales-report',
            )
            ->assertSessionHasErrors([
                'preset',
            ]);
    }

    public function test_custom_range_requires_both_dates(): void
    {
        $this
            ->from(
                '/_test/sales-report',
            )
            ->get(
                '/_test/sales-report?preset=custom',
            )
            ->assertRedirect(
                '/_test/sales-report',
            )
            ->assertSessionHasErrors([
                'from',
                'to',
            ]);
    }

    public function test_reverse_custom_range_is_rejected(): void
    {
        $this
            ->from(
                '/_test/sales-report',
            )
            ->get(
                '/_test/sales-report?'
                .http_build_query(
                    [
                        'preset' => 'custom',

                        'from' => '2026-11-20',

                        'to' => '2026-11-10',
                    ],
                ),
            )
            ->assertRedirect(
                '/_test/sales-report',
            )
            ->assertSessionHasErrors([
                'to',
            ]);
    }

    public function test_invalid_timezone_is_rejected(): void
    {
        $this
            ->from(
                '/_test/sales-report',
            )
            ->get(
                '/_test/sales-report?timezone=Invalid%2FZone',
            )
            ->assertRedirect(
                '/_test/sales-report',
            )
            ->assertSessionHasErrors([
                'timezone',
            ]);
    }

    public function test_per_page_outside_allowed_range_is_rejected(): void
    {
        $this
            ->from(
                '/_test/sales-report',
            )
            ->get(
                '/_test/sales-report?per_page=500',
            )
            ->assertRedirect(
                '/_test/sales-report',
            )
            ->assertSessionHasErrors([
                'per_page',
            ]);
    }

    public function test_page_must_be_positive(): void
    {
        $this
            ->from(
                '/_test/sales-report',
            )
            ->get(
                '/_test/sales-report?page=0',
            )
            ->assertRedirect(
                '/_test/sales-report',
            )
            ->assertSessionHasErrors([
                'page',
            ]);
    }

    public function test_non_custom_preset_does_not_serialize_stale_from_and_to_dates(): void
    {
        $response =
            $this->get(
                '/_test/sales-report?'
                .http_build_query(
                    [
                        'preset' => 'this_month',

                        'from' => '2020-01-01',

                        'to' => '2020-01-31',
                    ],
                ),
            );

        $response
            ->assertOk()
            ->assertJsonMissingPath(
                'query.from',
            )
            ->assertJsonMissingPath(
                'query.to',
            );
    }
}
