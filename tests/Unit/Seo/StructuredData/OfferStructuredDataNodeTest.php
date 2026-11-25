<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\StructuredData;

use App\Domain\Seo\StructuredData\Nodes\OfferStructuredDataNode;
use PHPUnit\Framework\TestCase;

final class OfferStructuredDataNodeTest extends TestCase
{
    public function test_it_builds_offer_schema(): void
    {
        $node =
            new OfferStructuredDataNode(
                id: 'https://example.com/products/charger#offer',

                url: 'https://example.com/products/charger',

                price: '4850.00',

                priceCurrency: 'BDT',
            );

        self::assertSame(
            [
                '@type' => 'Offer',

                '@id' => 'https://example.com/products/charger#offer',

                'url' => 'https://example.com/products/charger',

                'price' => '4850.00',

                'priceCurrency' => 'BDT',
            ],
            $node->toStructuredData(),
        );
    }
}
