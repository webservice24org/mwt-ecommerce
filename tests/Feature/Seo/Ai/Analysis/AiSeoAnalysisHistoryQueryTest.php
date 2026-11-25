<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Ai\Analysis;

use App\Domain\Seo\Ai\Analysis\Queries\AiSeoAnalysisHistoryQuery;
use App\Domain\Seo\Ai\Analysis\Services\AiSeoEntityAnalysisService;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AiSeoAnalysisHistoryQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_latest_and_bounded_history_are_entity_scoped(): void
    {
        $firstProduct =
            Product::factory()
                ->create();

        $secondProduct =
            Product::factory()
                ->create();

        $service =
            app(
                AiSeoEntityAnalysisService::class,
            );

        $first =
            $service->analyze(
                AiSeoEntityType::Product,
                $firstProduct->id,
            );

        $second =
            $service->analyze(
                AiSeoEntityType::Product,
                $firstProduct->id,
            );

        $service->analyze(
            AiSeoEntityType::Product,
            $secondProduct->id,
        );

        $query =
            app(
                AiSeoAnalysisHistoryQuery::class,
            );

        self::assertSame(
            $second->id,
            $query
                ->latest(
                    AiSeoEntityType::Product,
                    $firstProduct->id,
                )
                ?->id,
        );

        $history =
            $query
                ->history(
                    AiSeoEntityType::Product,
                    $firstProduct->id,
                    1,
                );

        self::assertCount(
            1,
            $history,
        );

        self::assertSame(
            $second->id,
            $history
                ->firstOrFail()
                ->id,
        );

        self::assertNotSame(
            $first->id,
            $history
                ->firstOrFail()
                ->id,
        );
    }
}
