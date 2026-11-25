<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Data;

use App\Domain\Seo\Merchant\Enums\MerchantIdentifierState;

final readonly class MerchantListingCandidateData
{
    /**
     * @param  list<string>  $imageUrls
     * @param  list<string>  $structuredDataTypes
     * @param  list<MerchantListingVariantData>  $variants
     */
    public function __construct(
        public int $productId,
        public string $productType,
        public string $title,
        public ?string $description,
        public ?string $canonicalUrl,
        public bool $robotsIndex,
        public bool $robotsFollow,
        public string $currency,
        public ?int $priceMinor,
        public bool $trackInventory,
        public int $stockQuantity,
        public ?string $brandName,
        public ?string $sku,
        public MerchantIdentifierState $identifierState,
        public array $imageUrls,
        public array $structuredDataTypes,
        public array $variants = [],
    ) {}
}
