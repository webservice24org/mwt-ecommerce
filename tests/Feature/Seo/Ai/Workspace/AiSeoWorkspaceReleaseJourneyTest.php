<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Ai\Workspace;

use App\Domain\Seo\Ai\Analysis\Services\AiSeoEntityAnalysisService;
use App\Domain\Seo\Ai\Enums\AiSeoAction;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Enums\AiSeoRequestStatus;
use App\Domain\Seo\Ai\Enums\AiSeoSuggestionField;
use App\Domain\Seo\Ai\Services\AiSeoEntitySnapshotBuilder;
use App\Domain\Seo\Ai\Services\AiSeoSnapshotHasher;
use App\Domain\Seo\Ai\Workspace\Queries\GetAiSeoEntityWorkspaceQuery;
use App\Domain\Seo\Ai\Workspace\Queries\GetSeoDashboardQuery;
use App\Domain\Seo\Ai\Workspace\Queries\GetSeoTechnicalHealthQuery;
use App\Domain\Seo\Ai\Workspace\Services\AiSeoSuggestionReviewService;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Models\AiSeoRequest;
use App\Models\AiSeoSuggestion;
use App\Models\Product;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AiSeoWorkspaceReleaseJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->saveHealthySeoSettings();
    }

    public function test_complete_workspace_journey_remains_append_only_stale_safe_and_non_destructive(): void
    {
        $product =
            Product::factory()
                ->create([
                    'name' => 'Workspace Release Product',

                    'slug' => 'workspace-release-product',

                    'meta_title' => 'Original release title',

                    'meta_description' => 'Original release description.',

                    'description' => str_repeat(
                        'Stable factual release content. ',
                        10,
                    ),
                ]);

        $analysisService =
            app(
                AiSeoEntityAnalysisService::class,
            );

        $firstAnalysis =
            $analysisService->analyze(
                AiSeoEntityType::Product,
                $product->id,
                101,
            );

        $snapshot =
            app(
                AiSeoEntitySnapshotBuilder::class,
            )
                ->build(
                    AiSeoEntityType::Product,
                    $product->id,
                )
                ->toArray();

        $request =
            AiSeoRequest::query()
                ->create([
                    'entity_type' => AiSeoEntityType::Product,

                    'entity_id' => $product->id,

                    'action' => AiSeoAction::GenerateMetadataSuggestions,

                    'status' => AiSeoRequestStatus::Completed,

                    'requested_by_admin_id' => 102,

                    'input_snapshot' => [
                        'entity_snapshot' => $snapshot,
                    ],

                    'input_hash' => app(
                        AiSeoSnapshotHasher::class,
                    )->hash([
                        'entity_snapshot' => $snapshot,
                    ]),

                    'provider' => 'release-provider',

                    'model_name' => 'release-model',

                    'prompt_version' => 'ai-seo-metadata-v1',

                    'started_at' => now()
                        ->subSecond(),

                    'completed_at' => now(),
                ]);

        $suggestion =
            AiSeoSuggestion::query()
                ->create([
                    'ai_seo_request_id' => $request->id,

                    'field' => AiSeoSuggestionField::MetaTitle,

                    'value' => 'Improved release SEO title',

                    'rationale' => 'A clear factual metadata draft.',

                    'position' => 0,
                ]);

        $reviewService =
            app(
                AiSeoSuggestionReviewService::class,
            );

        $approved =
            $reviewService->approve(
                $suggestion,
                103,
                'Approved for later application.',
            );

        self::assertSame(
            'approved',
            $approved
                ->decision
                ->value,
        );

        self::assertSame(
            'fresh',
            $approved
                ->freshness_state,
        );

        $product->refresh();

        self::assertSame(
            'Original release title',
            $product
                ->meta_title,
        );

        self::assertSame(
            'Original release description.',
            $product
                ->meta_description,
        );

        $workspace =
            app(
                GetAiSeoEntityWorkspaceQuery::class,
            )->handle(
                AiSeoEntityType::Product,
                $product->id,
            );

        self::assertSame(
            $firstAnalysis->id,
            $workspace[
                'latest_analysis'
            ]['id'],
        );

        self::assertSame(
            'approved',
            $workspace[
                'suggestions'
            ][0]['review_status'],
        );

        self::assertFalse(
            $workspace[
                'suggestions'
            ][0]['freshness']['is_stale'],
        );

        $overview =
            app(
                GetSeoDashboardQuery::class,
            )->handle();

        self::assertSame(
            1,
            $overview[
                'summary'
            ]['tracked_entities'],
        );

        self::assertSame(
            $firstAnalysis->score,
            $overview[
                'classic'
            ]['recent_entities'][0]['score'],
        );

        $health =
            app(
                GetSeoTechnicalHealthQuery::class,
            )->handle();

        self::assertSame(
            100,
            $health[
                'summary'
            ]['analysis_coverage_percent'],
        );

        self::assertSame(
            100,
            $health[
                'summary'
            ]['analysis_freshness_percent'],
        );

        $reviewCountBeforeStaleApproval =
            $suggestion
                ->reviews()
                ->count();

        $product->update([
            'description' => 'Changed after analysis and suggestion generation.',
        ]);

        $staleWorkspace =
            app(
                GetAiSeoEntityWorkspaceQuery::class,
            )->handle(
                AiSeoEntityType::Product,
                $product->id,
            );

        self::assertTrue(
            $staleWorkspace[
                'summary'
            ]['analysis_stale'],
        );

        self::assertTrue(
            $staleWorkspace[
                'suggestions'
            ][0]['freshness']['is_stale'],
        );

        self::assertFalse(
            $staleWorkspace[
                'suggestions'
            ][0]['can_approve'],
        );

        try {
            $reviewService->approve(
                $suggestion,
                104,
                'This must not append.',
            );

            self::fail(
                'Stale suggestion was approved.',
            );
        } catch (
            DomainException $exception
        ) {
            self::assertStringContainsString(
                'stale',
                strtolower(
                    $exception
                        ->getMessage(),
                ),
            );
        }

        self::assertSame(
            $reviewCountBeforeStaleApproval,
            $suggestion
                ->reviews()
                ->count(),
        );

        $secondAnalysis =
            $analysisService->analyze(
                AiSeoEntityType::Product,
                $product->id,
                105,
            );

        self::assertNotSame(
            $firstAnalysis->id,
            $secondAnalysis->id,
        );

        self::assertNotSame(
            $firstAnalysis
                ->snapshot_hash,
            $secondAnalysis
                ->snapshot_hash,
        );

        self::assertDatabaseCount(
            'ai_seo_analyses',
            2,
        );

        self::assertDatabaseCount(
            'ai_seo_suggestion_reviews',
            1,
        );

        $product->refresh();

        self::assertSame(
            'Original release title',
            $product
                ->meta_title,
        );

        self::assertSame(
            'Original release description.',
            $product
                ->meta_description,
        );
    }

    private function saveHealthySeoSettings(): void
    {
        app(
            SeoSettingsService::class,
        )->save(
            new SeoSettingsData(
                siteName: 'MWT Store',

                titleSeparator: '|',

                defaultTitleTemplate: '{page_title} {separator} {site_name}',

                defaultMetaDescription: 'Default factual storefront description.',

                canonicalHost: 'https://example.com',

                defaultRobotsIndex: true,

                defaultRobotsFollow: true,

                defaultOgImage: 'https://example.com/social.jpg',

                twitterCard: 'summary_large_image',

                organizationName: 'MWT Store',

                organizationLogo: 'https://example.com/logo.png',

                organizationSocialProfiles: [
                    'https://example.com/social-profile',
                ],

                googleSiteVerification: 'google-release-token',

                bingSiteVerification: null,

                enableStructuredData: true,

                enableOpenGraph: true,

                enableTwitterCards: true,

                enableSitemap: true,
            ),
        );
    }
}
