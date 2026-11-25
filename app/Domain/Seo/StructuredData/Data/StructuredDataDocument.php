<?php

declare(strict_types=1);

namespace App\Domain\Seo\StructuredData\Data;

final readonly class StructuredDataDocument
{
    public function __construct(
        public StructuredDataGraph $graph,
    ) {}

    public function isEmpty(): bool
    {
        return $this
            ->graph
            ->isEmpty();
    }

    /**
     * @return array{
     *     '@context': string,
     *     '@graph': list<array<string, mixed>>
     * }
     */
    public function toArray(): array
    {
        return [
            '@context' => 'https://schema.org',

            '@graph' => $this
                ->graph
                ->toArray(),
        ];
    }
}
