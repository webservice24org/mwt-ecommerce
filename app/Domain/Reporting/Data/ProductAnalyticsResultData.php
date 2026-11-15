<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

final readonly class ProductAnalyticsResultData
{
    /**
     * @param  list<ProductAnalyticsTrendRowData>  $trend
     * @param  list<ProductAnalyticsProductRowData>  $products
     */
    public function __construct(
        public ProductAnalyticsSummaryData $summary,
        public array $trend,
        public array $products,
    ) {}

    /**
     * @return array{
     *     summary: array<string, int>,
     *     trend: list<array<string, int|string>>,
     *     products: list<array<string, bool|int|string|null>>
     * }
     */
    public function toArray(): array
    {
        return [
            'summary' => $this->summary->toArray(),

            'trend' => array_map(
                static fn (
                    ProductAnalyticsTrendRowData $row,
                ): array => $row->toArray(),
                $this->trend,
            ),

            'products' => array_map(
                static fn (
                    ProductAnalyticsProductRowData $row,
                ): array => $row->toArray(),
                $this->products,
            ),
        ];
    }
}
