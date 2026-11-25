<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\StructuredData;

use App\Domain\Seo\StructuredData\Nodes\OfferStructuredDataNode;
use App\Domain\Seo\StructuredData\Nodes\VariantProductStructuredDataNode;
use PHPUnit\Framework\TestCase;

final class VariantProductStructuredDataNodeTest extends TestCase
{
    public function test_it_builds_variant_product_schema_with_group_reference_and_offer(): void
    {
        $offer =
            new OfferStructuredDataNode(
                id: 'https://example.com/products/cable#variant-41-offer',

                url: 'https://example.com/products/cable?variant=41',

                price: '850.00',

                priceCurrency: 'BDT',

                availability: 'https://schema.org/InStock',
            );

        $node =
            new VariantProductStructuredDataNode(
                id: 'https://example.com/products/cable#variant-41',

                groupId: 'https://example.com/products/cable#product-group',

                url: 'https://example.com/products/cable?variant=41',

                name: 'USB Cable - Black, 2 Meter',

                sku: 'CAB-BLK-2M',

                offer: $offer,

                variantProperties: [
                    'color' => 'Black',

                    'size' => '2 Meter',
                ],
            );

        self::assertSame(
            [
                '@type' => 'Product',

                '@id' => 'https://example.com/products/cable#variant-41',

                'isVariantOf' => [
                    '@id' => 'https://example.com/products/cable#product-group',
                ],

                'url' => 'https://example.com/products/cable?variant=41',

                'name' => 'USB Cable - Black, 2 Meter',

                'sku' => 'CAB-BLK-2M',

                'color' => 'Black',

                'size' => '2 Meter',

                'offers' => [
                    '@type' => 'Offer',

                    '@id' => 'https://example.com/products/cable#variant-41-offer',

                    'url' => 'https://example.com/products/cable?variant=41',

                    'price' => '850.00',

                    'priceCurrency' => 'BDT',

                    'availability' => 'https://schema.org/InStock',
                ],
            ],
            $node->toStructuredData(),
        );
    }

    public function test_variant_properties_are_optional(): void
    {
        $node =
            new VariantProductStructuredDataNode(
                id: 'https://example.com/products/cable#variant-42',

                groupId: 'https://example.com/products/cable#product-group',

                url: 'https://example.com/products/cable?variant=42',

                name: 'USB Cable - CAB-42',

                sku: 'CAB-42',

                offer: new OfferStructuredDataNode(
                    id: 'https://example.com/products/cable#variant-42-offer',

                    url: 'https://example.com/products/cable?variant=42',

                    price: '900.00',

                    priceCurrency: 'BDT',

                    availability: 'https://schema.org/InStock',
                ),
            );

        $data =
            $node->toStructuredData();

        self::assertArrayNotHasKey(
            'color',
            $data,
        );

        self::assertArrayNotHasKey(
            'size',
            $data,
        );

        self::assertSame(
            'CAB-42',
            $data['sku'],
        );
    }
}
