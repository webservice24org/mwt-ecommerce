<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Data;

final readonly class MerchantListingVariantData
{
    public function __construct(
        public int $id,
        public ?string $sku,
        public ?int $priceMinor,
        public int $stockQuantity,
    ) {}
}
