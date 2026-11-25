<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Data;

final readonly class MerchantFeedCatalogEntryData
{
    public function __construct(
        public int $productId,
        public string $productName,
        public ?int $variantId,
        public ?string $variantLabel,
        public MerchantFeedBuildResultData $result,
    ) {}
}
