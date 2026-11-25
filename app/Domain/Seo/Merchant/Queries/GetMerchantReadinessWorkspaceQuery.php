<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Queries;

use App\Domain\Seo\Merchant\Data\MerchantFeedCatalogData;
use App\Domain\Seo\Merchant\Data\MerchantFeedCatalogEntryData;
use App\Domain\Seo\Merchant\Data\MerchantListingFindingData;
use App\Domain\Seo\Merchant\Data\MerchantPolicyReadinessData;
use App\Domain\Seo\Merchant\Data\MerchantReadinessItemData;
use App\Domain\Seo\Merchant\Data\MerchantReadinessSummaryData;
use App\Domain\Seo\Merchant\Data\MerchantReadinessWorkspaceData;
use App\Domain\Seo\Merchant\Enums\MerchantListingFindingSeverity;
use App\Domain\Seo\Merchant\Enums\MerchantListingReadinessStatus;
use App\Domain\Seo\Merchant\MerchantListingReadinessPolicy;
use App\Domain\Seo\Merchant\Services\MerchantOrganizationPolicyStructuredDataResolver;
use App\Domain\Seo\Merchant\Services\MerchantProductFeedCatalogBuilder;
use App\Domain\Seo\Merchant\Services\MerchantReadinessFindingFactory;
use App\Domain\Seo\Merchant\Services\MerchantStructuredDataPolicyService;
use App\Domain\Seo\Services\SeoSettingsService;

