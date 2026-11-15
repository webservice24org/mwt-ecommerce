<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <title>
        Sales Analytics Report
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
        }

        .chart-grid.three {
            grid-template-columns:
                repeat(3,
                    minmax(0,
                        1fr));
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
            margin-top: 15px;
            break-inside: avoid;
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

        th,
        td {
            border-bottom: 1px solid #e2e8f0;
            padding: 7px 8px;
            text-align: right;
        }

        th {
            background: #f8fafc;
            color: #475569;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
        }

        th:first-child,
        td:first-child {
            text-align: left;
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
                Sales Analytics Report
            </h1>

            <p class="subtitle">
                Revenue, orders, payments and operational sales performance.
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
                Total Revenue
            </div>

            <div class="kpi-value">
                {{ $money($report->summary->grandTotal) }}
            </div>

            <div class="kpi-note">
                {{ number_format($report->summary->ordersCount) }}
                orders
            </div>
        </article>

        <article class="kpi">
            <div class="kpi-label">
                Paid Revenue
            </div>

            <div class="kpi-value">
                {{ $money($report->summary->paidRevenue) }}
            </div>

            <div class="kpi-note">
                Captured revenue
            </div>
        </article>

        <article class="kpi">
            <div class="kpi-label">
                Average Order Value
            </div>

            <div class="kpi-value">
                {{ $money($report->summary->averageOrderValue) }}
            </div>

            <div class="kpi-note">
                {{ number_format($report->summary->itemsQuantity) }}
                items
            </div>
        </article>

        <article class="kpi">
            <div class="kpi-label">
                Discounts
            </div>

            <div class="kpi-value">
                {{ $money($report->summary->discountTotal) }}
            </div>

            <div class="kpi-note">
                Applied discounts
            </div>
        </article>

        <article class="kpi">
            <div class="kpi-label">
                Subtotal
            </div>

            <div class="kpi-value">
                {{ $money($report->summary->subtotal) }}
            </div>

            <div class="kpi-note">
                Before adjustments
            </div>
        </article>

        <article class="kpi">
            <div class="kpi-label">
                Delivery Revenue
            </div>

            <div class="kpi-value">
                {{ $money($report->summary->deliveryTotal) }}
            </div>

            <div class="kpi-note">
                Shipping collected
            </div>
        </article>

        <article class="kpi">
            <div class="kpi-label">
                Tax
            </div>

            <div class="kpi-value">
                {{ $money($report->summary->taxTotal) }}
            </div>

            <div class="kpi-note">
                Tax collected
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
                {{ number_format($report->summary->itemsQuantity) }}
                items sold
            </div>
        </article>
    </section>

    <section class="chart-grid">
        <article class="chart-card">
            <h2 class="chart-title">
                {{ $charts->revenueTrend->title }}
            </h2>

            <p class="chart-description">
                {{ $charts->revenueTrend->description }}
            </p>

            <div class="chart">
                {!! $chartSvgs['revenue_trend'] !!}
            </div>
        </article>

        <article class="chart-card">
            <h2 class="chart-title">
                {{ $charts->ordersTrend->title }}
            </h2>

            <p class="chart-description">
                {{ $charts->ordersTrend->description }}
            </p>

            <div class="chart">
                {!! $chartSvgs['orders_trend'] !!}
            </div>
        </article>
    </section>

    <section class="chart-grid three">
        <article class="chart-card">
            <h2 class="chart-title">
                {{ $charts->statusRevenue->title }}
            </h2>

            <p class="chart-description">
                {{ $charts->statusRevenue->description }}
            </p>

            <div class="chart">
                {!! $chartSvgs['status_revenue'] !!}
            </div>
        </article>

        <article class="chart-card">
            <h2 class="chart-title">
                {{ $charts->paymentStatusRevenue->title }}
            </h2>

            <p class="chart-description">
                {{ $charts->paymentStatusRevenue->description }}
            </p>

            <div class="chart">
                {!! $chartSvgs['payment_status_revenue'] !!}
            </div>
        </article>

        <article class="chart-card">
            <h2 class="chart-title">
                {{ $charts->paymentMethodRevenue->title }}
            </h2>

            <p class="chart-description">
                {{ $charts->paymentMethodRevenue->description }}
            </p>

            <div class="chart">
                {!! $chartSvgs['payment_method_revenue'] !!}
            </div>
        </article>
    </section>

    <section class="summary">
        <h2 class="summary-title">
            Daily Sales Summary
        </h2>

        <table>
            <thead>
                <tr>
                    <th>
                        Date
                    </th>

                    <th>
                        Orders
                    </th>

                    <th>
                        Items
                    </th>

                    <th>
                        Subtotal
                    </th>

                    <th>
                        Discount
                    </th>

                    <th>
                        Delivery
                    </th>

                    <th>
                        Tax
                    </th>

                    <th>
                        Revenue
                    </th>

                    <th>
                        Paid
                    </th>
                </tr>
            </thead>

            <tbody>
                @forelse ($report->trend as $row)
                    <tr>
                        <td>
                            {{ $row->label }}
                        </td>

                        <td>
                            {{ number_format($row->ordersCount) }}
                        </td>

                        <td>
                            {{ number_format($row->itemsQuantity) }}
                        </td>

                        <td>
                            {{ $money($row->subtotal) }}
                        </td>

                        <td>
                            {{ $money($row->discountTotal) }}
                        </td>

                        <td>
                            {{ $money($row->deliveryTotal) }}
                        </td>

                        <td>
                            {{ $money($row->taxTotal) }}
                        </td>

                        <td>
                            {{ $money($row->grandTotal) }}
                        </td>

                        <td>
                            {{ $money($row->paidRevenue) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align:center;color:#64748b;">
                            No sales data for the selected period.
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
        Sales Analytics
    </footer>
</body>

</html>