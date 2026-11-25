<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Ai\Bulk;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkReviewItemResult;
use App\Domain\Seo\Ai\Enums\AiSeoSuggestionField;
use App\Models\AiSeoBulkReviewBatch;
use App\Models\AiSeoBulkReviewBatchItem;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class AiSeoBulkReviewContractTest extends TestCase
{
    public function test_k3_result_contract_supports_partial_review_outcomes(): void
    {
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
    }

    public function test_k3_preserves_the_six_field_metadata_allowlist(): void
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

    public function test_k3_review_routes_remain_separate_from_k4_application_routes(): void
    {
        self::assertTrue(
            Route::has('admin.seo.bulk.plans.review'),
        );

        self::assertTrue(
            Route::has('admin.seo.bulk.plans.review.store'),
        );

        self::assertTrue(
            Route::has('admin.seo.bulk.plans.apply'),
        );

        self::assertTrue(
            Route::has('admin.seo.bulk.plans.apply.store'),
        );

        self::assertFalse(
            Route::has('admin.seo.bulk.apply'),
        );
    }

    public function test_bulk_review_audit_models_contain_no_application_fields(): void
    {
        $batchFillable = (new AiSeoBulkReviewBatch)->getFillable();
        $itemFillable = (new AiSeoBulkReviewBatchItem)->getFillable();

        self::assertContains('decision', $batchFillable);
        self::assertContains('summary', $batchFillable);
        self::assertContains('result', $itemFillable);
        self::assertContains('freshness_state', $itemFillable);

        foreach (
            [
                'applied_at',
                'applied_by_admin_id',
                'before_value',
                'after_value',
                'canonical_url',
                'robots_index',
                'robots_follow',
            ] as $forbidden
        ) {
            self::assertNotContains($forbidden, $batchFillable);
            self::assertNotContains($forbidden, $itemFillable);
        }
    }

    public function test_k3_review_service_uses_existing_j2_human_review_service(): void
    {
        $source = file_get_contents(
            base_path(
                'app/Domain/Seo/Ai/Bulk/Services/AiSeoBulkSuggestionReviewService.php',
            ),
        );

        self::assertIsString($source);

        self::assertStringContainsString(
            'AiSeoSuggestionReviewService',
            $source,
        );

        foreach (
            [
                'EntitySeoRepository',
                'meta_title =',
                'meta_description =',
                'canonical_url =',
                'robots_index =',
            ] as $forbidden
        ) {
            self::assertStringNotContainsString(
                $forbidden,
                $source,
            );
        }
    }
}
