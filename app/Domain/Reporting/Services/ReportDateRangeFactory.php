<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Services;

use App\Domain\Reporting\Data\ReportDateRangeData;
use App\Domain\Reporting\Enums\ReportDatePreset;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Validation\ValidationException;

final class ReportDateRangeFactory
{
    public function make(
        ReportDatePreset $preset,
        ?string $from = null,
        ?string $to = null,
        ?string $timezone = null,
        DateTimeInterface|string|null $now = null,
    ): ReportDateRangeData {
        $timezone =
            $this->normalizeTimezone(
                $timezone,
            );

        $reference =
            $this->referenceTime(
                now: $now,

                timezone: $timezone,
            );

        return match ($preset) {
            ReportDatePreset::Today => $this->today(
                reference: $reference,

                timezone: $timezone,
            ),

            ReportDatePreset::Last7Days => $this->lastDays(
                preset: $preset,

                days: 7,

                reference: $reference,

                timezone: $timezone,
            ),

            ReportDatePreset::Last30Days => $this->lastDays(
                preset: $preset,

                days: 30,

                reference: $reference,

                timezone: $timezone,
            ),

            ReportDatePreset::ThisMonth => $this->thisMonth(
                reference: $reference,

                timezone: $timezone,
            ),

            ReportDatePreset::PreviousMonth => $this->previousMonth(
                reference: $reference,

                timezone: $timezone,
            ),

            ReportDatePreset::Custom => $this->custom(
                from: $from,

                to: $to,

                timezone: $timezone,
            ),
        };
    }

    private function today(
        CarbonImmutable $reference,
        string $timezone,
    ): ReportDateRangeData {
        $from =
            $reference
                ->startOfDay();

        return new ReportDateRangeData(
            preset: ReportDatePreset::Today,

            fromInclusive: $from,

            toExclusive: $from->addDay(),

            timezone: $timezone,
        );
    }

    private function lastDays(
        ReportDatePreset $preset,
        int $days,
        CarbonImmutable $reference,
        string $timezone,
    ): ReportDateRangeData {
        /*
         * "Last N days" includes today.
         *
         * Example:
         * Today = Nov 13
         * Last 7 days = Nov 7 through Nov 13.
         */
        $toExclusive =
            $reference
                ->startOfDay()
                ->addDay();

        $fromInclusive =
            $toExclusive
                ->subDays(
                    $days,
                );

        return new ReportDateRangeData(
            preset: $preset,

            fromInclusive: $fromInclusive,

            toExclusive: $toExclusive,

            timezone: $timezone,
        );
    }

    private function thisMonth(
        CarbonImmutable $reference,
        string $timezone,
    ): ReportDateRangeData {
        $fromInclusive =
            $reference
                ->startOfMonth();

        return new ReportDateRangeData(
            preset: ReportDatePreset::ThisMonth,

            fromInclusive: $fromInclusive,

            toExclusive: $fromInclusive
                ->addMonth(),

            timezone: $timezone,
        );
    }

    private function previousMonth(
        CarbonImmutable $reference,
        string $timezone,
    ): ReportDateRangeData {
        $toExclusive =
            $reference
                ->startOfMonth();

        $fromInclusive =
            $toExclusive
                ->subMonth();

        return new ReportDateRangeData(
            preset: ReportDatePreset::PreviousMonth,

            fromInclusive: $fromInclusive,

            toExclusive: $toExclusive,

            timezone: $timezone,
        );
    }

    private function custom(
        ?string $from,
        ?string $to,
        string $timezone,
    ): ReportDateRangeData {
        if (
            ! is_string(
                $from,
            )
            || trim(
                $from,
            ) === ''
            || ! is_string(
                $to,
            )
            || trim(
                $to,
            ) === ''
        ) {
            throw ValidationException::withMessages([
                'date_range' => 'Both from and to dates are required for a custom report range.',
            ]);
        }

        $fromInclusive =
            $this->parseDate(
                value: trim(
                    $from,
                ),

                timezone: $timezone,
            );

        $toInclusive =
            $this->parseDate(
                value: trim(
                    $to,
                ),

                timezone: $timezone,
            );

        if (
            $toInclusive
                ->lessThan(
                    $fromInclusive,
                )
        ) {
            throw ValidationException::withMessages([
                'date_range' => 'The report end date must be on or after the start date.',
            ]);
        }

        return new ReportDateRangeData(
            preset: ReportDatePreset::Custom,

            fromInclusive: $fromInclusive
                ->startOfDay(),

            toExclusive: $toInclusive
                ->startOfDay()
                ->addDay(),

            timezone: $timezone,
        );
    }

    private function referenceTime(
        DateTimeInterface|string|null $now,
        string $timezone,
    ): CarbonImmutable {
        if (
            $now instanceof DateTimeInterface
        ) {
            return CarbonImmutable::instance(
                $now,
            )->setTimezone(
                $timezone,
            );
        }

        if (
            is_string(
                $now,
            )
            && trim(
                $now,
            ) !== ''
        ) {
            return CarbonImmutable::parse(
                $now,
                $timezone,
            )->setTimezone(
                $timezone,
            );
        }

        return CarbonImmutable::now(
            $timezone,
        );
    }

    private function normalizeTimezone(
        ?string $timezone,
    ): string {
        $timezone =
            is_string(
                $timezone,
            )
            && trim(
                $timezone,
            ) !== ''
            ? trim(
                $timezone,
            )
            : (string) config(
                'app.timezone',
                'UTC',
            );

        try {
            new \DateTimeZone(
                $timezone,
            );
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'timezone' => 'The reporting timezone is invalid.',
            ]);
        }

        return $timezone;
    }

    /**
     * @throws ValidationException
     */
    private function parseDate(
        string $value,
        string $timezone,
    ): CarbonImmutable {
        $date =
            CarbonImmutable::createFromFormat(
                '!Y-m-d',
                $value,
                $timezone,
            );

        $errors =
            CarbonImmutable::getLastErrors();

        if (
            ! $date instanceof CarbonImmutable
            || (
                is_array(
                    $errors,
                )
                && (
                    $errors[
                        'warning_count'
                    ] > 0
                    || $errors[
                        'error_count'
                    ] > 0
                )
            )
            || $date->format(
                'Y-m-d',
            ) !== $value
        ) {
            throw ValidationException::withMessages([
                'date_range' => 'The custom report range contains an invalid date.',
            ]);
        }

        return $date;
    }
}
