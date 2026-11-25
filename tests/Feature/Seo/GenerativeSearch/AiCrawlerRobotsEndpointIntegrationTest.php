<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\GenerativeSearch;

use App\Domain\Seo\GenerativeSearch\Services\AiCrawlerPolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AiCrawlerRobotsEndpointIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_robots_endpoint_receives_l2_managed_policy_without_being_replaced(): void
    {
        $service =
            app(
                AiCrawlerPolicyService::class,
            );

        $initial =
            $service->get();

        $service->save(
            allowOaiSearchBot: false,

            allowGptBot: true,

            allowGoogleExtended: false,

            expectedVersion: $initial->version,
        );

        $response =
            $this->get(
                '/robots.txt',
            );

        $response
            ->assertOk()
            ->assertHeader(
                'content-type',
                'text/plain; charset=UTF-8',
            );

        $content =
            $response
                ->getContent();

        self::assertIsString(
            $content,
        );

        /*
         * Existing 10.4D output is still present.
         */
        self::assertStringContainsString(
            'User-agent:',
            $content,
        );

        /*
         * L.2 contributes only its managed block.
         */
        self::assertStringContainsString(
            '# BEGIN MWT AI CRAWLER POLICY',
            $content,
        );

        self::assertStringContainsString(
            "User-agent: OAI-SearchBot\nDisallow: /",
            $content,
        );

        self::assertStringNotContainsString(
            "User-agent: GPTBot\nDisallow: /",
            $content,
        );

        self::assertStringContainsString(
            "User-agent: Google-Extended\nDisallow: /",
            $content,
        );

        self::assertSame(
            1,
            substr_count(
                $content,
                '# BEGIN MWT AI CRAWLER POLICY',
            ),
        );
    }
}
