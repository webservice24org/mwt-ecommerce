<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Merchant;

use App\Domain\Seo\Merchant\Data\MerchantListingCandidateData;
use App\Domain\Seo\Merchant\Data\MerchantListingVariantData;
use App\Domain\Seo\Merchant\Enums\MerchantIdentifierState;
use App\Domain\Seo\Merchant\Enums\MerchantListingReadinessStatus;
use App\Domain\Seo\Merchant\Services\MerchantListingReadinessAnalyzer;
use DomainException;
use PHPUnit\Framework\TestCase;

final class MerchantListingReadinessAnalyzerTest extends TestCase
{
    public function test_complete_simple_product_is_ready_without_numeric_score(): void
    {
        $result =
            $this->analyzer()
                ->analyze(
                    $this->candidate(
                        identifierState: MerchantIdentifierState::Available,
                    ),
                );

        self::assertSame(
            MerchantListingReadinessStatus::Ready,
            $result
                ->status,
        );

        self::assertSame(
            'in_stock',
            $result
                ->signals[
                    'availability'
                ],
        );

        self::assertTrue(
            $result
                ->signals[
                    'has_sellable_price'
                ],
        );

        self::assertSame(
            [],
            $result
                ->findings,
        );

        self::assertArrayNotHasKey(
            'score',
            $result
                ->toArray(),
        );
    }

    public function test_noindex_missing_image_and_price_are_blockers(): void
    {
        $result =
            $this->analyzer()
                ->analyze(
                    $this->candidate(
                        robotsIndex: false,

                        priceMinor: null,

                        imageUrls: [],

                        identifierState: MerchantIdentifierState::Available,
                    ),
                );

        self::assertSame(
            MerchantListingReadinessStatus::Blocked,
            $result
                ->status,
        );

        $ids =
            $this->findingIds(
                $result
                    ->findings,
            );

        self::assertContains(
            'indexing_blocked',
            $ids,
        );

        self::assertContains(
            'primary_image_missing',
            $ids,
        );

        self::assertContains(
            'sellable_price_missing',
            $ids,
        );
    }

    public function test_missing_brand_unknown_identifier_and_structured_data_are_needs_work(): void
    {
        $result =
            $this->analyzer()
                ->analyze(
                    $this->candidate(
                        brandName: null,

                        structuredDataTypes: [],

                        identifierState: MerchantIdentifierState::Unknown,
                    ),
                );

        self::assertSame(
            MerchantListingReadinessStatus::NeedsWork,
            $result
                ->status,
        );

        $ids =
            $this->findingIds(
                $result
                    ->findings,
            );

        self::assertContains(
            'brand_missing_review_required',
            $ids,
        );

        self::assertContains(
            'merchant_identifier_state_unknown',
            $ids,
        );

        self::assertContains(
            'merchant_product_structured_data_missing',
            $ids,
        );
    }

    public function test_out_of_stock_is_informational_when_offer_data_is_otherwise_valid(): void
    {
        $result =
            $this->analyzer()
                ->analyze(
                    $this->candidate(
                        stockQuantity: 0,

                        identifierState: MerchantIdentifierState::Available,
                    ),
                );

        self::assertSame(
            MerchantListingReadinessStatus::Ready,
            $result
                ->status,
        );

        self::assertSame(
            'out_of_stock',
            $result
                ->signals[
                    'availability'
                ],
        );

        self::assertSame(
            'currently_out_of_stock',
            $result
                ->findings[
                    0
                ]
                    ->id,
        );

        self::assertSame(
            'info',
            $result
                ->findings[
                    0
                ]
                    ->severity
                    ->value,
        );
    }

    public function test_variable_product_requires_at_least_one_priced_variant(): void
    {
        $result =
            $this->analyzer()
                ->analyze(
                    $this->candidate(
                        productType: 'variable',

                        priceMinor: null,

                        identifierState: MerchantIdentifierState::Available,

                        structuredDataTypes: [
                            'ProductGroup',
                        ],

                        variants: [
                            new MerchantListingVariantData(
                                id: 41,

                                sku: 'VAR-41',

                                priceMinor: null,

                                stockQuantity: 5,
                            ),
                        ],
                    ),
                );

        self::assertSame(
            MerchantListingReadinessStatus::Blocked,
            $result
                ->status,
        );

        self::assertFalse(
            $result
                ->signals[
                    'has_sellable_price'
                ],
        );

        self::assertContains(
            'sellable_price_missing',
            $this->findingIds(
                $result
                    ->findings,
            ),
        );
    }

