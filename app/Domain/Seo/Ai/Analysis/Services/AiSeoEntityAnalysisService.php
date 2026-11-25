<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Analysis\Services;

use App\Domain\Seo\Ai\Analysis\Data\AiSeoAnalysisFindingData;
use App\Domain\Seo\Ai\Analysis\Data\AiSeoAnalysisRuleResultData;
use App\Domain\Seo\Ai\Data\AiSeoEntitySnapshotData;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Services\AiSeoEntitySnapshotBuilder;
use App\Domain\Seo\Ai\Services\AiSeoSnapshotHasher;
use App\Domain\Seo\Data\ResolvedSeoData;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Domain\Seo\Services\StorefrontSeoResolver;
use App\Models\AiSeoAnalysis;
use JsonException;

final readonly class AiSeoEntityAnalysisService
{
    public function __construct(
        private AiSeoAnalysisEntityLoader $entities,
        private AiSeoEntitySnapshotBuilder $snapshots,
        private StorefrontSeoResolver $seo,
        private SeoSettingsService $seoSettings,
        private AiSeoAnalysisEngine $engine,
        private AiSeoSnapshotHasher $snapshotHasher,
        private AiSeoAnalysisInputHasher $analysisInputHasher,
    ) {}

    /**
     * @throws JsonException
     */
    public function analyze(
        AiSeoEntityType $entityType,
        int $entityId,
        ?int $analyzedByAdminId = null,
    ): AiSeoAnalysis {
        return match ($entityType) {
            AiSeoEntityType::Product => $this->analyzeProduct(
                $entityId,
                $analyzedByAdminId,
            ),

            AiSeoEntityType::Category => $this->analyzeCategory(
                $entityId,
                $analyzedByAdminId,
            ),

            AiSeoEntityType::Brand => $this->analyzeBrand(
                $entityId,
                $analyzedByAdminId,
            ),

            AiSeoEntityType::Page => $this->analyzePage(
                $entityId,
                $analyzedByAdminId,
            ),
        };
    }

    /**
     * @throws JsonException
     */
    private function analyzeProduct(
        int $entityId,
        ?int $analyzedByAdminId,
    ): AiSeoAnalysis {
        $entity =
            $this
                ->entities
                ->product(
                    $entityId,
                );

        $snapshot =
            $this
                ->snapshots
                ->build(
                    AiSeoEntityType::Product,
                    $entity->id,
                );

        $resolved =
            $this
                ->seo
                ->resolve(
                    entity: $entity,

                    settings: $this
                        ->seoSettings
                        ->get(),
                );

        return $this->persist(
            $snapshot,
            $resolved,
            $analyzedByAdminId,
        );
    }

    /**
     * @throws JsonException
     */
    private function analyzeCategory(
        int $entityId,
        ?int $analyzedByAdminId,
    ): AiSeoAnalysis {
        $entity =
            $this
                ->entities
                ->category(
                    $entityId,
                );

        $snapshot =
            $this
                ->snapshots
                ->build(
                    AiSeoEntityType::Category,
                    $entity->id,
                );

        $resolved =
            $this
                ->seo
                ->resolve(
                    entity: $entity,

                    settings: $this
                        ->seoSettings
                        ->get(),
                );

        return $this->persist(
            $snapshot,
            $resolved,
            $analyzedByAdminId,
        );
    }

    /**
     * @throws JsonException
     */
    private function analyzeBrand(
        int $entityId,
        ?int $analyzedByAdminId,
    ): AiSeoAnalysis {
        $entity =
            $this
                ->entities
                ->brand(
                    $entityId,
                );

        $snapshot =
            $this
                ->snapshots
                ->build(
                    AiSeoEntityType::Brand,
                    $entity->id,
                );

        $resolved =
            $this
                ->seo
                ->resolve(
                    entity: $entity,

                    settings: $this
                        ->seoSettings
                        ->get(),
                );

        return $this->persist(
            $snapshot,
            $resolved,
            $analyzedByAdminId,
        );
    }

    /**
     * @throws JsonException
     */
    private function analyzePage(
        int $entityId,
        ?int $analyzedByAdminId,
    ): AiSeoAnalysis {
        $entity =
            $this
                ->entities
                ->page(
                    $entityId,
                );

        $snapshot =
            $this
                ->snapshots
                ->build(
                    AiSeoEntityType::Page,
                    $entity->id,
                );

        $resolved =
            $this
                ->seo
                ->resolve(
                    entity: $entity,

                    settings: $this
                        ->seoSettings
                        ->get(),
                );

        return $this->persist(
            $snapshot,
            $resolved,
            $analyzedByAdminId,
        );
    }

    /**
     * @throws JsonException
     */
    private function persist(
        AiSeoEntitySnapshotData $snapshot,
        ResolvedSeoData $resolvedSeo,
        ?int $analyzedByAdminId,
    ): AiSeoAnalysis {
        $sourceSnapshot =
            $snapshot
                ->toArray();

        $resolvedSeoPayload =
            $resolvedSeo
                ->toArray();

        $result =
            $this
                ->engine
                ->analyze(
                    $snapshot,
                    $resolvedSeo,
                );

        $now =
            now();

        return AiSeoAnalysis::query()
            ->create([
                'entity_type' => $snapshot
                    ->entityType,

                'entity_id' => $snapshot
                    ->entityId,

                'analysis_version' => $result
                    ->version,

                'snapshot_schema_version' => AiSeoEntitySnapshotData::SCHEMA_VERSION,

                'snapshot_hash' => $this
                    ->snapshotHasher
                    ->hash(
                        $sourceSnapshot,
                    ),

                'analysis_input_hash' => $this
                    ->analysisInputHasher
                    ->hash(
                        analysisVersion: $result
                            ->version,

                        sourceSnapshot: $sourceSnapshot,

                        resolvedSeo: $resolvedSeoPayload,
                    ),

                'source_snapshot' => $sourceSnapshot,

                'resolved_seo' => $resolvedSeoPayload,

                'score' => $result
                    ->score,

                'grade' => $result
                    ->grade,

                'breakdown' => $result
                    ->breakdown,

                'rules' => array_map(
                    static fn (
                        AiSeoAnalysisRuleResultData $rule,
                    ): array => $rule->toArray(),
                    $result
                        ->rules,
                ),

                'findings' => array_map(
                    static fn (
                        AiSeoAnalysisFindingData $finding,
                    ): array => $finding->toArray(),
                    $result
                        ->findings,
                ),

                'analyzed_by_admin_id' => $analyzedByAdminId,

                'analyzed_at' => $now,

                'created_at' => $now,
            ]);
    }
}
