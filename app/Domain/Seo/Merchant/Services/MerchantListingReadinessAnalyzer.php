<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Services;

use App\Domain\Seo\Merchant\Data\MerchantListingCandidateData;
use App\Domain\Seo\Merchant\Data\MerchantListingFindingData;
use App\Domain\Seo\Merchant\Data\MerchantListingReadinessResultData;
use App\Domain\Seo\Merchant\Enums\MerchantIdentifierState;
use App\Domain\Seo\Merchant\Enums\MerchantListingFindingSeverity;
use App\Domain\Seo\Merchant\Enums\MerchantListingReadinessStatus;
use App\Domain\Seo\Merchant\MerchantListingReadinessPolicy;
use DomainException;

final readonly class MerchantListingReadinessAnalyzer
{
    public function analyze(
        MerchantListingCandidateData $candidate,
    ): MerchantListingReadinessResultData {
        if (
            ! in_array(
                $candidate->productType,
                MerchantListingReadinessPolicy::supportedProductTypes(),
                true,
            )
        ) {
            throw new DomainException(
                sprintf(
                    'Unsupported merchant product type: %s.',
                    $candidate->productType,
                ),
            );
        }

        $findings = [];

        $canonicalPresent =
            $this->isHttpUrl(
                $candidate->canonicalUrl,
            );

        if (! $candidate->robotsIndex) {
            $findings[] =
                $this->finding(
                    id: 'indexing_blocked',

                    severity: MerchantListingFindingSeverity::Blocker,

                    category: 'indexability',

                    message: 'The product landing page is noindex.',

                    action: 'Keep noindex when intentional; otherwise correct the authoritative robots policy before merchant submission.',
                );
        }

        if (! $canonicalPresent) {
            $findings[] =
                $this->finding(
                    id: 'landing_url_missing_or_unsafe',

                    severity: MerchantListingFindingSeverity::Blocker,

                    category: 'landing_page',

                    message: 'The product does not have a safe HTTP(S) canonical landing URL.',

                    action: 'Resolve one stable public HTTP(S) product URL on the verified storefront domain.',
                );
        }

        if (
            trim(
                $candidate->title,
            ) === ''
        ) {
            $findings[] =
                $this->finding(
                    id: 'title_missing',

                    severity: MerchantListingFindingSeverity::Blocker,

                    category: 'product_data',

                    message: 'The product title is empty.',

                    action: 'Provide a truthful customer-facing product title.',
                );
        }

        if ($candidate->imageUrls === []) {
            $findings[] =
                $this->finding(
                    id: 'primary_image_missing',

                    severity: MerchantListingFindingSeverity::Blocker,

                    category: 'image',

                    message: 'The product has no merchant-ready product image.',

                    action: 'Add a crawlable primary product image that accurately shows the item and contains no promotional overlay or placeholder content.',
                );
        }

        if (! $this->isCurrency($candidate->currency)) {
            $findings[] =
                $this->finding(
                    id: 'currency_invalid',

                    severity: MerchantListingFindingSeverity::Blocker,

                    category: 'offer',

                    message: 'The offer currency is missing or is not a three-letter ISO-style currency code.',

                    action: 'Use the authoritative store currency for both landing-page/structured data and future merchant data.',
                );
        }

        $pricedVariantCount =
            $this->pricedVariantCount(
                $candidate,
            );

        $hasSellablePrice =
            $candidate->productType ===
                'simple'
                ? $this->positivePrice(
                    $candidate
                        ->priceMinor,
                )
                : $pricedVariantCount >
                    0;

        if (! $hasSellablePrice) {
            $findings[] =
                $this->finding(
                    id: 'sellable_price_missing',

                    severity: MerchantListingFindingSeverity::Blocker,

                    category: 'offer',

                    message: 'No positive authoritative selling price is available for the product or any variant.',

                    action: 'Publish an authoritative positive selling price that matches the landing page, structured data, checkout, and future merchant data source.',
                );
        }

        if (! $candidate->robotsFollow) {
            $findings[] =
                $this->finding(
                    id: 'link_following_disabled',

                    severity: MerchantListingFindingSeverity::Warning,

                    category: 'discovery',

                    message: 'The product robots policy disables link following.',

                    action: 'Use nofollow only when intentional; normal merchant product pages should remain discoverable through ordinary site navigation.',
                );
        }

        if (
            $candidate->description ===
            null
            || trim(
                strip_tags(
                    $candidate
                        ->description,
                ),
            ) === ''
        ) {
            $findings[] =
                $this->finding(
                    id: 'description_missing',

                    severity: MerchantListingFindingSeverity::Warning,

                    category: 'product_data',

                    message: 'The product has no useful customer-facing description.',

                    action: 'Add factual visible product content that accurately describes the item and matches any future merchant feed.',
                );
        }

        if (
            $candidate->brandName ===
            null
            || trim(
                $candidate
                    ->brandName,
            ) === ''
        ) {
            $findings[] =
                $this->finding(
                    id: 'brand_missing_review_required',

                    severity: MerchantListingFindingSeverity::Warning,

                    category: 'identifiers',

                    message: 'No product brand is available. Brand is required for many new products, with category-specific exceptions.',

                    action: 'Provide the real manufacturer/recognized brand when one exists; do not invent Generic, N/A, or another placeholder brand.',
                );
        }

        if (
            $candidate->identifierState ===
            MerchantIdentifierState::Unknown
        ) {
            $findings[] =
                $this->finding(
                    id: 'merchant_identifier_state_unknown',

                    severity: MerchantListingFindingSeverity::Warning,

                    category: 'identifiers',

                    message: 'Merchant unique-product-identifier state has not been declared.',

                    action: 'Determine whether the item has manufacturer identifiers such as GTIN/MPN, or explicitly declare that identifiers do not exist when policy allows.',
                );
        }

        if (
            ! $this->containsAny(
                haystack: $candidate
                    ->structuredDataTypes,

                needles: MerchantListingReadinessPolicy::merchantProductStructuredDataTypes(),
            )
        ) {
            $findings[] =
                $this->finding(
                    id: 'merchant_product_structured_data_missing',

                    severity: MerchantListingFindingSeverity::Warning,

                    category: 'structured_data',

                    message: 'No Product or ProductGroup structured-data type was reported for the landing page.',

                    action: 'Keep the existing Product/Offer or ProductGroup/variant structured data enabled and consistent with the visible product state.',
                );
        }

        if (
            $candidate->productType ===
            'variable'
            && $candidate->variants === []
        ) {
            $findings[] =
                $this->finding(
                    id: 'variable_product_has_no_variants',

                    severity: MerchantListingFindingSeverity::Blocker,

                    category: 'variants',

                    message: 'The product is variable but has no merchant-visible variants.',

                    action: 'Publish at least one real active variant with authoritative price and availability data.',
                );
        }

        if (
            $candidate->productType ===
            'variable'
            && $candidate->variants !== []
            && $pricedVariantCount <
                count(
                    $candidate
                        ->variants,
                )
        ) {
            $findings[] =
                $this->finding(
                    id: 'variant_price_gaps',

                    severity: MerchantListingFindingSeverity::Warning,

                    category: 'variants',

                    message: 'One or more variants do not have a positive authoritative price.',

                    action: 'Ensure each merchant-submitted variant has price and availability that match its landing-page state.',
                );
        }

        if (
            $candidate->sku ===
                null
            || trim(
                $candidate
                    ->sku,
            ) === ''
        ) {
            $findings[] =
                $this->finding(
                    id: 'internal_sku_missing',

                    severity: MerchantListingFindingSeverity::Info,

                    category: 'identifiers',

                    message: 'The parent product does not have an internal SKU.',

                    action: 'Use a stable internal item identity for feed/export operations; do not confuse SKU with GTIN or manufacturer MPN.',
                );
        }

        $availability =
            $this->availability(
                $candidate,
            );

        if (
            $availability ===
            'out_of_stock'
        ) {
            $findings[] =
                $this->finding(
                    id: 'currently_out_of_stock',

                    severity: MerchantListingFindingSeverity::Info,

                    category: 'availability',

                    message: 'The current authoritative availability is out of stock.',

                    action: 'Keep availability synchronized across the product page, structured data, checkout, and future merchant data source.',
                );
        }

        return new MerchantListingReadinessResultData(
            policyVersion: MerchantListingReadinessPolicy::VERSION,

            status: $this->status(
                $findings,
            ),

            signals: [
                'product_type' => $candidate
                    ->productType,

                'canonical_present' => $canonicalPresent,

                'robots_index' => $candidate
                    ->robotsIndex,

                'robots_follow' => $candidate
                    ->robotsFollow,

                'currency' => strtoupper(
                    trim(
                        $candidate
                            ->currency,
                    ),
                ),

                'has_sellable_price' => $hasSellablePrice,

                'availability' => $availability,

                'image_count' => count(
                    $candidate
                        ->imageUrls,
                ),

                'brand_present' => $candidate
                    ->brandName !==
                    null
                    && trim(
                        $candidate
                            ->brandName,
                    ) !== '',

                'sku_present' => $candidate
                    ->sku !==
                    null
                    && trim(
                        $candidate
                            ->sku,
                    ) !== '',

                'identifier_state' => $candidate
                    ->identifierState
                    ->value,

                'structured_data_types' => $this->normalizeTypes(
                    $candidate
                        ->structuredDataTypes,
                ),

                'priced_variant_count' => $pricedVariantCount,

                'variant_count' => count(
                    $candidate
                        ->variants,
                ),
            ],

            findings: $findings,
        );
    }

    /**
     * @param  list<MerchantListingFindingData>  $findings
     */
    private function status(
        array $findings,
    ): MerchantListingReadinessStatus {
        foreach (
            $findings as $finding
        ) {
            if (
                $finding->severity ===
                MerchantListingFindingSeverity::Blocker
            ) {
                return MerchantListingReadinessStatus::Blocked;
            }
        }

        foreach (
            $findings as $finding
        ) {
            if (
                $finding->severity ===
                MerchantListingFindingSeverity::Warning
            ) {
                return MerchantListingReadinessStatus::NeedsWork;
            }
        }

        return MerchantListingReadinessStatus::Ready;
    }

    private function finding(
        string $id,
        MerchantListingFindingSeverity $severity,
        string $category,
        string $message,
        string $action,
    ): MerchantListingFindingData {
        return new MerchantListingFindingData(
            id: $id,

            severity: $severity,

            category: $category,

            message: $message,

            action: $action,
        );
    }

    private function positivePrice(
        ?int $priceMinor,
    ): bool {
        return $priceMinor !== null
            && $priceMinor >
                0;
    }

    private function pricedVariantCount(
        MerchantListingCandidateData $candidate,
    ): int {
        $count = 0;

        foreach (
            $candidate->variants as $variant
        ) {
            if (
                $this->positivePrice(
                    $variant
                        ->priceMinor,
                )
            ) {
                $count++;
            }
        }

        return $count;
    }

    private function availability(
        MerchantListingCandidateData $candidate,
    ): string {
        if (
            $candidate->productType ===
            'simple'
        ) {
            return ! $candidate
                ->trackInventory
                || $candidate
                    ->stockQuantity >
                    0
                        ? 'in_stock'
                        : 'out_of_stock';
        }

        foreach (
            $candidate->variants as $variant
        ) {
            if (
                ! $candidate
                    ->trackInventory
                || $variant
                    ->stockQuantity >
                    0
            ) {
                return 'in_stock';
            }
        }

        return 'out_of_stock';
    }

    private function isCurrency(
        string $currency,
    ): bool {
        return preg_match(
            '/^[A-Z]{3}$/',
            strtoupper(
                trim(
                    $currency,
                ),
            ),
        ) === 1;
    }

    private function isHttpUrl(
        ?string $url,
    ): bool {
        if (
            $url === null
            || trim(
                $url,
            ) === ''
        ) {
            return false;
        }

        $scheme =
            parse_url(
                $url,
                PHP_URL_SCHEME,
            );

        return is_string(
            $scheme,
        )
        && in_array(
            strtolower(
                $scheme,
            ),
            [
                'http',
                'https',
            ],
            true,
        );
    }

    /**
     * @param  list<string>  $haystack
     * @param  list<string>  $needles
     */
    private function containsAny(
        array $haystack,
        array $needles,
    ): bool {
        $haystack =
            $this->normalizeTypes(
                $haystack,
            );

        foreach (
            $needles as $needle
        ) {
            if (
                in_array(
                    $needle,
                    $haystack,
                    true,
                )
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $types
     * @return list<string>
     */
    private function normalizeTypes(
        array $types,
    ): array {
        $normalized = [];

        foreach (
            $types as $type
        ) {
            $type =
                trim(
                    $type,
                );

            if (
                $type === ''
                || in_array(
                    $type,
                    $normalized,
                    true,
                )
            ) {
                continue;
            }

            $normalized[] =
                $type;
        }

        sort(
            $normalized,
            SORT_STRING,
        );

        return $normalized;
    }
}
