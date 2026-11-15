<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data\Charts;

final readonly class ReportChartData
{
    /**
     * @param  list<ReportChartSeriesData>  $series
     * @param  list<ReportChartPointData>  $points
     */
    public function __construct(
        public string $key,
        public string $title,
        public string $description,
        public string $type,
        public array $series,
        public array $points,
    ) {}

    /**
     * @return array{
     *     key: string,
     *     title: string,
     *     description: string,
     *     type: string,
     *     series: list<array{
     *         key: string,
     *         label: string,
     *         value_type: string
     *     }>,
     *     points: list<array{
     *         key: string,
     *         label: string,
     *         values: array<string, int|float|string|null>
     *     }>
     * }
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,

            'title' => $this->title,

            'description' => $this->description,

            'type' => $this->type,

            'series' => array_map(
                static fn (
                    ReportChartSeriesData $series,
                ): array => $series->toArray(),
                $this->series,
            ),

            'points' => array_map(
                static fn (
                    ReportChartPointData $point,
                ): array => $point->toArray(),
                $this->points,
            ),
        ];
    }
}
