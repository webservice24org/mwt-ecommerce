<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Pdf;

use App\Domain\Reporting\Data\Charts\ReportChartData;
use App\Domain\Reporting\Data\Charts\ReportChartPointData;
use App\Domain\Reporting\Data\Charts\ReportChartSeriesData;

final class SalesAnalyticsPdfChartRenderer
{
    private const WIDTH = 900;

    private const HEIGHT = 280;

    private const LEFT = 68;

    private const RIGHT = 24;

    private const TOP = 26;

    private const BOTTOM = 48;

    /**
     * @var list<string>
     */
    private const COLORS = [
        '#2563eb',
        '#10b981',
        '#f59e0b',
        '#8b5cf6',
        '#ef4444',
    ];

    public function render(
        ReportChartData $chart,
    ): string {
        if ($chart->points === []) {
            return $this->emptyChart(
                $chart->title,
            );
        }

        return match ($chart->type) {
            'line' => $this->lineChart(
                $chart,
            ),

            'bar' => $this->barChart(
                $chart,
            ),

            default => $this->emptyChart(
                $chart->title,
            ),
        };
    }

    private function lineChart(
        ReportChartData $chart,
    ): string {
        $width =
            self::WIDTH;

        $height =
            self::HEIGHT;

        $plotWidth =
            $width -
            self::LEFT -
            self::RIGHT;

        $plotHeight =
            $height -
            self::TOP -
            self::BOTTOM;

        $max =
            $this->maxValue(
                $chart,
            );

        $svg =
            $this->svgStart(
                $width,
                $height,
            );

        $svg .=
            $this->grid(
                max: $max,
                plotWidth: $plotWidth,
                plotHeight: $plotHeight,
                chart: $chart,
            );

        $pointCount =
            count(
                $chart->points,
            );

        foreach (
            $chart->series as $seriesIndex => $series
        ) {
            $coordinates =
                [];

            foreach (
                $chart->points as $pointIndex => $point
            ) {
                $value =
                    $this->numericValue(
                        $point,
                        $series,
                    );

                $x =
                    $pointCount <=
                    1
                    ? self::LEFT +
                    ($plotWidth / 2)
                    : self::LEFT +
                    (
                        $plotWidth *
                        $pointIndex /
                        (
                            $pointCount -
                            1
                        )
                    );

                $y =
                    self::TOP +
                    $plotHeight -
                    (
                        $plotHeight *
                        $value /
                        $max
                    );

                $coordinates[] =
                    sprintf(
                        '%.2f,%.2f',
                        $x,
                        $y,
                    );
            }

            $color =
                self::COLORS[
                    $seriesIndex %
                    count(
                        self::COLORS,
                    )
                ];

            $svg .=
                '<polyline '.
                'fill="none" '.
                'stroke="'.
                $color.
                '" '.
                'stroke-width="3" '.
                'stroke-linecap="round" '.
                'stroke-linejoin="round" '.
                'points="'.
                implode(
                    ' ',
                    $coordinates,
                ).
                '" />';

            foreach (
                $coordinates as $coordinate
            ) {
                [
                    $cx,
                    $cy,
                ] =
                    explode(
                        ',',
                        $coordinate,
                    );

                $svg .=
                    '<circle cx="'.
                    $cx.
                    '" cy="'.
                    $cy.
                    '" r="3.5" fill="'.
                    $color.
                    '" />';
            }
        }

        $svg .=
            $this->xLabels(
                $chart,
                $plotWidth,
            );

        $svg .=
            $this->legend(
                $chart,
            );

        $svg .=
            '</svg>';

        return $svg;
    }

