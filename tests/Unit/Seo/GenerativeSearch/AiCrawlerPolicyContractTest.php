<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\GenerativeSearch;

use App\Domain\Seo\GenerativeSearch\Data\AiCrawlerPolicyData;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class AiCrawlerPolicyContractTest extends TestCase
{
    public function test_policy_contract_keeps_search_training_and_google_extended_independent(): void
    {
        $policy =
            new AiCrawlerPolicyData(
                allowOaiSearchBot: true,

                allowGptBot: false,

                allowGoogleExtended: true,

                version: 7,
            );

        self::assertSame(
            [
                'allow_oai_search_bot' => true,

                'allow_gpt_bot' => false,

                'allow_google_extended' => true,

                'version' => 7,
            ],
            $policy->toArray(),
        );
    }

    public function test_admin_route_surface_is_small_and_explicit(): void
    {
        self::assertTrue(
            Route::has(
                'admin.seo.generative-search.crawlers.index',
            ),
        );

        self::assertTrue(
            Route::has(
                'admin.seo.generative-search.crawlers.update',
            ),
        );

        self::assertFalse(
            Route::has(
                'admin.seo.generative-search.crawlers.auto-detect',
            ),
        );

        self::assertFalse(
            Route::has(
                'admin.seo.generative-search.crawlers.allow-all-training',
            ),
        );
    }

    public function test_robots_appender_does_not_emit_user_initiated_or_future_shared_tokens(): void
    {
        $source =
            $this->source(
                'app/Domain/Seo/GenerativeSearch/Services/AiCrawlerRobotsPolicyAppender.php',
            );

        self::assertStringContainsString(
            'OAI-SearchBot',
            $source,
        );

        self::assertStringContainsString(
            'GPTBot',
            $source,
        );

        self::assertStringContainsString(
            'Google-Extended',
            $source,
        );

        self::assertStringNotContainsString(
            'ChatGPT-User',
            $source,
        );

        self::assertStringNotContainsString(
            '__usage_ai-training',
            $source,
        );
    }

    public function test_middleware_is_additive_and_does_not_generate_a_robots_document_on_404(): void
    {
        $source =
            $this->source(
                'app/Http/Middleware/AppendAiCrawlerPolicyToRobotsResponse.php',
            );

        self::assertStringContainsString(
            "->getStatusCode() !==\n                200",
            $source,
        );

        self::assertStringContainsString(
            '$next(',
            $source,
        );

        self::assertStringNotContainsString(
            "new Response(\n            'User-agent:",
            $source,
        );
    }

    public function test_policy_migration_enforces_one_global_scope_key(): void
    {
        $source =
            $this->source(
                'database/migrations/2026_11_23_143000_create_ai_crawler_policies_table.php',
            );

        self::assertStringContainsString(
            "'scope_key'",
            $source,
        );

        self::assertStringContainsString(
            "'ai_crawler_policy_scope_uq'",
            $source,
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
