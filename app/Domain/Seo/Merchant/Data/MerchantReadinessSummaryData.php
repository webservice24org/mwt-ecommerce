<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Data;

final readonly class MerchantReadinessSummaryData
{
    /**
     * @param  array<string, int>  $findingCounts
     * @param  array{blocker: int, warning: int, info: int}  $severityCounts
     */
    public function __construct(
        public int $productsConsidered,
        public int $itemCandidates,
        public int $ready,
        public int $needsWork,
        public int $blocked,
        public int $feedEligible,
        public int $excluded,
        public int $outOfStock,
        public array $findingCounts,
        public array $severityCounts,
        public bool $truncated,
        public int $maxItems,
    ) {}

    /**
     * @return array{
     *     products_considered: int,
     *     item_candidates: int,
     *     ready: int,
     *     needs_work: int,
     *     blocked: int,
     *     feed_eligible: int,
     *     excluded: int,
     *     out_of_stock: int,
     *     finding_counts: array<string, int>,
     *     severity_counts: array{blocker: int, warning: int, info: int},
     *     truncated: bool,
     *     max_items: int
     * }
     */
    public function toArray(): array
    {
        return [
            'products_considered' => $this->productsConsidered,

            'item_candidates' => $this->itemCandidates,

            'ready' => $this->ready,

            'needs_work' => $this->needsWork,

            'blocked' => $this->blocked,

            'feed_eligible' => $this->feedEligible,

            'excluded' => $this->excluded,

            'out_of_stock' => $this->outOfStock,

            'finding_counts' => $this->findingCounts,

            'severity_counts' => $this->severityCounts,

            'truncated' => $this->truncated,

            'max_items' => $this->maxItems,
        ];
    }
}
