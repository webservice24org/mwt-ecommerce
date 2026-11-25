<?php

declare(strict_types=1);

namespace App\Domain\Seo\StructuredData\Services;

use App\Domain\Reviews\Data\StorefrontProductReviewAggregateData;
use App\Domain\Seo\StructuredData\Nodes\AggregateRatingStructuredDataNode;

final class AggregateRatingStructuredDataEnricher
{
    /**
     * @param  array<string, mixed>|null  $document
     * @return array<string, mixed>|null
     */
    public function enrich(
        ?array $document,
        ?StorefrontProductReviewAggregateData $aggregate,
    ): ?array {
        if (
            $document === null
            || $aggregate === null
            || ! $aggregate->isValid()
        ) {
            return $document;
        }

        $graph =
            $document['@graph']
            ?? null;

        if (! is_array($graph)) {
            return $document;
        }

        /** @var list<mixed> $graph */
        $graph =
            array_values(
                $graph,
            );

        $targetType =
            $this->targetType(
                $graph,
            );

        if ($targetType === null) {
            return $document;
        }

        $aggregateRating =
            new AggregateRatingStructuredDataNode(
                aggregate: $aggregate,
            );

        $aggregatePayload =
            $aggregateRating
                ->toStructuredData();

        $enriched = false;
        $normalizedGraph = [];

        foreach ($graph as $node) {
            if (
                ! $enriched
                && is_array(
                    $node,
                )
                && (
                    $node['@type']
                    ?? null
                ) === $targetType
            ) {
                $node['aggregateRating'] =
                    $aggregatePayload;

                $enriched = true;
            }

            $normalizedGraph[] =
                $node;
        }

        if (! $enriched) {
            return $document;
        }

        $document['@graph'] =
            $normalizedGraph;

        return $document;
    }

    /**
     * ProductGroup wins whenever it is present.
     *
     * This guarantees product-level reviews are attached once
     * to a variable product's group and are never duplicated
     * onto nested variant Product nodes.
     *
     * @param  list<mixed>  $graph
     */
    private function targetType(
        array $graph,
    ): ?string {
        $hasProduct = false;

        foreach ($graph as $node) {
            if (! is_array($node)) {
                continue;
            }

            $type =
                $node['@type']
                ?? null;

            if ($type === 'ProductGroup') {
                return 'ProductGroup';
            }

            if ($type === 'Product') {
                $hasProduct = true;
            }
        }

        return $hasProduct
            ? 'Product'
            : null;
    }
}
