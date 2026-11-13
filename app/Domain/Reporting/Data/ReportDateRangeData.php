<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

use App\Domain\Reporting\Enums\ReportDatePreset;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

final readonly class ReportDateRangeData
{
    public function __construct(
        public ReportDatePreset $preset,

        public CarbonImmutable $fromInclusive,

        public CarbonImmutable $toExclusive,

        public string $timezone,
    ) {
        if (
            $this->toExclusive
                ->lessThanOrEqualTo(
                    $this->fromInclusive,
                )
        ) {
            throw new InvalidArgumentException(
                'Report date range end must be after its start.',
            );
        }
    }

    /**
     * Date suitable for a date input.
     */
    public function fromDate(): string
    {
        return $this
            ->fromInclusive
            ->setTimezone(
                $this->timezone,
            )
            ->toDateString();
    }

    /**
     * Inclusive display date suitable for a date input.
     *
     * Internally toExclusive remains exclusive.
     */
    public function toDate(): string
    {
        return $this
            ->toExclusive
            ->setTimezone(
                $this->timezone,
            )
            ->subDay()
            ->toDateString();
    }

    /**
     * @return array{
     *     preset: string,
     *     preset_label: string,
     *     from: string,
     *     to: string,
     *     timezone: string
     * }
     */
    public function toArray(): array
    {
        return [
            'preset' => $this->preset->value,

            'preset_label' => $this->preset->label(),

            'from' => $this->fromDate(),

            'to' => $this->toDate(),

            'timezone' => $this->timezone,
        ];
    }
}
