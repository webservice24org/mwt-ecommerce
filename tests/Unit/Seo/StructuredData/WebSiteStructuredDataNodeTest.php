<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\StructuredData;

use App\Domain\Seo\StructuredData\Nodes\WebSiteStructuredDataNode;
use PHPUnit\Framework\TestCase;

final class WebSiteStructuredDataNodeTest extends TestCase
{
    public function test_it_builds_website_with_publisher_reference(): void
    {
        $node =
            new WebSiteStructuredDataNode(
                id: 'https://example.com/#website',

                name: 'MWT Store',

                url: 'https://example.com',

                publisherId: 'https://example.com/#organization',
            );

        self::assertSame(
            [
                '@type' => 'WebSite',

                '@id' => 'https://example.com/#website',

                'name' => 'MWT Store',

                'url' => 'https://example.com',

                'publisher' => [
                    '@id' => 'https://example.com/#organization',
                ],
            ],
            $node->toStructuredData(),
        );
    }
}
