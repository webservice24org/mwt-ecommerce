<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Ai\Workspace;

use App\Domain\Seo\Ai\Analysis\Enrichment\Enums\AiSeoAnalysisEnrichmentStatus;
use App\Domain\Seo\Ai\Analysis\Enums\AiSeoAnalysisGrade;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Workspace\Queries\GetSeoDashboardQuery;
use App\Models\AiSeoAnalysis;
use App\Models\AiSeoAnalysisEnrichment;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class GetSeoDashboardQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_dashboard_summary_classic_and_graphical_data_from_latest_entity_analyses(): void
    {
        $productA =
            Product::factory()->create();

        $productB =
            Product::factory()->create();

        $category =
            Category::factory()->create();

        $brand =
            Brand::factory()->create();

        $page =
            Page::factory()->create();

        $olderProductAnalysis =
            $this->analysis(
                entityType: AiSeoEntityType::Product,
                entityId: $productA->id,
                score: 42,
                grade: AiSeoAnalysisGrade::Poor,
                findings: [
                    $this->finding(
                        id: 'old_issue',
                        category: 'search_metadata',
                        severity: 'critical',
                    ),
                ],
                analyzedAt: now()->subDays(4),
            );

        $latestProductAnalysis =
            $this->analysis(
                entityType: AiSeoEntityType::Product,
                entityId: $productA->id,
                score: 88,
                grade: AiSeoAnalysisGrade::Good,
                findings: [
                    $this->finding(
                        id: 'social_image_missing',
                        category: 'social_coverage',
                        severity: 'warning',
                    ),
                ],
                analyzedAt: now()->subHours(8),
            );

        $productBAnalysis =
            $this->analysis(
                entityType: AiSeoEntityType::Product,
                entityId: $productB->id,
                score: 96,
                grade: AiSeoAnalysisGrade::Excellent,
                findings: [
                    $this->finding(
                        id: 'resolved_noindex_notice',
                        category: 'search_metadata',
                        severity: 'info',
                    ),
                ],
                analyzedAt: now()->subHours(5),
            );

        $categoryAnalysis =
            $this->analysis(
                entityType: AiSeoEntityType::Category,
                entityId: $category->id,
                score: 61,
                grade: AiSeoAnalysisGrade::NeedsWork,
                findings: [
                    $this->finding(
                        id: 'missing_resolved_description',
                        category: 'search_metadata',
                        severity: 'critical',
                    ),
                ],
                analyzedAt: now()->subHours(4),
            );

        $brandAnalysis =
            $this->analysis(
                entityType: AiSeoEntityType::Brand,
                entityId: $brand->id,
                score: 74,
                grade: AiSeoAnalysisGrade::Good,
                findings: [],
                analyzedAt: now()->subHours(3),
            );

        $pageAnalysis =
            $this->analysis(
                entityType: AiSeoEntityType::Page,
                entityId: $page->id,
                score: 91,
                grade: AiSeoAnalysisGrade::Excellent,
                findings: [
                    $this->finding(
                        id: 'thin_page_body',
                        category: 'source_content',
                        severity: 'warning',
                    ),
                ],
                analyzedAt: now()->subHours(2),
            );

        $this->enrichment(
            $latestProductAnalysis,
            AiSeoAnalysisEnrichmentStatus::Completed,
        );

        $this->enrichment(
            $categoryAnalysis,
            AiSeoAnalysisEnrichmentStatus::Failed,
        );

        $dashboard =
            app(
                GetSeoDashboardQuery::class,
            )->handle();

        self::assertSame(
            5,
            $dashboard['summary']['tracked_entities'],
        );

        self::assertSame(
            82,
            $dashboard['summary']['average_score'],
        );

        self::assertSame(
            1,
            $dashboard['summary']['critical_findings'],
        );

        self::assertSame(
            2,
            $dashboard['summary']['warning_findings'],
        );

        self::assertSame(
            1,
            $dashboard['summary']['info_findings'],
        );

        self::assertSame(
            1,
            $dashboard['summary']['completed_enrichments'],
        );

        self::assertSame(
            1,
            $dashboard['summary']['failed_enrichments'],
        );

        self::assertSame(
            100,
            $dashboard['summary']['coverage_percent'],
        );

        self::assertSame(
            2,
            $dashboard['coverage']['products']['analyzed'],
        );

        self::assertSame(
            96,
            $dashboard['classic']['top_entities'][0]['score'],
        );

        self::assertSame(
            'social_image_missing',
            $dashboard['classic']['issue_hotspots'][1]['finding_id'],
        );

        self::assertSame(
            2,
            $dashboard['charts']['score_bands'][2]['count'],
        );

        self::assertSame(
            2,
            $dashboard['charts']['grade_distribution'][0]['count'],
        );

        self::assertSame(
            92,
            $dashboard['charts']['entity_type_averages'][0]['average_score'],
        );

        self::assertSame(
            2,
            $dashboard['charts']['finding_categories'][0]['count'],
        );

        self::assertSame(
            1,
            $dashboard['charts']['enrichment_statuses'][0]['count'],
        );

        self::assertSame(
            1,
            $dashboard['charts']['enrichment_statuses'][1]['count'],
        );

        self::assertSame(
            3,
            $dashboard['charts']['enrichment_statuses'][3]['count'],
        );
    }

    public function test_it_can_filter_the_dashboard_by_entity_type(): void
    {
        $product =
            Product::factory()->create();

        $category =
            Category::factory()->create();

        $this->analysis(
            entityType: AiSeoEntityType::Product,
            entityId: $product->id,
            score: 84,
            grade: AiSeoAnalysisGrade::Good,
            findings: [],
            analyzedAt: now()->subHour(),
        );

        $this->analysis(
            entityType: AiSeoEntityType::Category,
            entityId: $category->id,
            score: 61,
            grade: AiSeoAnalysisGrade::NeedsWork,
            findings: [
                $this->finding(
                    id: 'missing_resolved_description',
                    category: 'search_metadata',
                    severity: 'critical',
                ),
            ],
            analyzedAt: now(),
        );

        $dashboard =
            app(
                GetSeoDashboardQuery::class,
            )->handle(
                'category',
            );

        self::assertSame(
            'category',
            $dashboard['summary']['entity_scope'],
        );

        self::assertSame(
            1,
            $dashboard['summary']['tracked_entities'],
        );

        self::assertSame(
            61,
            $dashboard['summary']['average_score'],
        );

        self::assertSame(
            0,
            $dashboard['coverage']['products']['total'],
        );

        self::assertSame(
            1,
            $dashboard['coverage']['categories']['analyzed'],
        );
    }

    /**
     * @param  list<array<string, mixed>>  $findings
     */
    private function analysis(
        AiSeoEntityType $entityType,
        int $entityId,
        int $score,
        AiSeoAnalysisGrade $grade,
        array $findings,
        Carbon $analyzedAt,
    ): AiSeoAnalysis {
        return AiSeoAnalysis::query()->create([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'analysis_version' => 'ai-seo-analysis-v1',
            'snapshot_schema_version' => 'ai-seo-entity-snapshot-v1',
            'snapshot_hash' => str_repeat('a', 64),
            'analysis_input_hash' => str_repeat('b', 64),
            'source_snapshot' => [],
            'resolved_seo' => [],
            'score' => $score,
            'grade' => $grade,
            'breakdown' => [],
            'rules' => [],
            'findings' => $findings,
            'analyzed_by_admin_id' => null,
            'analyzed_at' => $analyzedAt,
            'created_at' => $analyzedAt,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function finding(
        string $id,
        string $category,
        string $severity,
    ): array {
        return [
            'id' => $id,
            'category' => $category,
            'severity' => $severity,
            'title' => ucfirst(str_replace('_', ' ', $id)),
            'message' => 'Message',
            'recommendation' => 'Recommendation',
        ];
    }

    private function enrichment(
        AiSeoAnalysis $analysis,
        AiSeoAnalysisEnrichmentStatus $status,
    ): void {
        AiSeoAnalysisEnrichment::query()->create([
            'ai_seo_analysis_id' => $analysis->id,
            'analysis_input_hash' => $analysis->analysis_input_hash,
            'prompt_version' => 'ai-seo-analysis-enrichment-v1',
            'status' => $status,
            'provider' => 'test-provider',
            'model_name' => 'test-model',
            'input_hash' => str_repeat('c', 64),
            'input_context' => [],
            'summary' => null,
            'actions' => null,
            'provider_response_id' => null,
            'error_code' => null,
            'error_message' => null,
            'enriched_by_admin_id' => null,
            'started_at' => now(),
            'completed_at' => now(),
            'created_at' => now(),
        ]);
    }
}
