'use client'

import {
    Bar,
    BarChart,
    CartesianGrid,
    Legend,
    Line,
    LineChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts'

type ChartValue =
    | number
    | string
    | null

interface ChartSeries {
    key: string
    label: string
    value_type: string
}

interface ChartPoint {
    key: string
    label: string
    values: Record<
        string,
        ChartValue
    >
}

export interface ReportChart {
    key: string
    title: string
    description: string
    type:
        | 'line'
        | 'bar'
    series: ChartSeries[]
    points: ChartPoint[]
}

interface AnalyticsChartProps {
    chart: ReportChart
}

interface ChartRow {
    key: string
    label: string
    [key: string]:
        | ChartValue
}

function formatMoney(
    value: number,
): string {
    return new Intl.NumberFormat(
        'en-BD',
        {
            style:
                'currency',
            currency:
                'BDT',
            minimumFractionDigits:
                0,
            maximumFractionDigits:
                2,
        },
    ).format(
        value / 100,
    )
}

function formatAxisValue(
    value: number,
    chart: ReportChart,
): string {
    const hasMoneySeries =
        chart.series.some(
            (
                series,
            ) =>
                series.value_type ===
                'money',
        )

    if (
        hasMoneySeries
    ) {
        return new Intl.NumberFormat(
            'en-BD',
            {
                notation:
                    'compact',
                maximumFractionDigits:
                    1,
            },
        ).format(
            value / 100,
        )
    }

    return new Intl.NumberFormat(
        'en-BD',
        {
            notation:
                'compact',
            maximumFractionDigits:
                1,
        },
    ).format(
        value,
    )
}

function tooltipFormatter(
    value:
        | number
        | string,
    name:
        | number
        | string,
    chart: ReportChart,
): [
    string,
    string,
] {
    const series =
        chart.series.find(
            (
                item,
            ) =>
                item.label ===
                String(
                    name,
                ),
        )

    if (
        typeof value !==
        'number'
    ) {
        return [
            String(
                value,
            ),
            series?.label ??
                String(
                    name,
                ),
        ]
    }

    if (
        series?.value_type ===
        'money'
    ) {
        return [
            formatMoney(
                value,
            ),
            series.label,
        ]
    }

    return [
        new Intl.NumberFormat(
            'en-BD',
        ).format(
            value,
        ),
        series?.label ??
            String(
                name,
            ),
    ]
}

export default function AnalyticsChart({
    chart,
}: AnalyticsChartProps) {
    const rows:
        ChartRow[] =
        chart.points.map(
            (
                point,
            ) => ({
                key:
                    point.key,

                label:
                    point.label,

                ...point.values,
            }),
        )

    const useHorizontalBars =
        chart.type ===
            'bar' &&
        rows.length >=
            6

    return (
        <section className="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm sm:p-5">
            <div>
                <h2 className="text-base font-semibold text-neutral-900">
                    {
                        chart.title
                    }
                </h2>

                <p className="mt-1 text-sm leading-5 text-neutral-500">
                    {
                        chart.description
                    }
                </p>
            </div>

            {rows.length ===
            0 ? (
                <div
                    role="status"
                    className="flex min-h-72 items-center justify-center text-sm text-neutral-500"
                >
                    No chart data
                    available for
                    the selected
                    filters.
                </div>
            ) : (
                <div
                    className="mt-5 h-72 w-full sm:h-80 lg:h-96"
                    aria-label={
                        chart.title
                    }
                >
                    <ResponsiveContainer
                        width="100%"
                        height="100%"
                    >
                        {chart.type ===
                        'line' ? (
                            <LineChart
                                data={
                                    rows
                                }
                                margin={{
                                    top: 8,
                                    right: 8,
                                    left: 0,
                                    bottom:
                                        8,
                                }}
                            >
                                <CartesianGrid
                                    strokeDasharray="3 3"
                                    vertical={
                                        false
                                    }
                                />

                                <XAxis
                                    dataKey="label"
                                    tick={{
                                        fontSize:
                                            11,
                                    }}
                                    minTickGap={
                                        24
                                    }
                                />

                                <YAxis
                                    tick={{
                                        fontSize:
                                            11,
                                    }}
                                    width={
                                        68
                                    }
                                    tickFormatter={(
                                        value,
                                    ) =>
                                        formatAxisValue(
                                            Number(
                                                value,
                                            ),
                                            chart,
                                        )
                                    }
                                />

                                <Tooltip
                                    formatter={(
                                        value,
                                        name,
                                    ) =>
                                        tooltipFormatter(
                                            value as
                                                | number
                                                | string,
                                            name as
                                                | number
                                                | string,
                                            chart,
                                        )
                                    }
                                />

                                <Legend />

                                {chart.series.map(
                                    (
                                        series,
                                        index,
                                    ) => (
                                        <Line
                                            key={
                                                series.key
                                            }
                                            type="monotone"
                                            dataKey={
                                                series.key
                                            }
                                            name={
                                                series.label
                                            }
                                            stroke={
                                                index ===
                                                0
                                                    ? '#2563eb'
                                                    : '#10b981'
                                            }
                                            strokeWidth={
                                                2
                                            }
                                            dot={{
                                                r: 3,
                                            }}
                                            activeDot={{
                                                r: 5,
                                            }}
                                        />
                                    ),
                                )}
                            </LineChart>
                        ) : (
                            <BarChart
                                data={
                                    rows
                                }
                                layout={
                                    useHorizontalBars
                                        ? 'vertical'
                                        : 'horizontal'
                                }
                                margin={{
                                    top: 8,
                                    right: 8,
                                    left: useHorizontalBars
                                        ? 16
                                        : 0,
                                    bottom:
                                        8,
                                }}
                            >
                                <CartesianGrid
                                    strokeDasharray="3 3"
                                    vertical={
                                        !useHorizontalBars
                                    }
                                />

                                {useHorizontalBars ? (
                                    <>
                                        <XAxis
                                            type="number"
                                            tick={{
                                                fontSize:
                                                    11,
                                            }}
                                            tickFormatter={(
                                                value,
                                            ) =>
                                                formatAxisValue(
                                                    Number(
                                                        value,
                                                    ),
                                                    chart,
                                                )
                                            }
                                        />

                                        <YAxis
                                            type="category"
                                            dataKey="label"
                                            width={
                                                120
                                            }
                                            tick={{
                                                fontSize:
                                                    11,
                                            }}
                                        />
                                    </>
                                ) : (
                                    <>
                                        <XAxis
                                            dataKey="label"
                                            tick={{
                                                fontSize:
                                                    11,
                                            }}
                                            minTickGap={
                                                16
                                            }
                                        />

                                        <YAxis
                                            tick={{
                                                fontSize:
                                                    11,
                                            }}
                                            width={
                                                68
                                            }
                                            tickFormatter={(
                                                value,
                                            ) =>
                                                formatAxisValue(
                                                    Number(
                                                        value,
                                                    ),
                                                    chart,
                                                )
                                            }
                                        />
                                    </>
                                )}

                                <Tooltip
                                    formatter={(
                                        value,
                                        name,
                                    ) =>
                                        tooltipFormatter(
                                            value as
                                                | number
                                                | string,
                                            name as
                                                | number
                                                | string,
                                            chart,
                                        )
                                    }
                                />

                                <Legend />

                                {chart.series.map(
                                    (
                                        series,
                                        index,
                                    ) => (
                                        <Bar
                                            key={
                                                series.key
                                            }
                                            dataKey={
                                                series.key
                                            }
                                            name={
                                                series.label
                                            }
                                            fill={
                                                index ===
                                                0
                                                    ? '#2563eb'
                                                    : '#f59e0b'
                                            }
                                            radius={
                                                useHorizontalBars
                                                    ? [
                                                          0,
                                                          4,
                                                          4,
                                                          0,
                                                      ]
                                                    : [
                                                          4,
                                                          4,
                                                          0,
                                                          0,
                                                      ]
                                            }
                                        />
                                    ),
                                )}
                            </BarChart>
                        )}
                    </ResponsiveContainer>
                </div>
            )}
        </section>
    )
}
