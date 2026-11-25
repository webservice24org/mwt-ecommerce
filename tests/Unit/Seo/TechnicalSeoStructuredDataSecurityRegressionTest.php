<?php

declare(strict_types=1);

namespace Tests\Unit\Seo;

use App\Domain\Seo\StructuredData\Nodes\OfferStructuredDataNode;
use App\Domain\Seo\StructuredData\Nodes\ProductGroupStructuredDataNode;
use App\Domain\Seo\StructuredData\Nodes\VariantProductStructuredDataNode;
use PHPUnit\Framework\TestCase;

final class TechnicalSeoStructuredDataSecurityRegressionTest extends TestCase
{
    public function test_variant_product_keeps_group_identity_offer_currency_and_authoritative_availability(): void
    {
        $groupId = 'https://example.com/products/usb-cable#product-group';
        $variantUrl = 'https://example.com/products/usb-cable?variant=41';

        $variant = new VariantProductStructuredDataNode(
            id: 'https://example.com/products/usb-cable#variant-41',
            groupId: $groupId,
            url: $variantUrl,
            name: 'USB Cable - Black',
            sku: 'USB-BLK-41',
            offer: new OfferStructuredDataNode(
                id: 'https://example.com/products/usb-cable#variant-41-offer',
                url: $variantUrl,
                price: '850.00',
                priceCurrency: 'BDT',
                availability: 'https://schema.org/InStock',
            ),
            variantProperties: [
                'color' => 'Black',
            ],
        );

        $group = new ProductGroupStructuredDataNode(
            id: $groupId,
            url: 'https://example.com/products/usb-cable',
            name: 'USB Cable',
            productGroupId: 'USB-GROUP',
            variesBy: [
                'https://schema.org/color',
            ],
            variants: [
                $variant,
            ],
            description: 'Braided USB cable.',
            brandName: 'MWT',
        );

        $data = $group->toStructuredData();

        self::assertSame('ProductGroup', $data['@type']);
        self::assertSame($groupId, $data['@id']);
        self::assertSame('USB-GROUP', $data['productGroupID']);
        self::assertSame(['https://schema.org/color'], $data['variesBy']);
        self::assertCount(1, $data['hasVariant']);

        $variantData = $data['hasVariant'][0];

        self::assertSame('Product', $variantData['@type']);
        self::assertSame(['@id' => $groupId], $variantData['isVariantOf']);
        self::assertSame('USB-BLK-41', $variantData['sku']);
        self::assertSame('Black', $variantData['color']);
        self::assertSame('Offer', $variantData['offers']['@type']);
        self::assertSame('850.00', $variantData['offers']['price']);
        self::assertSame('BDT', $variantData['offers']['priceCurrency']);
        self::assertSame(
            'https://schema.org/InStock',
            $variantData['offers']['availability'],
        );
    }

    public function test_structured_data_nodes_do_not_invent_optional_or_unsupported_fields(): void
    {
        $groupId = 'https://example.com/products/usb-cable#product-group';

        $variant = new VariantProductStructuredDataNode(
            id: 'https://example.com/products/usb-cable#variant-42',
            groupId: $groupId,
            url: 'https://example.com/products/usb-cable?variant=42',
            name: 'USB Cable - USB-42',
            sku: 'USB-42',
            offer: new OfferStructuredDataNode(
                id: 'https://example.com/products/usb-cable#variant-42-offer',
                url: 'https://example.com/products/usb-cable?variant=42',
                price: '900.00',
                priceCurrency: 'BDT',
                availability: 'https://schema.org/OutOfStock',
            ),
        );

        $group = new ProductGroupStructuredDataNode(
            id: $groupId,
            url: 'https://example.com/products/usb-cable',
            name: 'USB Cable',
            productGroupId: 'USB-GROUP',
            variesBy: [],
            variants: [$variant],
        );

        $data = $group->toStructuredData();
        $variantData = $data['hasVariant'][0];

        self::assertArrayNotHasKey('description', $data);
        self::assertArrayNotHasKey('brand', $data);
        self::assertArrayNotHasKey('variesBy', $data);
        self::assertArrayNotHasKey('color', $variantData);
        self::assertArrayNotHasKey('size', $variantData);
        self::assertArrayNotHasKey('storage', $variantData);
    }
}
