<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Data;

use App\Domain\Seo\Merchant\Enums\MerchantListingReadinessStatus;

final readonly class MerchantReadinessItemData
{
    /**
     * @param  list<MerchantListingFindingData>  $findings
     */
    public function __construct(
        public int $productId,
        public string $productName,
        public ?int $variantId,
        public ?string $variantLabel,
        public ?string $feedId,
        public MerchantListingReadinessStatus $status,
        public bool $feedEligible,
        public ?string $availability,
        public ?string $identifierState,
        public array $findings,
    ) {}

    /**
     * @return array{
     *     product_id: int,
     *     product_name: string,
     *     variant_id: int|null,
     *     variant_label: string|null,
     *     feed_id: string|null,
     *     status: string,
     *     feed_eligible: bool,
     *     availability: string|null,
     *     identifier_state: string|null,
     *     findings: list<array{
     *         id: string,
     *         severity: string,
     *         category: string,
     *         message: string,
     *         action: string
     *     }>
     * }
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,

            'product_name' => $this->productName,

            'variant_id' => $this->variantId,

            'variant_label' => $this->variantLabel,

            'feed_id' => $this->feedId,

            'status' => $this->status
                ->value,

            'feed_eligible' => $this->feedEligible,

            'availability' => $this->availability,

            'identifier_state' => $this->identifierState,

            'findings' => array_map(
                static fn (
                    MerchantListingFindingData $finding,
                ): array => $finding->toArray(),
                $this->findings,
            ),
        ];
    }
}