    private function barChart(
        ReportChartData $chart,
    ): string {
        $width =
            self::WIDTH;

        $height =
            self::HEIGHT;

        $plotWidth =
            $width -
            self::LEFT -
            self::RIGHT;

        $plotHeight =
            $height -
            self::TOP -
            self::BOTTOM;

        $primarySeries =
            $chart->series[0] ??
            null;

        if (
            ! $primarySeries instanceof ReportChartSeriesData
        ) {
            return $this->emptyChart(
                $chart->title,
            );
        }

        $max =
            1;

        foreach (
            $chart->points as $point
        ) {
            $max =
                max(
                    $max,
                    $this->numericValue(
                        $point,
                        $primarySeries,
                    ),
                );
        }

        $svg =
            $this->svgStart(
                $width,
                $height,
            );

        $svg .=
            $this->grid(
                max: $max,
                plotWidth: $plotWidth,
                plotHeight: $plotHeight,
                chart: $chart,
            );

        $count =
            count(
                $chart->points,
            );

        $slotWidth =
            $plotWidth /
            max(
                1,
                $count,
            );

        $barWidth =
            min(
                72,
                $slotWidth *
                0.58,
            );

        foreach (
            $chart->points as $index => $point
        ) {
            $value =
                $this->numericValue(
                    $point,
                    $primarySeries,
                );

            $barHeight =
                $plotHeight *
                $value /
                $max;

            $x =
                self::LEFT +
                ($slotWidth * $index) +
                (
                    (
                        $slotWidth -
                        $barWidth
                    ) /
                    2
                );

            $y =
                self::TOP +
                $plotHeight -
                $barHeight;

            $svg .=
                '<rect '.
                'x="'.
                sprintf(
                    '%.2f',
                    $x,
                ).
                '" y="'.
                sprintf(
                    '%.2f',
                    $y,
                ).
                '" width="'.
                sprintf(
                    '%.2f',
                    $barWidth,
                ).
                '" height="'.
                sprintf(
                    '%.2f',
                    $barHeight,
                ).
                '" rx="5" fill="'.
                self::COLORS[0].
                '" />';

            $orders =
                $point
                    ->values[
                    'orders_count'
                ] ??
                null;

            if (
                is_int(
                    $orders,
                ) ||
                is_float(
                    $orders,
                )
            ) {
                $svg .=
                    '<text '.
                    'x="'.
                    sprintf(
                        '%.2f',
                        $x +
                        ($barWidth / 2),
                    ).
                    '" y="'.
                    sprintf(
                        '%.2f',
                        max(
                            14,
                            $y - 6,
                        ),
                    ).
                    '" text-anchor="middle" '.
                    'font-size="10" '.
                    'fill="#475569">'.
                    $this->escape(
                        number_format(
                            (float)
                            $orders,
                            0,
                        ).
                        ' orders',
                    ).
                    '</text>';
            }
        }

        $svg .=
            $this->xLabels(
                $chart,
                $plotWidth,
            );

        $svg .=
            '</svg>';

        return $svg;
    }

    private function grid(
        int|float $max,
        int|float $plotWidth,
        int|float $plotHeight,
        ReportChartData $chart,
    ): string {
        $svg =
            '';

        for (
            $step = 0;
            $step <= 4;
            $step++
        ) {
            $ratio =
                $step /
                4;

            $y =
                self::TOP +
                $plotHeight -
                (
                    $plotHeight *
                    $ratio
                );

            $value =
                $max *
                $ratio;

            $svg .=
                '<line '.
                'x1="'.
                self::LEFT.
                '" y1="'.
                sprintf(
                    '%.2f',
                    $y,
                ).
                '" x2="'.
                (
                    self::LEFT +
                    $plotWidth
                ).
                '" y2="'.
                sprintf(
                    '%.2f',
                    $y,
                ).
                '" stroke="#e2e8f0" '.
                'stroke-width="1" />';

            $svg .=
                '<text '.
                'x="'.
                (
                    self::LEFT -
                    10
                ).
                '" y="'.
                sprintf(
                    '%.2f',
                    $y + 4,
                ).
                '" text-anchor="end" '.
                'font-size="10" '.
                'fill="#64748b">'.
                $this->escape(
                    $this->axisValue(
                        $value,
                        $chart,
                    ),
                ).
                '</text>';
        }

        return $svg;
    }

