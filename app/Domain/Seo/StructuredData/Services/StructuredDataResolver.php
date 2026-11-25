<?php

declare(strict_types=1);

namespace App\Domain\Seo\StructuredData\Services;

use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\StructuredData\Contracts\StructuredDataNode;
use App\Domain\Seo\StructuredData\Data\StructuredDataDocument;
use App\Domain\Seo\StructuredData\Data\StructuredDataGraph;

final class StructuredDataResolver
{
    /**
     * @param  list<StructuredDataNode>  $nodes
     */
    public function resolve(
        SeoSettingsData $settings,
        array $nodes,
    ): ?StructuredDataDocument {
        if (
            ! $settings->enableStructuredData
        ) {
            return null;
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
