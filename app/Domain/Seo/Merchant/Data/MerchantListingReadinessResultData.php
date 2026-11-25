<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Data;

use App\Domain\Seo\Merchant\Enums\MerchantListingReadinessStatus;

final readonly class MerchantListingReadinessResultData
{
    /**
     * @param  list<MerchantListingFindingData>  $findings
     * @param array{
     *     product_type: string,
     *     canonical_present: bool,
     *     robots_index: bool,
     *     robots_follow: bool,
     *     currency: string,
     *     has_sellable_price: bool,
     *     availability: string,
     *     image_count: int,
     *     brand_present: bool,
     *     sku_present: bool,
     *     identifier_state: string,
     *     structured_data_types: list<string>,
     *     priced_variant_count: int,
     *     variant_count: int
     * } $signals
     */
    public function __construct(
        public string $policyVersion,
        public MerchantListingReadinessStatus $status,
        public array $signals,
        public array $findings,
    ) {}

    /**
     * @return array{
     *     policy_version: string,
     *     status: string,
     *     signals: array{
     *         product_type: string,
     *         canonical_present: bool,
     *         robots_index: bool,
     *         robots_follow: bool,
     *         currency: string,
     *         has_sellable_price: bool,
     *         availability: string,
     *         image_count: int,
     *         brand_present: bool,
     *         sku_present: bool,
     *         identifier_state: string,
     *         structured_data_types: list<string>,
     *         priced_variant_count: int,
     *         variant_count: int
     *     },
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
            'policy_version' => $this->policyVersion,

            'status' => $this->status
                ->value,

            'signals' => $this->signals,

            'findings' => array_map(
                static fn (
                    MerchantListingFindingData $finding,
                ): array => $finding->toArray(),
                $this->findings,
            ),
        ];
    }
}
