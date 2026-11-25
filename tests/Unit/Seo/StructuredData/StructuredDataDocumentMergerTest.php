<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\StructuredData;

use App\Domain\Seo\StructuredData\Contracts\StructuredDataNode;
use App\Domain\Seo\StructuredData\Data\StructuredDataDocument;
use App\Domain\Seo\StructuredData\Data\StructuredDataGraph;
use App\Domain\Seo\StructuredData\Services\StructuredDataDocumentMerger;
use PHPUnit\Framework\TestCase;

final class StructuredDataDocumentMergerTest extends TestCase
{
    public function test_documents_are_merged_in_order(): void
    {
        $breadcrumb =
            $this->document(
                'BreadcrumbList',
            );

        $product =
            $this->document(
                'Product',
            );

        $merged =
            (new StructuredDataDocumentMerger)
                ->merge(
                    $breadcrumb,
                    $product,
                );

        self::assertNotNull(
            $merged,
        );

        self::assertSame(
            [
                'BreadcrumbList',
                'Product',
            ],
            array_column(
                $merged
                    ->toArray()['@graph'],
                '@type',
            ),
        );
    }

    public function test_null_documents_are_ignored(): void
    {
        $merged =
            (new StructuredDataDocumentMerger)
                ->merge(
                    null,
                    $this->document(
                        'Product',
                    ),
                );

        self::assertNotNull(
            $merged,
        );

        self::assertCount(
            1,
            $merged
                ->toArray()['@graph'],
        );
    }

    public function test_all_null_documents_return_null(): void
    {
        self::assertNull(
            (new StructuredDataDocumentMerger)
                ->merge(
                    null,
                    null,
                ),
        );
    }

    private function document(
        string $type,
    ): StructuredDataDocument {
        $node =
            new class($type) implements StructuredDataNode
            {
                public function __construct(
                    private readonly string $type,
                ) {}

                public function toStructuredData(): array
                {
                    return [
                        '@type' => $this->type,
                    ];
                }
            };

        return new StructuredDataDocument(
            graph: new StructuredDataGraph([
                $node,
            ]),
        );
    }
}
