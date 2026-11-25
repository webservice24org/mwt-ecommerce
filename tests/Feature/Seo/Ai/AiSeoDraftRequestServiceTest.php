<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Ai;

use App\Domain\Seo\Ai\Data\AiSeoPromptContextData;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Enums\AiSeoRequestStatus;
use App\Domain\Seo\Ai\Services\AiSeoDraftRequestService;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AiSeoDraftRequestServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_the_exact_versioned_provider_context_as_the_h1_input_snapshot(): void
    {
        $product =
            Product::factory()
                ->create([
                    'name' => 'Snapshot Cable',

                    'slug' => 'snapshot-cable',

                    'short_description' => '<p>Reliable charging cable.</p>',
                ]);

        $service =
            app(
                AiSeoDraftRequestService::class,
            );

        $request =
            $service->create(
                entityType: AiSeoEntityType::Product,

                entityId: $product->id,

                requestedByAdminId: 99,
            );

        self::assertSame(
            AiSeoRequestStatus::Pending,
            $request->status,
        );

        self::assertSame(
            AiSeoPromptContextData::PROMPT_VERSION,
            $request->input_snapshot[
                'prompt_version'
            ],
        );

        self::assertSame(
            'Snapshot Cable',
            $request
                ->input_snapshot[
                    'entity_snapshot'
                ]['entity']['name'],
        );

        self::assertSame(
            'Reliable charging cable.',
            $request
                ->input_snapshot[
                    'entity_snapshot'
                ]['content']['summary'],
        );

        self::assertSame(
            99,
            $request->requested_by_admin_id,
        );

        self::assertSame(
            64,
            strlen(
                $request->input_hash,
            ),
        );
    }
}
