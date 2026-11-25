<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\StructuredData;

use App\Domain\Seo\StructuredData\Nodes\OfferStructuredDataNode;
use App\Domain\Seo\StructuredData\Nodes\ProductGroupStructuredDataNode;
use App\Domain\Seo\StructuredData\Nodes\VariantProductStructuredDataNode;
use PHPUnit\Framework\TestCase;

final class ProductGroupStructuredDataNodeTest extends TestCase
{
    public function test_it_builds_product_group_with_nested_variants(): void
    {
        $groupId =
            'https://example.com/products/cable#product-group';

        $first =
            $this->variant(
                id: 41,

                groupId: $groupId,

                sku: 'CAB-BLK',

                name: 'USB Cable - Black',

                color: 'Black',

                price: '850.00',
            );

        $second =
            $this->variant(
                id: 42,

                groupId: $groupId,

                sku: 'CAB-WHT',

                name: 'USB Cable - White',

                color: 'White',

                price: '900.00',
            );

        $node =
            new ProductGroupStructuredDataNode(
                id: $groupId,

                url: 'https://example.com/products/cable',

                name: 'USB Cable',

                productGroupId: 'CABLE-GROUP',

                variesBy: [
                    'https://schema.org/color',
                ],

                variants: [
                    $first,
                    $second,
                ],

                description: 'Braided USB cable.',

                brandName: 'MWT',
            );

        $data =
            $node->toStructuredData();

        self::assertSame(
            'ProductGroup',
            $data['@type'],
        );

        self::assertSame(
            $groupId,
            $data['@id'],
        );

        self::assertSame(
            'CABLE-GROUP',
            $data['productGroupID'],
        );

        self::assertSame(
            [
                'https://schema.org/color',
            ],
            $data['variesBy'],
        );

        self::assertSame(
            [
                '@type' => 'Brand',

                'name' => 'MWT',
            ],
            $data['brand'],
        );

        self::assertCount(
            2,
            $data['hasVariant'],
        );

        self::assertSame(
            'CAB-BLK',
            $data['hasVariant'][0]['sku'],
        );

        self::assertSame(
            'CAB-WHT',
            $data['hasVariant'][1]['sku'],
        );

        self::assertSame(
            'https://example.com/products/cable?variant=41',
            $data['hasVariant'][0]['url'],
        );

        self::assertSame(
            'https://example.com/products/cable?variant=42',
            $data['hasVariant'][1]['url'],
        );
    }

    public function test_optional_group_fields_are_omitted(): void
    {
        $groupId =
            'https://example.com/products/cable#product-group';

        $node =
            new ProductGroupStructuredDataNode(
                id: $groupId,

                url: 'https://example.com/products/cable',

                name: 'USB Cable',

                productGroupId: 'product-10',

                variesBy: [],

                variants: [
                    $this->variant(
                        id: 41,

                        groupId: $groupId,

                        sku: 'CAB-41',

                        name: 'USB Cable - CAB-41',

                        color: null,

                        price: '850.00',
                    ),
                ],
            );

        $data =
            $node->toStructuredData();

        self::assertArrayNotHasKey(
            'description',
            $data,
        );

        self::assertArrayNotHasKey(
            'brand',
            $data,
        );

        self::assertArrayNotHasKey(
            'variesBy',
            $data,
        );
    }

    private function variant(
        int $id,
        string $groupId,
        string $sku,
        string $name,
        ?string $color,
        string $price,
    ): VariantProductStructuredDataNode {
        $url =
            'https://example.com/products/cable?variant='
            .$id;

        $properties =
            $color === null
                ? []
                : [
                    'color' => $color,
                ];

        return new VariantProductStructuredDataNode(
            id: 'https://example.com/products/cable#variant-'
                .$id,

            groupId: $groupId,

            url: $url,

            name: $name,

            sku: $sku,

            offer: new OfferStructuredDataNode(
                id: 'https://example.com/products/cable#variant-'
                    .$id
                    .'-offer',

                url: $url,

                price: $price,

                priceCurrency: 'BDT',

                availability: 'https://schema.org/InStock',
            ),

            variantProperties: $properties,
        );
    }
}
