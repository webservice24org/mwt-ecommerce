<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Ai\Bulk;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkItemState;
use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkPlannedAction;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class AiSeoBulkPlanningContractTest extends TestCase
{
    public function test_k1_contract_is_planning_only(): void
    {
        self::assertSame(
            [
                'ready',
                'stale',
                'fresh',
            ],
            array_map(
                static fn (
                    AiSeoBulkItemState $state,
                ): string => $state->value,
                AiSeoBulkItemState::cases(),
            ),
        );

        self::assertSame(
            [
                'generate',
                'skip',
            ],
            array_map(
                static fn (
                    AiSeoBulkPlannedAction $action,
                ): string => $action->value,
                AiSeoBulkPlannedAction::cases(),
            ),
        );

        self::assertTrue(
            Route::has(
                'admin.seo.bulk.index',
            ),
        );

        self::assertTrue(
            Route::has(
                'admin.seo.bulk.plans.store',
            ),
        );

        self::assertFalse(
            Route::has(
                'admin.seo.bulk.execute',
            ),
        );

        self::assertFalse(
            Route::has(
                'admin.seo.bulk.apply',
            ),
        );
    }

    public function test_k1_sources_do_not_call_generation_service_or_provider(): void
    {
        $sources = [
            'app/Domain/Seo/Ai/Bulk/Queries/GetAiSeoBulkCandidatesQuery.php',
            'app/Domain/Seo/Ai/Bulk/Services/AiSeoBulkPlanService.php',
            'app/Domain/Seo/Ai/Bulk/Queries/GetAiSeoBulkWorkspaceQuery.php',
            'app/Http/Controllers/Admin/Seo/AiSeoBulkWorkspaceController.php',
            'app/Http/Controllers/Admin/Seo/AiSeoBulkPlanController.php',
        ];

        foreach ($sources as $source) {
            $contents =
                file_get_contents(
                    base_path(
                        $source,
                    ),
                );

            self::assertIsString(
                $contents,
            );

            self::assertStringNotContainsString(
                'AiSeoSuggestionGenerationService',
                $contents,
                $source,
            );

            self::assertStringNotContainsString(
                'AiSeoProvider',
                $contents,
                $source,
            );

            self::assertStringNotContainsString(
                '->generate(',
                $contents,
                $source,
            );
        }
    }
}
