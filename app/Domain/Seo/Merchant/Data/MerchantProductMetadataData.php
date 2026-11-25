<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Data;

use App\Domain\Seo\Merchant\Enums\MerchantIdentifierState;
use App\Domain\Seo\Merchant\Enums\MerchantProductCondition;

final readonly class MerchantProductMetadataData
{
    public function __construct(
        public MerchantProductCondition $condition,
        public MerchantIdentifierState $identifierState,
        public ?string $gtin,
        public ?string $mpn,
        public ?string $googleProductCategory,
        public int $version,
    ) {}

    /**
     * @return array{
     *     condition: string,
     *     identifier_state: string,
     *     gtin: string|null,
     *     mpn: string|null,
     *     google_product_category: string|null,
     *     version: int
     * }
     */
    public function toArray(): array
    {
        return [
            'condition' => $this->condition
                ->value,

            'identifier_state' => $this->identifierState
                ->value,

            'gtin' => $this->gtin,

            'mpn' => $this->mpn,

            'google_product_category' => $this->googleProductCategory,

            'version' => $this->version,
        ];
    }
}
