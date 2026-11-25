<?php

declare(strict_types=1);

namespace App\Domain\Seo\StructuredData\Data;

use App\Domain\Seo\StructuredData\Contracts\StructuredDataNode;

final readonly class StructuredDataGraph
{
    /**
     * @param  list<StructuredDataNode>  $nodes
     */
    public function __construct(
        public array $nodes,
    ) {}

    public function isEmpty(): bool
    {
        return $this->nodes === [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(
            static fn (
                StructuredDataNode $node,
            ): array => $node->toStructuredData(),
            $this->nodes,
        );
    }
}
