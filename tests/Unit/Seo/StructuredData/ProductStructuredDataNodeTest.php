<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\StructuredData;

use App\Domain\Seo\StructuredData\Nodes\OfferStructuredDataNode;
use App\Domain\Seo\StructuredData\Nodes\ProductStructuredDataNode;
use PHPUnit\Framework\TestCase;

final class ProductStructuredDataNodeTest extends TestCase
{
    public function test_it_builds_product_with_offer(): void
    {
        $offer =
            new OfferStructuredDataNode(
                id: 'https://example.com/products/charger#offer',

                url: 'https://example.com/products/charger',

                price: '4850.00',

                priceCurrency: 'BDT',
            );

        $node =
            new ProductStructuredDataNode(
                id: 'https://example.com/products/charger#product',

                url: 'https://example.com/products/charger',

                name: 'USB Charger',

                description: 'Fast USB charger.',

                sku: 'CHG-001',

                images: [
                    'https://example.com/storage/charger.jpg',
                ],

                brandName: 'WiWU',

                offer: $offer,
            );

        $data =
            $node->toStructuredData();

        self::assertSame(
            'Product',
            $data['@type'],
        );

        self::assertSame(
            'CHG-001',
            $data['sku'],
        );

        self::assertSame(
            [
                '@type' => 'Brand',

                'name' => 'WiWU',
            ],
            $data['brand'],
        );

        self::assertSame(
            [
                'https://example.com/storage/charger.jpg',
            ],
            $data['image'],
        );

        self::assertSame(
            'Offer',
            $data['offers']['@type'],
        );

        self::assertSame(
            '4850.00',
            $data['offers']['price'],
        );
    }

    public function test_optional_product_fields_are_omitted(): void
    {
        $node =
            new ProductStructuredDataNode(
                id: 'https://example.com/products/basic#product',

                url: 'https://example.com/products/basic',

                name: 'Basic Product',
            );

        $data =
            $node->toStructuredData();

        self::assertArrayNotHasKey(
            'description',
            $data,
        );

        self::assertArrayNotHasKey(
            'sku',
            $data,
        );

        self::assertArrayNotHasKey(
            'image',
            $data,
        );

        self::assertArrayNotHasKey(
            'brand',
            $data,
        );

        self::assertArrayNotHasKey(
            'offers',
            $data,
        );
    }
}