    private function xLabels(
        ReportChartData $chart,
        int|float $plotWidth,
    ): string {
        $count =
            count(
                $chart->points,
            );

        if ($count === 0) {
            return '';
        }

        $svg =
            '';

        $maxLabels =
            8;

        $step =
            max(
                1,
                (int)
                ceil(
                    $count /
                    $maxLabels,
                ),
            );

        foreach (
            $chart->points as $index => $point
        ) {
            if (
                $index %
                $step !==
                0 &&
                $index !==
                $count - 1
            ) {
                continue;
            }

            $x =
                $count <=
                1
                ? self::LEFT +
                ($plotWidth / 2)
                : self::LEFT +
                (
                    $plotWidth *
                    $index /
                    (
                        $count -
                        1
                    )
                );

            $svg .=
                '<text '.
                'x="'.
                sprintf(
                    '%.2f',
                    $x,
                ).
                '" y="'.
                (
                    self::HEIGHT -
                    18
                ).
                '" text-anchor="middle" '.
                'font-size="9" '.
                'fill="#64748b">'.
                $this->escape(
                    $this->truncate(
                        $point->label,
                        16,
                    ),
                ).
                '</text>';
        }

        return $svg;
    }

    private function legend(
        ReportChartData $chart,
    ): string {
        $svg =
            '';

        $x =
            self::LEFT;

        foreach (
            $chart->series as $index => $series
        ) {
            $color =
                self::COLORS[
                    $index %
                    count(
                        self::COLORS,
                    )
                ];

            $svg .=
                '<rect x="'.
                $x.
                '" y="5" width="10" height="10" rx="2" fill="'.
                $color.
                '" />';

            $svg .=
                '<text x="'.
                ($x + 15).
                '" y="14" font-size="10" fill="#475569">'.
                $this->escape(
                    $series->label,
                ).
                '</text>';

            $x +=
                120;
        }

        return $svg;
    }

    private function maxValue(
        ReportChartData $chart,
    ): int|float {
        $max =
            1;

        foreach (
            $chart->points as $point
        ) {
            foreach (
                $chart->series as $series
            ) {
                $max =
                    max(
                        $max,
                        $this->numericValue(
                            $point,
                            $series,
                        ),
                    );
            }
        }

        return $max;
    }

    private function numericValue(
        ReportChartPointData $point,
        ReportChartSeriesData $series,
    ): int|float {
        $value =
            $point
                ->values[
                $series->key
            ] ??
            0;

        if (
            ! is_int(
                $value,
            ) &&
            ! is_float(
                $value,
            )
        ) {
            return 0;
        }

        return $value;
    }

    private function axisValue(
        int|float $value,
        ReportChartData $chart,
    ): string {
        $money =
            false;

        foreach (
            $chart->series as $series
        ) {
            if (
                $series->valueType ===
                'money'
            ) {
                $money =
                    true;

                break;
            }
        }

        if ($money) {
            return 'BDT '.
                $this->compact(
                    $value /
                    100,
                );
        }

        return $this->compact(
            $value,
        );
    }

    private function compact(
        int|float $value,
    ): string {
        $absolute =
            abs(
                $value,
            );

        if (
            $absolute >=
            1_000_000
        ) {
            return number_format(
                $value /
                1_000_000,
                1,
            ).
                'M';
        }

        if (
            $absolute >=
            1_000
        ) {
            return number_format(
                $value /
                1_000,
                1,
            ).
                'K';
        }

        return number_format(
            $value,
            0,
        );
    }

    private function emptyChart(
        string $title,
    ): string {
        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 180" width="900" height="180">
                <rect width="900" height="180" rx="8" fill="#f8fafc"/>
                <text x="450" y="82" text-anchor="middle" font-size="15" fill="#334155">%s</text>
                <text x="450" y="108" text-anchor="middle" font-size="12" fill="#64748b">No data available for the selected filters.</text>
            </svg>',
            $this->escape(
                $title,
            ),
        );
    }

    private function svgStart(
        int $width,
        int $height,
    ): string {
        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" width="%d" height="%d">',
            $width,
            $height,
            $width,
            $height,
        );
    }

    private function truncate(
        string $value,
        int $length,
    ): string {
        if (
            mb_strlen(
                $value,
            ) <=
            $length
        ) {
            return $value;
        }

        return mb_substr(
            $value,
            0,
            $length - 1,
        ).
            '…';
    }

    private function escape(
        string $value,
    ): string {
        return htmlspecialchars(
            $value,
            ENT_QUOTES |
            ENT_SUBSTITUTE,
            'UTF-8',
        );
    }
}
