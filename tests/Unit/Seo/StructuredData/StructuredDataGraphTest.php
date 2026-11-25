<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\StructuredData;

use App\Domain\Seo\StructuredData\Contracts\StructuredDataNode;
use App\Domain\Seo\StructuredData\Data\StructuredDataGraph;
use PHPUnit\Framework\TestCase;

final class StructuredDataGraphTest extends TestCase
{
    public function test_empty_graph_reports_empty(): void
    {
        $graph =
            new StructuredDataGraph(
                [],
            );

        self::assertTrue(
            $graph->isEmpty(),
        );

        self::assertSame(
            [],
            $graph->toArray(),
        );
    }

    public function test_graph_serializes_nodes_in_order(): void
    {
        $graph =
            new StructuredDataGraph([
                $this->node(
                    'Organization',
                    'MWT Store',
                ),

                $this->node(
                    'WebSite',
                    'MWT Store',
                ),
            ]);

        self::assertFalse(
            $graph->isEmpty(),
        );

        self::assertSame(
            [
                [
                    '@type' => 'Organization',

                    'name' => 'MWT Store',
                ],

                [
                    '@type' => 'WebSite',

                    'name' => 'MWT Store',
                ],
            ],
            $graph->toArray(),
        );
    }

    private function node(
        string $type,
        string $name,
    ): StructuredDataNode {
        return new class($type, $name) implements StructuredDataNode
        {
            public function __construct(
                private readonly string $type,
                private readonly string $name,
            ) {}

            public function toStructuredData(): array
            {
                return [
                    '@type' => $this->type,

                    'name' => $this->name,
                ];
            }
        };
    }
}
