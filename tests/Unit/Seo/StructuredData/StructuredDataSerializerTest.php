<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\StructuredData;

use App\Domain\Seo\StructuredData\Contracts\StructuredDataNode;
use App\Domain\Seo\StructuredData\Data\StructuredDataDocument;
use App\Domain\Seo\StructuredData\Data\StructuredDataGraph;
use App\Domain\Seo\StructuredData\Services\StructuredDataSerializer;
use PHPUnit\Framework\TestCase;

final class StructuredDataSerializerTest extends TestCase
{
    public function test_it_serializes_valid_json_ld(): void
    {
        $node =
            new class implements StructuredDataNode
            {
                public function toStructuredData(): array
                {
                    return [
                        '@type' => 'Organization',

                        'name' => 'MicroWeb Technology',

                        'url' => 'https://example.com',
                    ];
                }
            };

        $document =
            new StructuredDataDocument(
                new StructuredDataGraph([
                    $node,
                ]),
            );

        $json =
            (new StructuredDataSerializer)
                ->serialize(
                    $document,
                );

        $decoded =
            json_decode(
                $json,
                true,
                512,
                JSON_THROW_ON_ERROR,
            );

        self::assertSame(
            'https://schema.org',
            $decoded['@context'],
        );

        self::assertSame(
            'Organization',
            $decoded['@graph'][0]['@type'],
        );

        self::assertSame(
            'MicroWeb Technology',
            $decoded['@graph'][0]['name'],
        );
    }

    public function test_urls_are_not_unnecessarily_escaped(): void
    {
        $node =
            new class implements StructuredDataNode
            {
                public function toStructuredData(): array
                {
                    return [
                        '@type' => 'WebSite',

                        'url' => 'https://example.com/products/test',
                    ];
                }
            };

        $document =
            new StructuredDataDocument(
                new StructuredDataGraph([
                    $node,
                ]),
            );

        $json =
            (new StructuredDataSerializer)
                ->serialize(
                    $document,
                );

        self::assertStringContainsString(
            'https://example.com/products/test',
            $json,
        );

        self::assertStringNotContainsString(
            'https:\\/\\/',
            $json,
        );
    }
}
