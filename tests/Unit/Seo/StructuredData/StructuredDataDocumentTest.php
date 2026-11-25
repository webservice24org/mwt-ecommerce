<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\StructuredData;

use App\Domain\Seo\StructuredData\Contracts\StructuredDataNode;
use App\Domain\Seo\StructuredData\Data\StructuredDataDocument;
use App\Domain\Seo\StructuredData\Data\StructuredDataGraph;
use PHPUnit\Framework\TestCase;

final class StructuredDataDocumentTest extends TestCase
{
    public function test_document_uses_schema_org_context(): void
    {
        $node =
            new class implements StructuredDataNode
            {
                public function toStructuredData(): array
                {
                    return [
                        '@type' => 'Organization',

                        'name' => 'MWT Store',
                    ];
                }
            };

        $document =
            new StructuredDataDocument(
                new StructuredDataGraph([
                    $node,
                ]),
            );

        self::assertSame(
            [
                '@context' => 'https://schema.org',

                '@graph' => [
                    [
                        '@type' => 'Organization',

                        'name' => 'MWT Store',
                    ],
                ],
            ],
            $document->toArray(),
        );
    }
}
