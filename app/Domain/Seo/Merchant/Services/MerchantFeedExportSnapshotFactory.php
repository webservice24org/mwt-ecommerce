<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Services;

use App\Domain\Seo\Merchant\Data\MerchantFeedCatalogData;
use App\Domain\Seo\Merchant\Data\MerchantFeedExportSnapshotData;
use App\Domain\Seo\Merchant\Data\MerchantListingFindingData;
use App\Domain\Seo\Merchant\Data\MerchantReadinessWorkspaceData;
use App\Domain\Seo\Merchant\Enums\MerchantFeedExportFormat;

final readonly class MerchantFeedExportSnapshotFactory
{
    public function __construct(
        private MerchantTsvFeedSerializer $tsv,
    ) {}

    public function make(
        MerchantReadinessWorkspaceData $workspace,
        MerchantFeedExportFormat $format,
        ?MerchantFeedCatalogData $catalog = null,
    ): MerchantFeedExportSnapshotData {
        /** @var list<string> $eligibleIds */
        $eligibleIds = [];

        /** @var list<array{product_id: int, variant_id: int|null, finding_ids: list<string>}> $excluded */
        $excluded = [];

        foreach ($workspace->items as $item) {
            if (
                $item->feedEligible
                && $item->feedId !== null
            ) {
                $eligibleIds[] =
                    $item->feedId;

                continue;
            }

            $findingIds =
                array_map(
                    static fn (
                        MerchantListingFindingData $finding,
                    ): string => $finding->id,
                    $item->findings,
                );

            sort(
                $findingIds,
                SORT_STRING,
            );

            $excluded[] = [
                'product_id' => $item->productId,

                'variant_id' => $item->variantId,

                'finding_ids' => $findingIds,
            ];
        }

        sort(
            $eligibleIds,
            SORT_STRING,
        );

        usort(
            $excluded,
            static function (
                array $left,
                array $right,
            ): int {
                $product =
                    $left['product_id']
                    <=> $right['product_id'];

                if ($product !== 0) {
                    return $product;
                }

                return (
                    $left['variant_id']
                    ?? 0
                ) <=> (
                    $right['variant_id']
                    ?? 0
                );
            },
        );

        $feedContentSha256 =
            $this->feedContentSha256(
                format: $format,

                catalog: $catalog,
            );

        $snapshot = [
            'format' => $format->value,

            'schema_version' => MerchantFeedExportSnapshotData::SCHEMA_VERSION,

            'readiness_policy_version' => $workspace->policyVersion,

            'merchant_policy' => $workspace->policy
                ->toArray(),

            'summary' => [
                'products_considered' => $workspace->summary
                    ->productsConsidered,

                'item_candidates' => $workspace->summary
                    ->itemCandidates,

                'ready' => $workspace->summary
                    ->ready,

                'needs_work' => $workspace->summary
                    ->needsWork,

                'blocked' => $workspace->summary
                    ->blocked,

                'feed_eligible' => $workspace->summary
                    ->feedEligible,

                'excluded' => $workspace->summary
                    ->excluded,

                'out_of_stock' => $workspace->summary
                    ->outOfStock,

                'severity_counts' => $workspace->summary
                    ->severityCounts,

                'truncated' => $workspace->summary
                    ->truncated,

                'max_items' => $workspace->summary
                    ->maxItems,
            ],

            'eligible_feed_ids' => $eligibleIds,

            'excluded_items' => $excluded,

            'feed_content_sha256' => $feedContentSha256,
        ];

        return new MerchantFeedExportSnapshotData(
            schemaVersion: MerchantFeedExportSnapshotData::SCHEMA_VERSION,

            readinessPolicyVersion: $workspace->policyVersion,

            merchantPolicyVersion: $workspace->policy
                ->policyVersion,

            productsConsidered: $workspace->summary
                ->productsConsidered,

            itemCandidates: $workspace->summary
                ->itemCandidates,

            exportableCount: $workspace->summary
                ->feedEligible,

            excludedCount: $workspace->summary
                ->excluded,

            outOfStockCount: $workspace->summary
                ->outOfStock,

            blockerCount: $workspace->summary
                ->severityCounts['blocker'],

            warningCount: $workspace->summary
                ->severityCounts['warning'],

            infoCount: $workspace->summary
                ->severityCounts['info'],

            truncated: $workspace->summary
                ->truncated,

            maxItems: $workspace->summary
                ->maxItems,

            snapshot: $snapshot,

            hash: MerchantFeedExportSnapshotData::hash(
                $snapshot,
            ),
        );
    }

    private function feedContentSha256(
        MerchantFeedExportFormat $format,
        ?MerchantFeedCatalogData $catalog,
    ): ?string {
        if ($catalog === null) {
            return null;
        }

        return match ($format) {
            MerchantFeedExportFormat::Tsv => hash(
                'sha256',
                $this->tsv
                    ->serialize(
                        $catalog->items,
                    ),
            ),
        };
    }
}
