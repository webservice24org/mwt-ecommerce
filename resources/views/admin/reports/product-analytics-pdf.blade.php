<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <title>
        Product Analytics Report
    </title>

    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        * {
            box-sizing: border-box;
        }

        html {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            margin: 0;
            background: #ffffff;
            color: #0f172a;
            font-family:
                Arial,
                Helvetica,
                sans-serif;
            font-size: 11px;
        }

        .header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 18px;
        }

        .title {
            margin: 0;
            font-size: 24px;
            line-height: 1.2;
            font-weight: 700;
        }

        .subtitle {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 11px;
        }

        .range {
            text-align: right;
            color: #475569;
            line-height: 1.6;
        }

        .kpis {
            display: grid;
            grid-template-columns:
                repeat(4,
                    1fr);
            gap: 10px;
            margin-bottom: 16px;
        }

        .kpi {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #ffffff;
            padding: 12px;
            break-inside: avoid;
        }

        .kpi-label {
            color: #64748b;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
        }

        .kpi-value {
            margin-top: 5px;
            color: #0f172a;
            font-size: 18px;
            font-weight: 700;
        }

        .kpi-note {
            margin-top: 4px;
            color: #64748b;
            font-size: 9px;
        }

        .chart-grid {
            display: grid;
            grid-template-columns:
                repeat(2,
                    minmax(0,
                        1fr));
            gap: 12px;
            margin-top: 12px;
        }

        .chart-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #ffffff;
            padding: 10px;
            break-inside: avoid;
        }

        .chart-title {
            margin: 0;
            font-size: 12px;
            font-weight: 700;
        }

        .chart-description {
            margin: 3px 0 8px;
            color: #64748b;
            font-size: 9px;
            line-height: 1.4;
        }

        .chart {
            width: 100%;
            overflow: hidden;
        }

        .chart svg {
            display: block;
            width: 100%;
            height: auto;
        }

        .summary {
            margin-top: 18px;
        }

        .summary-title {
            margin: 0 0 8px;
            font-size: 14px;
            font-weight: 700;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            display: table-header-group;
        }

        tr {
            break-inside: avoid;
        }

        th,
        td {
            border-bottom: 1px solid #e2e8f0;
            padding: 7px 8px;
            text-align: right;
            vertical-align: top;
        }

        th {
            background: #f8fafc;
            color: #475569;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
        }

        th:first-child,
        td:first-child,
        th:nth-child(2),
        td:nth-child(2) {
            text-align: left;
        }

        .historical {
            display: inline-block;
            margin-top: 3px;
            border-radius: 9999px;
            background: #fef3c7;
            color: #92400e;
            padding: 2px 6px;
            font-size: 8px;
            font-weight: 700;
        }

        .footer {
            margin-top: 14px;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            color: #94a3b8;
            font-size: 8px;
            text-align: center;
        }
    </style>
</head>

