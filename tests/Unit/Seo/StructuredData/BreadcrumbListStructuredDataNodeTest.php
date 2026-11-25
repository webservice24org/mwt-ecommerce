<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\StructuredData;

use App\Domain\Seo\StructuredData\Data\BreadcrumbStructuredDataItemData;
use App\Domain\Seo\StructuredData\Nodes\BreadcrumbListStructuredDataNode;
use PHPUnit\Framework\TestCase;

final class BreadcrumbListStructuredDataNodeTest extends TestCase
{
    public function test_it_builds_ordered_breadcrumb_list(): void
    {
        $node =
            new BreadcrumbListStructuredDataNode([
                new BreadcrumbStructuredDataItemData(
                    name: 'Home',

                    path: 'https://example.com',
                ),

                new BreadcrumbStructuredDataItemData(
                    name: 'Shop',

                    path: 'https://example.com/products',
                ),

                new BreadcrumbStructuredDataItemData(
                    name: 'USB Charger',
                ),
            ]);

        self::assertSame(
            [
                '@type' => 'BreadcrumbList',

                'itemListElement' => [
                    [
                        '@type' => 'ListItem',

                        'position' => 1,

                        'name' => 'Home',

                        'item' => 'https://example.com',
                    ],

                    [
                        '@type' => 'ListItem',

                        'position' => 2,

                        'name' => 'Shop',

                        'item' => 'https://example.com/products',
                    ],

                    [
                        '@type' => 'ListItem',

                        'position' => 3,

                        'name' => 'USB Charger',
                    ],
                ],
            ],
            $node->toStructuredData(),
        );
    }
}
