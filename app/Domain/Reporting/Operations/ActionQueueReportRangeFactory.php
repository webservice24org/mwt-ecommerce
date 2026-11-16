<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Operations;

use App\Domain\Reporting\Data\ReportDateRangeData;
use App\Domain\Reporting\Enums\ReportDatePreset;
use Carbon\CarbonImmutable;

final class ActionQueueReportRangeFactory
{
    public function make(): ReportDateRangeData
    {
        $timezone =
            (string) config(
                'app.timezone',
                'UTC',
            );

        $days =
            max(
                1,
                (int) config(
                    'reporting.action_queue.lookback_days',
                    90,
                ),
            );

        $now =
            CarbonImmutable::now(
                $timezone,
            );

        return new ReportDateRangeData(
            preset: ReportDatePreset::Custom,

            fromInclusive: $now
                ->subDays(
                    $days - 1,
                )
                ->startOfDay(),

            toExclusive: $now
                ->addDay()
                ->startOfDay(),

            timezone: $timezone,
        );
    }
}
