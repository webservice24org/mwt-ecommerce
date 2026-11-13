<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Enums;

enum ReportDatePreset: string
{
    case Today = 'today';

    case Last7Days = 'last_7_days';

    case Last30Days = 'last_30_days';

    case ThisMonth = 'this_month';

    case PreviousMonth = 'previous_month';

    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Today => 'Today',

            self::Last7Days => 'Last 7 days',

            self::Last30Days => 'Last 30 days',

            self::ThisMonth => 'This month',

            self::PreviousMonth => 'Previous month',

            self::Custom => 'Custom range',
        };
    }

    /**
     * @return list<array{
     *     value: string,
     *     label: string
     * }>
     */
    public static function options(): array
    {
        return array_map(
            static fn (
                self $preset,
            ): array => [
                'value' => $preset->value,

                'label' => $preset->label(),
            ],
            self::cases(),
        );
    }
}
