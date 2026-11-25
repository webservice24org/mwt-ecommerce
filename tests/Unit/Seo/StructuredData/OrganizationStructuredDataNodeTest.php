<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\StructuredData;

use App\Domain\Seo\StructuredData\Nodes\OrganizationStructuredDataNode;
use PHPUnit\Framework\TestCase;

final class OrganizationStructuredDataNodeTest extends TestCase
{
    public function test_it_builds_organization_node(): void
    {
        $node =
            new OrganizationStructuredDataNode(
                id: 'https://example.com/#organization',

                name: 'MicroWeb Technology',

                url: 'https://example.com',

                logo: 'https://example.com/storage/logo.png',

                sameAs: [
                    'https://facebook.com/microweb',
                    'https://linkedin.com/company/microweb',
                ],
            );

        self::assertSame(
            [
                '@type' => 'Organization',

                '@id' => 'https://example.com/#organization',

                'name' => 'MicroWeb Technology',

                'url' => 'https://example.com',

                'logo' => [
                    '@type' => 'ImageObject',

                    'url' => 'https://example.com/storage/logo.png',
                ],

                'sameAs' => [
                    'https://facebook.com/microweb',
                    'https://linkedin.com/company/microweb',
                ],
            ],
            $node->toStructuredData(),
        );
    }

    public function test_optional_fields_are_omitted(): void
    {
        $node =
            new OrganizationStructuredDataNode(
                id: 'https://example.com/#organization',

                name: 'MWT Store',

                url: 'https://example.com',
            );

        $data =
            $node->toStructuredData();

        self::assertArrayNotHasKey(
            'logo',
            $data,
        );

        self::assertArrayNotHasKey(
            'sameAs',
            $data,
        );
    }
}
