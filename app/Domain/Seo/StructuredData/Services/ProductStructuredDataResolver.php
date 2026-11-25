<?php

declare(strict_types=1);

namespace App\Domain\Seo\StructuredData\Services;

use App\Domain\Catalog\Data\Storefront\StorefrontProductDetailData;
use App\Domain\Catalog\Enums\ProductType;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\CanonicalUrlResolver;
use App\Domain\Seo\StructuredData\Data\StructuredDataDocument;
use App\Domain\Seo\StructuredData\Nodes\OfferStructuredDataNode;
use App\Domain\Seo\StructuredData\Nodes\ProductStructuredDataNode;

final readonly class ProductStructuredDataResolver
{
    public function __construct(
        private StructuredDataResolver $structuredData,
        private StructuredDataMoneyFormatter $money,
        private StructuredDataCurrencyResolver $currency,
        private CanonicalUrlResolver $canonical,
        private StructuredDataAvailabilityResolver $availability,
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

        /*
         * Variable-product structured data belongs to F.5.
         *
         * Do not pretend a set of variants is one simple
         * Product/Offer and do not use AggregateOffer for
         * variants.
         */
        if (
            $product->type !==
            ProductType::Simple
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

        $name =
            $this->nullableString(
                $product->name,
            );

        if ($name === null) {
            return null;
        }

        $productNode =
            new ProductStructuredDataNode(
                id: $canonicalUrl
                .'#product',

                url: $canonicalUrl,

                name: $name,

                description: $this->description(
                    $product,
                ),

                sku: $this->nullableString(
                    $product->sku,
                ),

                images: $this->images(
                    settings: $settings,

                    product: $product,
                ),

                brandName: $this->nullableString(
                    $product
                        ->brand
                        ?->name,
                ),

                offer: $this->offer(
                    product: $product,

                    canonicalUrl: $canonicalUrl,
                ),
            );

        return $this
            ->structuredData
            ->resolve(
                settings: $settings,

                nodes: [
                    $productNode,
                ],
            );
    }

    private function offer(
        StorefrontProductDetailData $product,
        string $canonicalUrl,
    ): ?OfferStructuredDataNode {
        $price =
            $product
                ->pricing
                ->price;

        if (
            $price === null
            || $price <= 0
        ) {
            return null;
        }

        return new OfferStructuredDataNode(
            id: $canonicalUrl
            .'#offer',

            url: $canonicalUrl,

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

                    stockQuantity: $product
                        ->stockQuantity,
                )
                ->value,
        );
    }

    /**
     * @return list<string>
     */
    private function images(
        SeoSettingsData $settings,
        StorefrontProductDetailData $product,
    ): array {
        $images = [];

        foreach (
            $product->images as $image
        ) {
            $url =
                $this->imageUrl(
                    value: $image->url,

                    canonicalHost: $settings
                        ->canonicalHost,
                );

            if ($url !== null) {
                $images[] =
                    $url;
            }
        }

        return array_values(
            array_unique(
                $images,
            ),
        );
    }

    private function imageUrl(
        string $value,
        ?string $canonicalHost,
    ): ?string {
        $value =
            trim(
                $value,
            );

        if ($value === '') {
            return null;
        }

        $absolute =
            $this->absoluteHttpUrl(
                $value,
            );

        if ($absolute !== null) {
            return $absolute;
        }

        /*
         * Only genuine local paths may be resolved
         * against the canonical host.
         */
        if (
            ! str_starts_with(
                $value,
                '/',
            )
            || str_starts_with(
                $value,
                '//',
            )
        ) {
            return null;
        }

        $host =
            trim(
                (string) (
                    $canonicalHost
                    ?? config(
                        'app.url',
                        '',
                    )
                ),
            );

        if ($host === '') {
            return null;
        }

        return $this
            ->absoluteHttpUrl(
                $this
                    ->canonical
                    ->resolve(
                        canonicalOverride: null,

                        path: $value,

                        canonicalHost: $host,
                    ),
            );
    }

    private function description(
        StorefrontProductDetailData $product,
    ): ?string {
        foreach (
            [
                $product->shortDescription,
                $product->description,
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
