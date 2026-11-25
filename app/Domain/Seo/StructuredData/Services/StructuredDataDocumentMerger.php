<?php

declare(strict_types=1);

namespace App\Domain\Seo\StructuredData\Services;

use App\Domain\Seo\StructuredData\Data\StructuredDataDocument;
use App\Domain\Seo\StructuredData\Data\StructuredDataGraph;

final class StructuredDataDocumentMerger
{
    public function merge(
        ?StructuredDataDocument ...$documents,
    ): ?StructuredDataDocument {
        $nodes = [];

        foreach ($documents as $document) {
            if ($document === null) {
                continue;
            }

            foreach (
                $document
                    ->graph
                    ->nodes as $node
            ) {
                $nodes[] =
                    $node;
            }
        }

        if ($nodes === []) {
            return null;
        }

        return new StructuredDataDocument(
            graph: new StructuredDataGraph(
                nodes: $nodes,
            ),
        );
    }
}
