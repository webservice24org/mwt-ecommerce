<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Services;

use App\Domain\Catalog\Enums\ProductType;
use App\Domain\Catalog\Services\ProductPricingResolver;
use App\Domain\Seo\Merchant\Data\MerchantFeedBuildResultData;
use App\Domain\Seo\Merchant\Data\MerchantFeedItemData;
use App\Domain\Seo\Merchant\Data\MerchantListingCandidateData;
use App\Domain\Seo\Merchant\Data\MerchantListingReadinessResultData;
use App\Domain\Seo\Merchant\Data\MerchantListingVariantData;
use App\Domain\Seo\Merchant\Data\MerchantProductMetadataData;
use App\Domain\Seo\Merchant\Enums\MerchantIdentifierState;
use App\Domain\Seo\Merchant\Enums\MerchantListingFindingSeverity;
use App\Domain\Seo\Merchant\Enums\MerchantListingReadinessStatus;
use App\Domain\Seo\Services\CanonicalUrlResolver;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Domain\Seo\Services\StorefrontSeoResolver;
use App\Domain\Seo\StructuredData\Enums\StructuredDataAvailability;
use App\Domain\Seo\StructuredData\Services\StructuredDataAvailabilityResolver;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

final readonly class MerchantProductFeedItemFactory
{
    public function __construct(
        private ProductPricingResolver $pricing,
        private StorefrontSeoResolver $seo,
        private SeoSettingsService $seoSettings,
        private CanonicalUrlResolver $canonical,
        private StructuredDataAvailabilityResolver $availability,
        private MerchantListingReadinessAnalyzer $readiness,
        private MerchantGtinValidator $gtin,
    ) {}

    public function build(
        Product $product,
        ?ProductVariant $variant,
        MerchantProductMetadataData $metadata,
    ): MerchantFeedBuildResultData {
        $product->loadMissing([
            'brand',
            'images',
            'variants.attributeValues.attribute',
        ]);

        if ($variant !== null) {
            $variant->loadMissing(
                'attributeValues.attribute',
            );
        }

        if (
            $variant !== null
            && (
                $variant->product_id !==
                    $product->id
                || ! $variant->is_active
            )
        ) {
            return $this->excluded(
                'variant_not_active_or_not_owned',
            );
        }

        if (
            $product->type ===
                ProductType::Simple
            && $variant !== null
        ) {
            return $this->excluded(
                'simple_product_cannot_export_variant',
            );
        }

        if (
            $product->type ===
                ProductType::Variable
            && $variant === null
        ) {
            return $this->excluded(
                'variable_product_requires_variant_item',
            );
        }

        $settings =
            $this
                ->seoSettings
                ->get();

        $resolvedSeo =
            $this
                ->seo
                ->resolve(
                    entity: $product,

                    settings: $settings,
                );

        $canonicalUrl =
            $this
                ->canonical
                ->resolve(
                    canonicalOverride: $resolvedSeo
                        ->canonicalOverride,

                    path: '/products/'
                        .$product
                            ->slug,

                    canonicalHost: $settings
                        ->canonicalHost,
                );

        $images =
            $this->imageUrls(
                product: $product,

                canonicalHost: $settings
                    ->canonicalHost,
            );

        $price =
            $this
                ->pricing
                ->resolve(
                    product: $product,

                    variant: $variant,
                )
                ->price;

        $stockQuantity =
            $this->stockQuantity(
                product: $product,

                variant: $variant,
            );

        $availability =
            $this
                ->availability
                ->resolve(
                    trackInventory: $product
                        ->track_inventory,

                    stockQuantity: $stockQuantity,
                );

        $activeVariants =
            $product
                ->variants
                ->filter(
                    static fn (
                        ProductVariant $item,
                    ): bool => $item
                        ->is_active,
                )
                ->values();

        $candidate =
            new MerchantListingCandidateData(
                productId: $product->id,

                productType: $product
                    ->type
                    ->value,

                title: $product
                    ->name,

                description: $this->description(
                    $product,
                ),

                canonicalUrl: $canonicalUrl,

                robotsIndex: $resolvedSeo
                    ->robotsIndex,

                robotsFollow: $resolvedSeo
                    ->robotsFollow,

                currency: $this->currency(),

                priceMinor: $product
                    ->type ===
                        ProductType::Simple
                            ? $price
                            : null,

                trackInventory: $product
                    ->track_inventory,

                stockQuantity: $product
                    ->stock_quantity,

                brandName: $product
                    ->brand
                    ?->name,

                sku: $this->sku(
                    product: $product,

                    variant: $variant,
                ),

                identifierState: $metadata
                    ->identifierState,

                imageUrls: $images,

                structuredDataTypes: $settings
                    ->enableStructuredData
                        ? [
                            $product
                                ->type ===
                                ProductType::Variable
                                    ? 'ProductGroup'
                                    : 'Product',
                        ]
                        : [],

                variants: $activeVariants
                    ->map(
                        fn (
                            ProductVariant $item,
                        ): MerchantListingVariantData => new MerchantListingVariantData(
                            id: $item->id,

                            sku: $item->sku,

                            priceMinor: $this
                                ->pricing
                                ->resolve(
                                    product: $product,

                                    variant: $item,
                                )
                                ->price,

                            stockQuantity: $item
                                ->stock_quantity,
                        ),
                    )
                    ->all(),
            );

        $readiness =
            $this
                ->readiness
                ->analyze(
                    $candidate,
                );

        if (
            $readiness->status ===
            MerchantListingReadinessStatus::Blocked
        ) {
            $reasons = [];

            foreach (
                $readiness->findings as $finding
            ) {
                if (
                    $finding->severity ===
                    MerchantListingFindingSeverity::Blocker
                ) {
                    $reasons[] =
                        $finding->id;
                }
            }

            return new MerchantFeedBuildResultData(
                item: null,

                exclusionReasons: $reasons,

                warnings: [],

                readiness: $readiness,
            );
        }

        if (
            $price === null
            || $price <=
                0
        ) {
            return $this->excluded(
                'item_price_missing',
                $readiness,
            );
        }

        if ($images === []) {
            return $this->excluded(
                'item_image_missing',
                $readiness,
            );
        }

        if (! $this->isHttpUrl(
            $canonicalUrl,
        )) {
            return $this->excluded(
                'item_link_missing_or_unsafe',
                $readiness,
            );
        }

        if (
            $metadata->gtin !== null
            && ! $this
                ->gtin
                ->valid(
                    $metadata
                        ->gtin,
                )
        ) {
            return $this->excluded(
                'invalid_gtin',
                $readiness,
            );
        }

        if (
            $metadata
                ->condition
                ->value !==
                'new'
        ) {
            return $this->excluded(
                'non_new_condition_not_visible_on_landing_page',
                $readiness,
            );
        }

        $warnings = [];

        foreach (
            $readiness->findings as $finding
        ) {
            if (
                $finding->severity !==
                MerchantListingFindingSeverity::Blocker
            ) {
                $warnings[] =
                    $finding->id;
            }
        }

        if (
            $metadata->identifierState ===
                MerchantIdentifierState::Available
            && $metadata->gtin ===
                null
            && $metadata->mpn ===
                null
        ) {
            $warnings[] =
                'identifier_declared_available_but_values_missing';
        }

        $attributes =
            $variant !== null
                ? $this->variantAttributes(
                    $variant,
                )
                : [];

        $link =
            $variant !== null
                ? $canonicalUrl
                    .'?variant='
                    .$variant->id
                : $canonicalUrl;

        return new MerchantFeedBuildResultData(
            item: new MerchantFeedItemData(
                id: $variant !== null
                        ? 'v-'
                            .$variant
                                ->id
                        : 'p-'
                            .$product
                                ->id,

                title: $this->title(
                    product: $product,

                    variant: $variant,

                    attributes: $attributes,
                ),

                description: $this->feedDescription(
                    $product,
                ),

                link: $link,

                imageLink: $images[
                        0
                    ],

                additionalImageLinks: array_slice(
                    $images,
                    1,
                    10,
                ),

                availability: $availability ===
                        StructuredDataAvailability::InStock
                            ? 'in_stock'
                            : 'out_of_stock',

                price: $this->money(
                    $price,
                )
                    .' '
                    .$this->currency(),

                brand: $product
                    ->brand
                    ?->name,

                gtin: $metadata
                    ->gtin,

                mpn: $metadata
                    ->mpn,

                identifierExists: match (
                    $metadata
                        ->identifierState
                ) {
                    MerchantIdentifierState::Available => 'yes',

                    MerchantIdentifierState::NotAvailableDeclared => 'no',

                    MerchantIdentifierState::Unknown => null,
                },

                condition: $metadata
                    ->condition
                    ->value,

                itemGroupId: $variant !== null
                        ? 'p-'
                            .$product
                                ->id
                        : null,

                googleProductCategory: $metadata
                    ->googleProductCategory,

                color: $attributes[
                        'color'
                    ]
                    ?? null,

                size: $attributes[
                        'size'
                    ]
                    ?? null,

                material: $attributes[
                        'material'
                    ]
                    ?? null,

                pattern: $attributes[
                        'pattern'
                    ]
                    ?? null,
            ),

            exclusionReasons: [],

            warnings: array_values(
                array_unique(
                    $warnings,
                ),
            ),

            readiness: $readiness,
        );
    }

    /**
     * @return list<string>
     */
    private function imageUrls(
        Product $product,
        ?string $canonicalHost,
    ): array {
        /** @var FilesystemAdapter $disk */
        $disk =
            Storage::disk(
                'public',
            );

        $images =
            $product
                ->images
                ->sort(
                    static function (
                        ProductImage $left,
                        ProductImage $right,
                    ): int {
                        if (
                            $left->is_primary !==
                            $right->is_primary
                        ) {
                            return $left->is_primary
                                ? -1
                                : 1;
                        }

                        $position =
                            $left->position
                            <=>
                            $right->position;

                        return $position !==
                            0
                                ? $position
                                : $left->id
                                    <=>
                                    $right->id;
                    },
                );

        $urls = [];

        foreach ($images as $image) {
            $url =
                $this->absoluteUrl(
                    url: $disk->url(
                        $image->path,
                    ),

                    canonicalHost: $canonicalHost,
                );

            if (
                $url !== null
                && ! in_array(
                    $url,
                    $urls,
                    true,
                )
            ) {
                $urls[] =
                    $url;
            }
        }

        return $urls;
    }

    /**
     * @return array<string, string>
     */
    private function variantAttributes(
        ProductVariant $variant,
    ): array {
        $attributes = [];

        foreach (
            $variant
                ->attributeValues as $value
        ) {
            $attribute =
                $value->attribute;

            if ($attribute === null) {
                continue;
            }

            $slug =
                strtolower(
                    trim(
                        $attribute
                            ->slug,
                    ),
                );

            if (
                ! in_array(
                    $slug,
                    [
                        'color',
                        'size',
                        'material',
                        'pattern',
                    ],
                    true,
                )
                || array_key_exists(
                    $slug,
                    $attributes,
                )
            ) {
                continue;
            }

            $attributes[
                $slug
            ] =
                trim(
                    $value
                        ->name,
                );
        }

        return $attributes;
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function title(
        Product $product,
        ?ProductVariant $variant,
        array $attributes,
    ): string {
        $parts = [
            trim(
                $product
                    ->name,
            ),
        ];

        if (
            $variant !== null
            && $variant->name !==
                null
            && trim(
                $variant->name,
            ) !== ''
        ) {
            $parts[] =
                trim(
                    $variant
                        ->name,
                );
        } elseif ($attributes !== []) {
            $parts[] =
                implode(
                    ' / ',
                    array_values(
                        $attributes,
                    ),
                );
        }

        return mb_substr(
            implode(
                ' - ',
                array_values(
                    array_unique(
                        $parts,
                    ),
                ),
            ),
            0,
            150,
        );
    }

    private function feedDescription(
        Product $product,
    ): string {
        return mb_substr(
            $this->description(
                $product,
            )
            ?? trim(
                $product
                    ->name,
            ),
            0,
            5000,
        );
    }

    private function description(
        Product $product,
    ): ?string {
        $source =
            $product
                ->short_description
            ?? $product
                ->description;

        if ($source === null) {
            return null;
        }

        $text =
            html_entity_decode(
                strip_tags(
                    $source,
                ),
                ENT_QUOTES
                | ENT_HTML5,
                'UTF-8',
            );

        $text =
            preg_replace(
                '/\s+/u',
                ' ',
                $text,
            );

        if (! is_string($text)) {
            return null;
        }

        $text =
            trim(
                $text,
            );

        return $text !== ''
            ? $text
            : null;
    }

    private function absoluteUrl(
        string $url,
        ?string $canonicalHost,
    ): ?string {
        if (
            $this->isHttpUrl(
                $url,
            )
        ) {
            return $url;
        }

        if (
            $canonicalHost === null
            || ! $this->isHttpUrl(
                $canonicalHost,
            )
        ) {
            return null;
        }

        return rtrim(
            $canonicalHost,
            '/',
        )
        .'/'
        .ltrim(
            $url,
            '/',
        );
    }

    private function isHttpUrl(
        string $url,
    ): bool {
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

    private function stockQuantity(
        Product $product,
        ?ProductVariant $variant,
    ): int {
        if ($variant === null) {
            return $product
                ->stock_quantity;
        }

        return $variant
            ->stock_quantity;
    }

    private function sku(
        Product $product,
        ?ProductVariant $variant,
    ): ?string {
        if ($variant === null) {
            return $product
                ->sku;
        }

        return $variant
            ->sku;
    }

    private function currency(): string
    {
        return strtoupper(
            (string) config(
                'commerce.currency',
                'BDT',
            ),
        );
    }

    private function money(
        int $minorUnits,
    ): string {
        $absolute =
            abs(
                $minorUnits,
            );

        return (
            $minorUnits < 0
                ? '-'
                : ''
        )
        .intdiv(
            $absolute,
            100,
        )
        .'.'
        .str_pad(
            (string) (
                $absolute
                % 100
            ),
            2,
            '0',
            STR_PAD_LEFT,
        );
    }

    private function excluded(
        string $reason,
        ?MerchantListingReadinessResultData $readiness = null,
    ): MerchantFeedBuildResultData {
        return new MerchantFeedBuildResultData(
            item: null,

            exclusionReasons: [
                $reason,
            ],

            warnings: [],

            readiness: $readiness,
        );
    }
}
