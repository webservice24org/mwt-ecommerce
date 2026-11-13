<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Reporting\Data\ReportDateRangeData;
use App\Domain\Reporting\Enums\ReportDatePreset;
use App\Domain\Reporting\Services\ReportDateRangeFactory;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class ReportDateRangeTest extends TestCase
{
    private ReportDateRangeFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory =
            app(
                ReportDateRangeFactory::class,
            );
    }

    public function test_today_range_uses_start_of_local_day_and_exclusive_next_day(): void
    {
        $range =
            $this->factory->make(
                preset: ReportDatePreset::Today,

                timezone: 'Asia/Dhaka',

                now: '2026-11-13 21:00:00',
            );

        $this->assertSame(
            '2026-11-13 00:00:00',
            $range
                ->fromInclusive
                ->format(
                    'Y-m-d H:i:s',
                ),
        );

        $this->assertSame(
            '2026-11-14 00:00:00',
            $range
                ->toExclusive
                ->format(
                    'Y-m-d H:i:s',
                ),
        );

        $this->assertSame(
            'Asia/Dhaka',
            $range->timezone,
        );
    }

    public function test_last_seven_days_includes_today(): void
    {
        $range =
            $this->factory->make(
                preset: ReportDatePreset::Last7Days,

                timezone: 'Asia/Dhaka',

                now: '2026-11-13 10:30:00',
            );

        $this->assertSame(
            '2026-11-07',
            $range
                ->fromInclusive
                ->toDateString(),
        );

        $this->assertSame(
            '2026-11-14',
            $range
                ->toExclusive
                ->toDateString(),
        );

        $this->assertSame(
            '2026-11-13',
            $range->toDate(),
        );
    }

    public function test_last_thirty_days_includes_today(): void
    {
        $range =
            $this->factory->make(
                preset: ReportDatePreset::Last30Days,

                timezone: 'Asia/Dhaka',

                now: '2026-11-13 10:30:00',
            );

        $this->assertSame(
            '2026-10-15',
            $range
                ->fromInclusive
                ->toDateString(),
        );

        $this->assertSame(
            '2026-11-14',
            $range
                ->toExclusive
                ->toDateString(),
        );
    }

    public function test_this_month_uses_exact_month_boundaries(): void
    {
        $range =
            $this->factory->make(
                preset: ReportDatePreset::ThisMonth,

                timezone: 'Asia/Dhaka',

                now: '2026-11-13 21:00:00',
            );

        $this->assertSame(
            '2026-11-01',
            $range
                ->fromInclusive
                ->toDateString(),
        );

        $this->assertSame(
            '2026-12-01',
            $range
                ->toExclusive
                ->toDateString(),
        );
    }

    public function test_previous_month_handles_month_length_correctly(): void
    {
        $range =
            $this->factory->make(
                preset: ReportDatePreset::PreviousMonth,

                timezone: 'Asia/Dhaka',

                now: '2026-03-31 12:00:00',
            );

        $this->assertSame(
            '2026-02-01',
            $range
                ->fromInclusive
                ->toDateString(),
        );

        $this->assertSame(
            '2026-03-01',
            $range
                ->toExclusive
                ->toDateString(),
        );
    }

    public function test_previous_month_handles_year_boundary(): void
    {
        $range =
            $this->factory->make(
                preset: ReportDatePreset::PreviousMonth,

                timezone: 'Asia/Dhaka',

                now: '2026-01-15 12:00:00',
            );

        $this->assertSame(
            '2025-12-01',
            $range
                ->fromInclusive
                ->toDateString(),
        );

        $this->assertSame(
            '2026-01-01',
            $range
                ->toExclusive
                ->toDateString(),
        );
    }

    public function test_custom_range_converts_inclusive_to_date_into_exclusive_boundary(): void
    {
        $range =
            $this->factory->make(
                preset: ReportDatePreset::Custom,

                from: '2026-11-01',

                to: '2026-11-13',

                timezone: 'Asia/Dhaka',
            );

        $this->assertSame(
            '2026-11-01 00:00:00',
            $range
                ->fromInclusive
                ->format(
                    'Y-m-d H:i:s',
                ),
        );

        $this->assertSame(
            '2026-11-14 00:00:00',
            $range
                ->toExclusive
                ->format(
                    'Y-m-d H:i:s',
                ),
        );

        $this->assertSame(
            '2026-11-13',
            $range->toDate(),
        );
    }

    public function test_single_day_custom_range_is_valid(): void
    {
        $range =
            $this->factory->make(
                preset: ReportDatePreset::Custom,

                from: '2026-11-13',

                to: '2026-11-13',

                timezone: 'Asia/Dhaka',
            );

        $this->assertSame(
            '2026-11-13',
            $range->fromDate(),
        );

        $this->assertSame(
            '2026-11-13',
            $range->toDate(),
        );

        $this->assertSame(
            '2026-11-14',
            $range
                ->toExclusive
                ->toDateString(),
        );
    }

    public function test_custom_range_requires_both_dates(): void
    {
        try {
            $this->factory->make(
                preset: ReportDatePreset::Custom,

                from: '2026-11-01',

                to: null,

                timezone: 'Asia/Dhaka',
            );

            $this->fail(
                'Expected validation exception was not thrown.',
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'date_range',
                $exception->errors(),
            );
        }
    }

    public function test_custom_range_rejects_reverse_dates(): void
    {
        try {
            $this->factory->make(
                preset: ReportDatePreset::Custom,

                from: '2026-11-14',

                to: '2026-11-13',

                timezone: 'Asia/Dhaka',
            );

            $this->fail(
                'Expected validation exception was not thrown.',
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'date_range',
                $exception->errors(),
            );
        }
    }

    public function test_custom_range_rejects_invalid_calendar_date(): void
    {
        try {
            $this->factory->make(
                preset: ReportDatePreset::Custom,

                from: '2026-02-31',

                to: '2026-03-01',

                timezone: 'Asia/Dhaka',
            );

            $this->fail(
                'Expected validation exception was not thrown.',
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'date_range',
                $exception->errors(),
            );
        }
    }

    public function test_invalid_timezone_is_rejected(): void
    {
        try {
            $this->factory->make(
                preset: ReportDatePreset::Today,

                timezone: 'Invalid/Timezone',
            );

            $this->fail(
                'Expected validation exception was not thrown.',
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'timezone',
                $exception->errors(),
            );
        }
    }

    public function test_range_serializes_for_frontend_filter_contract(): void
    {
        $range =
            $this->factory->make(
                preset: ReportDatePreset::Custom,

                from: '2026-11-01',

                to: '2026-11-13',

                timezone: 'Asia/Dhaka',
            );

        $this->assertSame(
            [
                'preset' => 'custom',

                'preset_label' => 'Custom range',

                'from' => '2026-11-01',

                'to' => '2026-11-13',

                'timezone' => 'Asia/Dhaka',
            ],
            $range->toArray(),
        );
    }

    public function test_date_preset_options_have_stable_frontend_contract(): void
    {
        $this->assertSame(
            [
                [
                    'value' => 'today',

                    'label' => 'Today',
                ],

                [
                    'value' => 'last_7_days',

                    'label' => 'Last 7 days',
                ],

                [
                    'value' => 'last_30_days',

                    'label' => 'Last 30 days',
                ],

                [
                    'value' => 'this_month',

                    'label' => 'This month',
                ],

                [
                    'value' => 'previous_month',

                    'label' => 'Previous month',
                ],

                [
                    'value' => 'custom',

                    'label' => 'Custom range',
                ],
            ],
            ReportDatePreset::options(),
        );
    }

    public function test_data_object_rejects_empty_or_reverse_half_open_range(): void
    {
        $this->expectException(
            \InvalidArgumentException::class,
        );

        $instant =
            now()
                ->toImmutable();

        new ReportDateRangeData(
            preset: ReportDatePreset::Today,

            fromInclusive: $instant,

            toExclusive: $instant,

            timezone: 'Asia/Dhaka',
        );
    }
}