<body>
    @php
        $money = static fn(int $value): string =>
            'BDT ' .
            number_format(
                $value / 100,
                2
            );
    @endphp

    <header class="header">
        <div>
            <h1 class="title">
                Product Analytics Report
            </h1>

            <p class="subtitle">
                Product sales, revenue contribution, SKU performance and historical product analysis.
            </p>
        </div>

        <div class="range">
            <strong>
                {{ $filters->range->fromDate() }}
                -
                {{ $filters->range->toDate() }}
            </strong>

            <br>

            Timezone:
            {{ $filters->range->timezone }}
        </div>
    </header>

    <section class="kpis">
        <article class="kpi">
            <div class="kpi-label">
                Product Groups
            </div>

            <div class="kpi-value">
                {{ number_format($report->summary->productGroupsCount) }}
            </div>

            <div class="kpi-note">
                Distinct product groups
            </div>
        </article>

        <article class="kpi">
            <div class="kpi-label">
                Quantity Sold
            </div>

            <div class="kpi-value">
                {{ number_format($report->summary->quantitySold) }}
            </div>

            <div class="kpi-note">
                Total units sold
            </div>
        </article>

        <article class="kpi">
            <div class="kpi-label">
                Orders
            </div>

            <div class="kpi-value">
                {{ number_format($report->summary->ordersCount) }}
            </div>

            <div class="kpi-note">
                Distinct orders
            </div>
        </article>

        <article class="kpi">
            <div class="kpi-label">
                Gross Sales
            </div>

            <div class="kpi-value">
                {{ $money($report->summary->grossSales) }}
            </div>

            <div class="kpi-note">
                Product line revenue
            </div>
        </article>

        <article class="kpi">
            <div class="kpi-label">
                Avg. Revenue / Order
            </div>

            <div class="kpi-value">
                {{ $money($report->summary->averageRevenuePerOrder) }}
            </div>

            <div class="kpi-note">
                Gross sales per order
            </div>
        </article>

        <article class="kpi">
            <div class="kpi-label">
                Avg. Revenue / Item
            </div>

            <div class="kpi-value">
                {{ $money($report->summary->averageRevenuePerItem) }}
            </div>

            <div class="kpi-note">
                Gross sales per unit
            </div>
        </article>

        <article class="kpi">
            <div class="kpi-label">
                Current Product Revenue
            </div>

            <div class="kpi-value">
                {{ $money($report->summary->currentProductRevenue) }}
            </div>

            <div class="kpi-note">
                Linked product revenue
            </div>
        </article>

        <article class="kpi">
            <div class="kpi-label">
                Historical Product Revenue
            </div>

            <div class="kpi-value">
                {{ $money($report->summary->historicalProductRevenue) }}
            </div>

            <div class="kpi-note">
                Deleted or unlinked snapshots
            </div>
        </article>
    </section>

    <section class="chart-grid">
        <article class="chart-card">
            <h2 class="chart-title">
                {{ $charts->salesTrend->title }}
            </h2>

            <p class="chart-description">
                {{ $charts->salesTrend->description }}
            </p>

            <div class="chart">
                {!! $chartSvgs['sales_trend'] !!}
            </div>
        </article>

        <article class="chart-card">
            <h2 class="chart-title">
                {{ $charts->currentVsHistoricalRevenue->title }}
            </h2>

            <p class="chart-description">
                {{ $charts->currentVsHistoricalRevenue->description }}
            </p>

            <div class="chart">
                {!! $chartSvgs['current_vs_historical_revenue'] !!}
            </div>
        </article>
    </section>

    <section class="chart-grid">
        <article class="chart-card">
            <h2 class="chart-title">
                {{ $charts->topProductsByRevenue->title }}
            </h2>

            <p class="chart-description">
                {{ $charts->topProductsByRevenue->description }}
            </p>

            <div class="chart">
                {!! $chartSvgs['top_products_by_revenue'] !!}
            </div>
        </article>

        <article class="chart-card">
            <h2 class="chart-title">
                {{ $charts->topProductsByQuantity->title }}
            </h2>

            <p class="chart-description">
                {{ $charts->topProductsByQuantity->description }}
            </p>

            <div class="chart">
                {!! $chartSvgs['top_products_by_quantity'] !!}
            </div>
        </article>
    </section>

    <section class="chart-grid">
        <article class="chart-card">
            <h2 class="chart-title">
                {{ $charts->skuPerformance->title }}
            </h2>

            <p class="chart-description">
                {{ $charts->skuPerformance->description }}
            </p>

            <div class="chart">
                {!! $chartSvgs['sku_performance'] !!}
            </div>
        </article>

        <article class="chart-card">
            <h2 class="chart-title">
                {{ $charts->revenueShareByProduct->title }}
            </h2>

            <p class="chart-description">
                {{ $charts->revenueShareByProduct->description }}
            </p>

            <div class="chart">
                {!! $chartSvgs['revenue_share_by_product'] !!}
            </div>
        </article>
    </section>

    <section class="summary">
        <h2 class="summary-title">
            Product Performance Summary
        </h2>

        <table>
            <thead>
                <tr>
                    <th>
                        Product
                    </th>

                    <th>
                        SKU
                    </th>

                    <th>
                        Quantity
                    </th>

                    <th>
                        Orders
                    </th>

                    <th>
                        Gross Sales
                    </th>

                    <th>
                        Avg. Revenue / Item
                    </th>
                </tr>
            </thead>

            <tbody>
                @forelse ($report->products as $row)
                    <tr>
                        <td>
                            {{ $row->productName }}

                            @if ($row->historical)
                                <br>

                                <span class="historical">
                                    Historical
                                </span>
                            @endif
                        </td>

                        <td>
                            {{ $row->sku ?? '—' }}
                        </td>

                        <td>
                            {{ number_format($row->quantitySold) }}
                        </td>

                        <td>
                            {{ number_format($row->ordersCount) }}
                        </td>

                        <td>
                            {{ $money($row->grossSales) }}
                        </td>

                        <td>
                            {{ $money($row->averageRevenuePerItem) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;color:#64748b;">
                            No product analytics data for the selected filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <footer class="footer">
        Generated by
        {{ config('app.name') }}
        -
        Product Analytics
    </footer>
</body>

</html>