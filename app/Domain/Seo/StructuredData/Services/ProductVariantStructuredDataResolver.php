<?php

declare(strict_types=1);

namespace App\Domain\Seo\StructuredData\Services;

use App\Domain\Catalog\Data\Storefront\StorefrontAttributeValueData;
use App\Domain\Catalog\Data\Storefront\StorefrontProductDetailData;
use App\Domain\Catalog\Data\Storefront\StorefrontVariantData;
use App\Domain\Catalog\Enums\ProductType;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\StructuredData\Data\StructuredDataDocument;
use App\Domain\Seo\StructuredData\Nodes\OfferStructuredDataNode;
use App\Domain\Seo\StructuredData\Nodes\ProductGroupStructuredDataNode;
use App\Domain\Seo\StructuredData\Nodes\VariantProductStructuredDataNode;

final readonly class ProductVariantStructuredDataResolver
{
    public function __construct(
        private StructuredDataResolver $structuredData,
        private StructuredDataMoneyFormatter $money,
        private StructuredDataCurrencyResolver $currency,
        private StructuredDataAvailabilityResolver $availability,
        private StructuredDataVariantPropertyResolver $variantProperties,
    ) {}

    public function resolve(
        SeoSettingsData $settings,
        StorefrontProductDetailData $product,
        string $canonicalUrl,
    ): ?StructuredDataDocument {
        if (
            ! $settings->enableStructuredData
        ) {
            return null;
        }

        if (
            $product->type !==
            ProductType::Variable
        ) {
            return null;
        }

        $canonicalUrl =
            $this->absoluteHttpUrl(
                $canonicalUrl,
            );

        if ($canonicalUrl === null) {
            return null;
        }

        $productName =
            $this->nullableString(
                $product->name,
            );

        if ($productName === null) {
            return null;
        }

        /*
         * ================================================
         * STEP 16 — STABLE PRODUCT GROUP ID
         * ================================================
         */
        $productGroupId =
            $this->productGroupId(
                $product,
            );

        $groupNodeId =
            $canonicalUrl
            .'#product-group';

        $variants = [];

        /**
         * Collect the attribute values used by valid
         * structured-data variants. These later determine
         * ProductGroup.variesBy.
         *
         * @var list<StorefrontAttributeValueData> $groupAttributeValues
         */
        $groupAttributeValues = [];

        foreach (
            $product->variants as $variant
        ) {
            /*
             * ============================================
             * STEP 18 — VARIABLE PRICE SAFETY
             * ============================================
             *
             * Do not emit a Product/Offer for a variant
             * without a real positive resolved price.
             */
            $price =
                $variant
                    ->pricing
                    ->price;

            if (
                $price === null
                || $price <= 0
            ) {
                continue;
            }

            $sku =
                $this->nullableString(
                    $variant->sku,
                );

            /*
             * Variant SKU is part of our authoritative
             * variant identity contract.
             */
            if ($sku === null) {
                continue;
            }

            /*
             * ============================================
             * STEP 15 — DISTINCT VARIANT DEEP LINK
             * ============================================
             */
            $variantUrl =
                $canonicalUrl
                .'?variant='
                .$variant->id;

            $variantNodeId =
                $canonicalUrl
                .'#variant-'
                .$variant->id;

            $offerNodeId =
                $canonicalUrl
                .'#variant-'
                .$variant->id
                .'-offer';

            $offer =
                new OfferStructuredDataNode(
                    id: $offerNodeId,

                    url: $variantUrl,

                    price: $this
                        ->money
                        ->fromMinorUnits(
                            $price,
                        ),

                    priceCurrency: $this
                        ->currency
                        ->currency(),

                    availability: $this
                        ->availability
                        ->resolve(
                            trackInventory: $product
                                ->trackInventory,

                            stockQuantity: $variant
                                ->stockQuantity,
                        )
                        ->value,
                );

            $properties =
                $this
                    ->variantProperties
                    ->properties(
                        $variant
                            ->attributeValues,
                    );

            foreach (
                $variant->attributeValues as $attributeValue
            ) {
                $groupAttributeValues[] =
                    $attributeValue;
            }

            $variants[] =
                new VariantProductStructuredDataNode(
                    id: $variantNodeId,

                    groupId: $groupNodeId,

                    url: $variantUrl,

                    /*
                     * ====================================
                     * STEP 17 — VARIANT-SPECIFIC NAME
                     * ====================================
                     */
                    name: $this->variantName(
                        product: $product,

                        variant: $variant,
                    ),

                    sku: $sku,

                    offer: $offer,

                    variantProperties: $properties,
                );
        }

        /*
         * A ProductGroup without any purchasable variants
         * is not useful structured data.
         */
        if ($variants === []) {
            return null;
        }

        $group =
            new ProductGroupStructuredDataNode(
                id: $groupNodeId,

                url: $canonicalUrl,

                name: $productName,

                productGroupId: $productGroupId,

                variesBy: $this
                    ->variantProperties
                    ->variesBy(
                        $groupAttributeValues,
                    ),

                variants: $variants,

                description: $this->description(
                    $product,
                ),

                brandName: $this->nullableString(
                    $product
                        ->brand
                        ?->name,
                ),
            );

        return $this
            ->structuredData
            ->resolve(
                settings: $settings,

                nodes: [
                    $group,
                ],
            );
    }

    /*
     * ====================================================
     * STEP 16 — PRODUCT GROUP IDENTIFIER
     * ====================================================
     */
    private function productGroupId(
        StorefrontProductDetailData $product,
    ): string {
        $sku =
            $this->nullableString(
                $product->sku,
            );

        if ($sku !== null) {
            return $sku;
        }

        return 'product-'
            .$product->id;
    }

    /*
     * ====================================================
     * STEP 17 — VARIANT NAME
     * ====================================================
     */
    private function variantName(
        StorefrontProductDetailData $product,
        StorefrontVariantData $variant,
    ): string {
        $values =
            array_map(
                static fn (
                    StorefrontAttributeValueData $value,
                ): string => trim(
                    $value->name,
                ),
                $variant
                    ->attributeValues,
            );

        $values =
            array_values(
                array_filter(
                    $values,
                    static fn (
                        string $value,
                    ): bool => $value !== '',
                ),
            );

        /*
         * Best case:
         *
         * USB Cable - Black, 2 Meter
         */
        if ($values !== []) {
            return $product->name
                .' - '
                .implode(
                    ', ',
                    $values,
                );
        }

        /*
         * Some variants legitimately have no attribute
         * combination. Use their explicit variant name.
         */
        $variantName =
            $this->nullableString(
                $variant->name,
            );

        if ($variantName !== null) {
            return $product->name
                .' - '
                .$variantName;
        }

        /*
         * Last deterministic fallback.
         */
        return $product->name
            .' - '
            .$variant->sku;
    }

    private function description(
        StorefrontProductDetailData $product,
    ): ?string {
        foreach (
            [
                $product
                    ->shortDescription,

                $product
                    ->description,
            ] as $description
        ) {
            $description =
                $this->plainText(
                    $description,
                );

            if ($description !== null) {
                return $description;
            }
        }

        return null;
    }

    private function plainText(
        ?string $value,
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value =
            html_entity_decode(
                strip_tags(
                    $value,
                ),
                ENT_QUOTES
                | ENT_HTML5,
                'UTF-8',
            );

        $normalized =
            preg_replace(
                '/\s+/u',
                ' ',
                $value,
            );

        return $this
            ->nullableString(
                $normalized
                ?? $value,
            );
    }

    private function absoluteHttpUrl(
        string $value,
    ): ?string {
        $value =
            trim(
                $value,
            );

        if (
            $value === ''
            || filter_var(
                $value,
                FILTER_VALIDATE_URL,
            ) === false
        ) {
            return null;
        }

        $scheme =
            strtolower(
                (string) parse_url(
                    $value,
                    PHP_URL_SCHEME,
                ),
            );

        if (
            $scheme !== 'http'
            && $scheme !== 'https'
        ) {
            return null;
        }

        return $value;
    }

    private function nullableString(
        ?string $value,
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value =
            trim(
                $value,
            );

        return $value === ''
            ? null
            : $value;
    }
}