    public function test_variable_product_with_partial_price_gaps_is_needs_work_not_blocked(): void
    {
        $result =
            $this->analyzer()
                ->analyze(
                    $this->candidate(
                        productType: 'variable',

                        priceMinor: null,

                        identifierState: MerchantIdentifierState::Available,

                        structuredDataTypes: [
                            'ProductGroup',
                        ],

                        variants: [
                            new MerchantListingVariantData(
                                id: 41,

                                sku: 'VAR-41',

                                priceMinor: 85000,

                                stockQuantity: 5,
                            ),

                            new MerchantListingVariantData(
                                id: 42,

                                sku: 'VAR-42',

                                priceMinor: null,

                                stockQuantity: 0,
                            ),
                        ],
                    ),
                );

        self::assertSame(
            MerchantListingReadinessStatus::NeedsWork,
            $result
                ->status,
        );

        self::assertSame(
            1,
            $result
                ->signals[
                    'priced_variant_count'
                ],
        );

        self::assertContains(
            'variant_price_gaps',
            $this->findingIds(
                $result
                    ->findings,
            ),
        );
    }

    public function test_unsafe_landing_url_is_blocked(): void
    {
        $result =
            $this->analyzer()
                ->analyze(
                    $this->candidate(
                        canonicalUrl: 'javascript:alert(1)',

                        identifierState: MerchantIdentifierState::Available,
                    ),
                );

        self::assertSame(
            MerchantListingReadinessStatus::Blocked,
            $result
                ->status,
        );

        self::assertContains(
            'landing_url_missing_or_unsafe',
            $this->findingIds(
                $result
                    ->findings,
            ),
        );
    }

    public function test_unsupported_product_type_is_rejected(): void
    {
        $this->expectException(
            DomainException::class,
        );

        $this->analyzer()
            ->analyze(
                $this->candidate(
                    productType: 'subscription',

                    identifierState: MerchantIdentifierState::Available,
                ),
            );
    }

    /**
     * @param  list<string>  $imageUrls
     * @param  list<string>  $structuredDataTypes
     * @param  list<MerchantListingVariantData>  $variants
     */
    private function candidate(
        string $productType = 'simple',
        string $title = 'USB Cable',
        ?string $description = 'Braided USB cable.',
        ?string $canonicalUrl = 'https://example.com/products/cable',
        bool $robotsIndex = true,
        bool $robotsFollow = true,
        string $currency = 'BDT',
        ?int $priceMinor = 85000,
        bool $trackInventory = true,
        int $stockQuantity = 5,
        ?string $brandName = 'MWT',
        ?string $sku = 'CAB-001',
        MerchantIdentifierState $identifierState = MerchantIdentifierState::Unknown,
        array $imageUrls = [
            'https://example.com/storage/cable.jpg',
        ],
        array $structuredDataTypes = [
            'Product',
        ],
        array $variants = [],
    ): MerchantListingCandidateData {
        return new MerchantListingCandidateData(
            productId: 10,

            productType: $productType,

            title: $title,

            description: $description,

            canonicalUrl: $canonicalUrl,

            robotsIndex: $robotsIndex,

            robotsFollow: $robotsFollow,

            currency: $currency,

            priceMinor: $priceMinor,

            trackInventory: $trackInventory,

            stockQuantity: $stockQuantity,

            brandName: $brandName,

            sku: $sku,

            identifierState: $identifierState,

            imageUrls: $imageUrls,

            structuredDataTypes: $structuredDataTypes,

            variants: $variants,
        );
    }

    /**
     * @param  list<object{id: string}>  $findings
     * @return list<string>
     */
    private function findingIds(
        array $findings,
    ): array {
        return array_map(
            static fn (
                object $finding,
            ): string => $finding->id,
            $findings,
        );
    }

    private function analyzer(): MerchantListingReadinessAnalyzer
    {
        return new MerchantListingReadinessAnalyzer;
    }
}