final readonly class GetMerchantReadinessWorkspaceQuery
{
    public function __construct(
        private MerchantProductFeedCatalogBuilder $catalog,
        private MerchantReadinessFindingFactory $findings,
        private MerchantStructuredDataPolicyService $policies,
        private MerchantOrganizationPolicyStructuredDataResolver $policyStructuredData,
        private SeoSettingsService $seoSettings,
    ) {}

    public function get(): MerchantReadinessWorkspaceData
    {
        return $this->fromCatalog(
            $this->catalog->build(),
        );
    }

    public function fromCatalog(
        MerchantFeedCatalogData $catalog,
    ): MerchantReadinessWorkspaceData {
        /** @var list<MerchantReadinessItemData> $items */
        $items = [];

        /** @var array<int, true> $productIds */
        $productIds = [];

        /** @var array<string, int> $findingCounts */
        $findingCounts = [];
        $severityCounts = [
            'blocker' => 0,
            'warning' => 0,
            'info' => 0,
        ];
        $ready = 0;
        $needsWork = 0;
        $blocked = 0;
        $feedEligible = 0;
        $outOfStock = 0;

        foreach (
            $catalog->entries as $entry
        ) {
            $productIds[
                $entry->productId
            ] = true;

            $findings =
                $this->findingsFor(
                    $entry,
                );

            $status =
                $this->status(
                    $findings,
                );

            match ($status) {
                MerchantListingReadinessStatus::Ready => $ready++,

                MerchantListingReadinessStatus::NeedsWork => $needsWork++,

                MerchantListingReadinessStatus::Blocked => $blocked++,
            };

            if ($entry->result->included()) {
                $feedEligible++;
            }

            $availability =
                $this->availability(
                    $entry,
                );

            if ($availability === 'out_of_stock') {
                $outOfStock++;
            }

            foreach ($findings as $finding) {
                $findingCounts[
                    $finding->id
                ] =
                    ($findingCounts[
                        $finding->id
                    ] ?? 0)
                    + 1;

                match ($finding->severity) {
                    MerchantListingFindingSeverity::Blocker => $severityCounts['blocker']++,

                    MerchantListingFindingSeverity::Warning => $severityCounts['warning']++,

                    MerchantListingFindingSeverity::Info => $severityCounts['info']++,
                };
            }

            $items[] =
                new MerchantReadinessItemData(
                    productId: $entry->productId,

                    productName: $entry->productName,

                    variantId: $entry->variantId,

                    variantLabel: $entry->variantLabel,

                    feedId: $entry->result
                        ->item
                        ?->id,

                    status: $status,

                    feedEligible: $entry->result
                        ->included(),

                    availability: $availability,

                    identifierState: $this->identifierState(
                        $entry,
                    ),

                    findings: $findings,
                );
        }

        ksort(
            $findingCounts,
            SORT_STRING,
        );

        return new MerchantReadinessWorkspaceData(
            policyVersion: MerchantListingReadinessPolicy::VERSION,

            summary: new MerchantReadinessSummaryData(
                productsConsidered: count(
                    $productIds,
                ),

                itemCandidates: count(
                    $items,
                ),

                ready: $ready,

                needsWork: $needsWork,

                blocked: $blocked,

                feedEligible: $feedEligible,

                excluded: count(
                    $items,
                )
                    - $feedEligible,

                outOfStock: $outOfStock,

                findingCounts: $findingCounts,

                severityCounts: $severityCounts,

                truncated: $catalog->truncated,

                maxItems: $catalog->maxItems,
            ),

            policy: $this->policyReadiness(),

            items: $items,
        );
    }

    /**
     * @return list<MerchantListingFindingData>
     */
    private function findingsFor(
        MerchantFeedCatalogEntryData $entry,
    ): array {
        $findings = [];

        if ($entry->result->readiness !== null) {
            foreach (
                $entry->result
                    ->readiness
                    ->findings as $finding
            ) {
                $findings[
                    $finding->id
                ] =
                    $finding;
            }
        }

        foreach (
            $entry->result
                ->exclusionReasons as $id
        ) {
            if (
                array_key_exists(
                    $id,
                    $findings,
                )
            ) {
                continue;
            }

            $findings[
                $id
            ] =
                $this->findings
                    ->exclusion(
                        $id,
                    );
        }

        foreach (
            $entry->result
                ->warnings as $id
        ) {
            if (
                array_key_exists(
                    $id,
                    $findings,
                )
            ) {
                continue;
            }

            $findings[
                $id
            ] =
                $this->findings
                    ->warning(
                        $id,
                    );
        }

        return array_values(
            $findings,
        );
    }

    /**
     * @param  list<MerchantListingFindingData>  $findings
     */
    private function status(
        array $findings,
    ): MerchantListingReadinessStatus {
        foreach ($findings as $finding) {
            if (
                $finding->severity ===
                MerchantListingFindingSeverity::Blocker
            ) {
                return MerchantListingReadinessStatus::Blocked;
            }
        }

        foreach ($findings as $finding) {
            if (
                $finding->severity ===
                MerchantListingFindingSeverity::Warning
            ) {
                return MerchantListingReadinessStatus::NeedsWork;
            }
        }

        return MerchantListingReadinessStatus::Ready;
    }

    private function availability(
        MerchantFeedCatalogEntryData $entry,
    ): ?string {
        $signal =
            $entry->result
                ->readiness
                ?->signals[
                    'availability'
                ]
            ?? null;

        if (is_string($signal)) {
            return $signal;
        }

        return $entry->result
            ->item
            ?->availability;
    }

    private function identifierState(
        MerchantFeedCatalogEntryData $entry,
    ): ?string {
        $signal =
            $entry->result
                ->readiness
                ?->signals[
                    'identifier_state'
                ]
            ?? null;

        return is_string($signal)
            ? $signal
            : null;
    }

    private function policyReadiness(): MerchantPolicyReadinessData
    {
        $settings =
            $this->seoSettings
                ->get();

        $policy =
            $this->policies
                ->get();

        $document =
            $this->policyStructuredData
                ->resolve();

        $organization =
            $document['@graph'][0]
            ?? [];

        return new MerchantPolicyReadinessData(
            structuredDataEnabled: $settings->enableStructuredData,

            shippingMarkupEnabled: $policy->shippingMarkupEnabled,

            shippingPublished: array_key_exists(
                'hasShippingService',
                $organization,
            ),

            shippingOmissionReasons: $this->policyStructuredData
                ->shippingOmissionReasons(),

            returnPolicyEnabled: $policy->returnPolicyEnabled,

            returnPolicyPublished: array_key_exists(
                'hasMerchantReturnPolicy',
                $organization,
            ),

            policyVersion: $policy->version,
        );
    }
}
