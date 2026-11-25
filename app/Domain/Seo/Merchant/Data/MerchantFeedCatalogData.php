<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Data;

final readonly class MerchantFeedCatalogData
{
    /**
     * @param  list<MerchantFeedItemData>  $items
     * @param list<array{
     *     product_id: int,
     *     variant_id: int|null,
     *     reasons: list<string>
     * }> $excluded
     * @param  list<MerchantFeedCatalogEntryData>  $entries
     */
    public function __construct(
        public array $items,
        public array $excluded,
        public bool $truncated,
        public int $maxItems,
        public array $entries = [],
    ) {}
}
