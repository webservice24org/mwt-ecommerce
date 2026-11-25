<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Ai\Bulk;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkApplyItemResult;
use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkItemExecutionStatus;
use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkReviewItemResult;
use App\Domain\Seo\Ai\Enums\AiSeoSuggestionField;
use App\Models\AiSeoSuggestionApplication;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class AiSeoBulkOptimizationReleaseContractTest extends TestCase
{
    public function test_release_keeps_exact_six_field_ai_metadata_allowlist(): void
    {
        self::assertSame(
            [
                'meta_title',
                'meta_description',
                'og_title',
                'og_description',
                'twitter_title',
                'twitter_description',
            ],
            array_map(
                static fn (
                    AiSeoSuggestionField $field,
                ): string => $field->value,
                AiSeoSuggestionField::cases(),
            ),
        );
    }

    public function test_release_state_contracts_cover_plan_execution_review_and_application(): void
    {
        self::assertSame(
            [
                'planned',
                'queued',
                'processing',
                'completed',
                'skipped',
                'drifted',
                'failed',
            ],
            array_map(
                static fn (
                    AiSeoBulkItemExecutionStatus $status,
                ): string => $status->value,
                AiSeoBulkItemExecutionStatus::cases(),
            ),
        );

        self::assertSame(
            [
                'approved',
                'rejected',
                'already_decided',
                'blocked_stale',
                'failed',
            ],
            array_map(
                static fn (
                    AiSeoBulkReviewItemResult $result,
                ): string => $result->value,
                AiSeoBulkReviewItemResult::cases(),
            ),
        );

        self::assertSame(
            [
                'applied',
                'already_applied',
                'conflict',
                'stale',
                'not_approved',
                'failed',
            ],
            array_map(
                static fn (
                    AiSeoBulkApplyItemResult $result,
                ): string => $result->value,
                AiSeoBulkApplyItemResult::cases(),
            ),
        );
    }

    public function test_release_route_surface_is_explicit_and_has_no_force_or_auto_approval_path(): void
    {
        foreach (
            [
                'admin.seo.bulk.index',
                'admin.seo.bulk.plans.store',
                'admin.seo.bulk.plans.show',
                'admin.seo.bulk.plans.execute',
                'admin.seo.bulk.plans.retry-failed',
                'admin.seo.bulk.plans.review',
                'admin.seo.bulk.plans.review.store',
                'admin.seo.bulk.plans.apply',
                'admin.seo.bulk.plans.apply.store',
            ] as $routeName
        ) {
            self::assertTrue(
                Route::has(
                    $routeName,
                ),
                $routeName,
            );
        }

        foreach (
            [
                'admin.seo.bulk.plans.apply.force',
                'admin.seo.bulk.force-apply',
                'admin.seo.bulk.plans.auto-approve',
                'admin.seo.bulk.auto-approve',
                'admin.seo.bulk.rollback',
            ] as $routeName
        ) {
            self::assertFalse(
                Route::has(
                    $routeName,
                ),
                $routeName,
            );
        }
    }

    public function test_application_ledger_has_single_suggestion_identity_contract(): void
    {
        $fillable =
            (new AiSeoSuggestionApplication)
                ->getFillable();

        foreach (
            [
                'ai_seo_suggestion_id',
                'ai_seo_suggestion_review_id',
                'ai_seo_request_id',
                'ai_seo_bulk_job_id',
                'ai_seo_bulk_job_item_id',
                'entity_type',
                'entity_id',
                'field',
                'baseline_value',
                'before_value',
                'applied_value',
                'source_snapshot_hash',
                'current_snapshot_hash',
                'applied_by_admin_id',
                'applied_at',
            ] as $field
        ) {
            self::assertContains(
                $field,
                $fillable,
            );
        }

        $migration =
            file_get_contents(
                base_path(
                    'database/migrations/2026_11_23_130200_create_ai_seo_suggestion_applications_table.php',
                ),
            );

        self::assertIsString(
            $migration,
        );

        self::assertStringContainsString(
            "'ai_seo_sa_suggestion_unique'",
            $migration,
        );

        self::assertStringContainsString(
            "'ai_seo_suggestion_id'",
            $migration,
        );
    }

    public function test_release_ui_keeps_review_and_apply_as_separate_explicit_steps(): void
    {
        $show =
            $this->source(
                'resources/js/Pages/Admin/Seo/Bulk/Show.tsx',
            );

        $review =
            $this->source(
                'resources/js/Pages/Admin/Seo/Bulk/Review.tsx',
            );

        $apply =
            $this->source(
                'resources/js/Pages/Admin/Seo/Bulk/Apply.tsx',
            );

        self::assertStringContainsString(
            'admin.seo.bulk.plans.review',
            $show,
        );

        self::assertStringContainsString(
            'admin.seo.bulk.plans.apply',
            $show,
        );

        self::assertStringContainsString(
            'admin.seo.bulk.plans.apply',
            $review,
        );

        self::assertStringContainsString(
            'ConfirmDialog',
            $apply,
        );

        self::assertStringContainsString(
            'admin.seo.bulk.plans.apply.store',
            $apply,
        );

        self::assertStringNotContainsString(
            'force-apply',
            $apply,
        );
    }

    public function test_application_service_requires_approval_and_conflict_stale_guards(): void
    {
        $application =
            $this->source(
                'app/Domain/Seo/Ai/Bulk/Services/AiSeoBulkApplicationService.php',
            );

        self::assertStringContainsString(
            'AiSeoSuggestionReviewDecision::Approved',
            $application,
        );

        self::assertStringContainsString(
            "'field_conflict'",
            $application,
        );

        self::assertStringContainsString(
            "'snapshot_stale'",
            $application,
        );

        self::assertStringContainsString(
            'AiSeoSuggestionApplication::query()',
            $application,
        );

        self::assertStringNotContainsString(
            'forceApply',
            $application,
        );

        self::assertStringNotContainsString(
            'overwriteConflict',
            $application,
        );
    }

    private function source(
        string $path,
    ): string {
        $source =
            file_get_contents(
                base_path(
                    $path,
                ),
            );

        self::assertIsString(
            $source,
            $path,
        );

        return $source;
    }
}
