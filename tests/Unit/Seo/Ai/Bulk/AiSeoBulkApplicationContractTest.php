<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Ai\Bulk;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkApplyItemResult;
use App\Domain\Seo\Ai\Enums\AiSeoSuggestionField;
use App\Models\AiSeoBulkApplyBatch;
use App\Models\AiSeoBulkApplyBatchItem;
use App\Models\AiSeoSuggestionApplication;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class AiSeoBulkApplicationContractTest extends TestCase
{
    public function test_k4_application_result_contract_is_partial_and_explicit(): void
    {
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

    public function test_k4_live_write_allowlist_remains_exactly_six_text_metadata_fields(): void
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
                array_map(
                    static fn (
                        AiSeoSuggestionField $field,
                    ): string => $field->value,
                    AiSeoSuggestionField::cases(),
                ),
            );
        }
    }

    public function test_k4_routes_expose_guarded_application_without_force_overwrite_route(): void
    {
        self::assertTrue(
            Route::has(
                'admin.seo.bulk.plans.apply',
            ),
        );

        self::assertTrue(
            Route::has(
                'admin.seo.bulk.plans.apply.store',
            ),
        );

        self::assertFalse(
            Route::has(
                'admin.seo.bulk.plans.apply.force',
            ),
        );

        self::assertFalse(
            Route::has(
                'admin.seo.bulk.force-apply',
            ),
        );

        self::assertFalse(
            Route::has(
                'admin.seo.bulk.rollback',
            ),
        );
    }

    public function test_application_audit_models_capture_before_after_and_actor_state(): void
    {
        $batch =
            (new AiSeoBulkApplyBatch)
                ->getFillable();

        $item =
            (new AiSeoBulkApplyBatchItem)
                ->getFillable();

        $ledger =
            (new AiSeoSuggestionApplication)
                ->getFillable();

        self::assertContains(
            'applied_by_admin_id',
            $batch,
        );

        self::assertContains(
            'summary',
            $batch,
        );

        self::assertContains(
            'baseline_value',
            $item,
        );

        self::assertContains(
            'before_value',
            $item,
        );

        self::assertContains(
            'proposed_value',
            $item,
        );

        self::assertContains(
            'after_value',
            $item,
        );

        self::assertContains(
            'ai_seo_suggestion_review_id',
            $ledger,
        );

        self::assertContains(
            'source_snapshot_hash',
            $ledger,
        );

        self::assertContains(
            'current_snapshot_hash',
            $ledger,
        );

        self::assertContains(
            'applied_value',
            $ledger,
        );
    }

    public function test_application_service_has_no_force_or_protected_field_write_contract(): void
    {
        $source =
            file_get_contents(
                base_path(
                    'app/Domain/Seo/Ai/Bulk/Services/AiSeoBulkApplicationService.php',
                ),
            );

        self::assertIsString(
            $source,
        );

        self::assertStringContainsString(
            'field_conflict',
            $source,
        );

        self::assertStringContainsString(
            'snapshot_stale',
            $source,
        );

        self::assertStringNotContainsString(
            'forceApply',
            $source,
        );

        self::assertStringNotContainsString(
            'overwriteConflict',
            $source,
        );
    }
}
