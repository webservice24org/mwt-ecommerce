<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Ai\Workspace;

use App\Domain\Seo\Ai\Enums\AiSeoSuggestionField;
use App\Domain\Seo\Ai\Workspace\Enums\AiSeoSuggestionReviewDecision;
use App\Models\AiSeoSuggestionReview;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class AiSeoWorkspaceReleaseContractTest extends TestCase
{
    public function test_metadata_suggestion_allowlist_stays_limited_to_six_text_fields(): void
    {
        $fields =
            array_map(
                static fn (
                    AiSeoSuggestionField $field,
                ): string => $field->value,
                AiSeoSuggestionField::cases(),
            );

        self::assertSame(
            [
                'meta_title',
                'meta_description',
                'og_title',
                'og_description',
                'twitter_title',
                'twitter_description',
            ],
            $fields,
        );

        foreach (
            [
                'canonical_url',
                'canonical',
                'robots_index',
                'robots_follow',
                'og_image',
                'twitter_image',
                'structured_data',
                'price',
                'inventory',
                'status',
            ] as $protectedField
        ) {
            self::assertNotContains(
                $protectedField,
                $fields,
            );
        }
    }

    public function test_review_decisions_remain_human_review_states_not_apply_states(): void
    {
        self::assertSame(
            [
                'approved',
                'rejected',
            ],
            array_map(
                static fn (
                    AiSeoSuggestionReviewDecision $decision,
                ): string => $decision->value,
                AiSeoSuggestionReviewDecision::cases(),
            ),
        );

        $fillable =
            (new AiSeoSuggestionReview)
                ->getFillable();

        self::assertContains(
            'decision',
            $fillable,
        );

        self::assertContains(
            'source_snapshot_hash',
            $fillable,
        );

        self::assertContains(
            'current_snapshot_hash',
            $fillable,
        );

        self::assertNotContains(
            'applied_at',
            $fillable,
        );

        self::assertNotContains(
            'applied_by_admin_id',
            $fillable,
        );

        self::assertNotContains(
            'before_value',
            $fillable,
        );

        self::assertNotContains(
            'after_value',
            $fillable,
        );
    }

    public function test_workspace_route_contract_is_complete_without_an_apply_route(): void
    {
        self::assertTrue(
            Route::has(
                'admin.seo.overview',
            ),
        );

        self::assertTrue(
            Route::has(
                'admin.seo.suggestions.index',
            ),
        );

        self::assertTrue(
            Route::has(
                'admin.seo.suggestions.approve',
            ),
        );

        self::assertTrue(
            Route::has(
                'admin.seo.suggestions.reject',
            ),
        );

        self::assertTrue(
            Route::has(
                'admin.seo.entities.show',
            ),
        );

        self::assertTrue(
            Route::has(
                'admin.seo.entities.analyze',
            ),
        );

        self::assertTrue(
            Route::has(
                'admin.seo.entities.suggestions.generate',
            ),
        );

        self::assertTrue(
            Route::has(
                'admin.seo.entities.analyses.enrich',
            ),
        );

        self::assertTrue(
            Route::has(
                'admin.seo.technical-health',
            ),
        );

        self::assertFalse(
            Route::has(
                'admin.seo.suggestions.apply',
            ),
        );
    }

    public function test_workspace_frontend_sources_link_the_four_surfaces_together(): void
    {
        $overview =
            $this->source(
                'resources/js/Pages/Admin/Seo/Overview.tsx',
            );

        $suggestions =
            $this->source(
                'resources/js/Pages/Admin/Seo/Suggestions/Index.tsx',
            );

        $entity =
            $this->source(
                'resources/js/Pages/Admin/Seo/Entities/Show.tsx',
            );

        $technical =
            $this->source(
                'resources/js/Pages/Admin/Seo/TechnicalHealth.tsx',
            );

        self::assertStringContainsString(
            'admin.seo.entities.show',
            $overview,
        );

        self::assertStringContainsString(
            'admin.seo.entities.show',
            $suggestions,
        );

        self::assertStringContainsString(
            'admin.seo.overview',
            $entity,
        );

        self::assertStringContainsString(
            'admin.seo.suggestions.index',
            $entity,
        );

        self::assertStringContainsString(
            'admin.seo.overview',
            $technical,
        );

        self::assertStringContainsString(
            'admin.seo.suggestions.index',
            $technical,
        );
    }

    public function test_workspace_controllers_do_not_reference_live_seo_write_repository(): void
    {
        $controllers = [
            'app/Http/Controllers/Admin/Seo/SeoOverviewController.php',
            'app/Http/Controllers/Admin/Seo/AiSeoSuggestionsInboxController.php',
            'app/Http/Controllers/Admin/Seo/AiSeoSuggestionReviewController.php',
            'app/Http/Controllers/Admin/Seo/AiSeoEntityWorkspaceController.php',
            'app/Http/Controllers/Admin/Seo/AiSeoEntityWorkspaceActionController.php',
            'app/Http/Controllers/Admin/Seo/SeoTechnicalHealthController.php',
        ];

        foreach (
            $controllers as $relativePath
        ) {
            $source =
                $this->source(
                    $relativePath,
                );

            self::assertStringNotContainsString(
                'EntitySeoRepository',
                $source,
                $relativePath,
            );

            self::assertStringNotContainsString(
                'seoMeta()->update',
                $source,
                $relativePath,
            );

            self::assertStringNotContainsString(
                'meta_title =',
                $source,
                $relativePath,
            );

            self::assertStringNotContainsString(
                'canonical_url =',
                $source,
                $relativePath,
            );
        }
    }

    private function source(
        string $relativePath,
    ): string {
        $path =
            base_path(
                $relativePath,
            );

        self::assertFileExists(
            $path,
        );

        $source =
            file_get_contents(
                $path,
            );

        self::assertIsString(
            $source,
        );

        return $source;
    }
}
