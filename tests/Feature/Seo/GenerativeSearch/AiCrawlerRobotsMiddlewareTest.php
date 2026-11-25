<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\GenerativeSearch;

use App\Domain\Seo\GenerativeSearch\Services\AiCrawlerPolicyService;
use App\Http\Middleware\AppendAiCrawlerPolicyToRobotsResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

final class AiCrawlerRobotsMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_robots_response_receives_managed_ai_policy_block(): void
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

        $request =
            Request::create(
                '/robots.txt',
                'GET',
            );

        $response =
            app(
                AppendAiCrawlerPolicyToRobotsResponse::class,
            )->handle(
                $request,
                static fn (
                    Request $request,
                ): Response => new Response(
                    "User-agent: *\n"
                    ."Disallow: /admin\n"
                    ."Sitemap: https://example.com/sitemap.xml\n",
                    200,
                    [
                        'Content-Type' => 'text/plain; charset=UTF-8',
                    ],
                ),
            );

        $content =
            $response->getContent();

        self::assertIsString(
            $content,
        );

        self::assertStringContainsString(
            "User-agent: *\nDisallow: /admin",
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
    }

    public function test_non_robots_response_is_unchanged(): void
    {
        $request =
            Request::create(
                '/products',
                'GET',
            );

        $response =
            app(
                AppendAiCrawlerPolicyToRobotsResponse::class,
            )->handle(
                $request,
                static fn (
                    Request $request,
                ): Response => new Response(
                    'storefront',
                    200,
                ),
            );

        self::assertSame(
            'storefront',
            $response
                ->getContent(),
        );
    }

    public function test_non_successful_robots_response_is_not_rewritten(): void
    {
        $request =
            Request::create(
                '/robots.txt',
                'GET',
            );

        $response =
            app(
                AppendAiCrawlerPolicyToRobotsResponse::class,
            )->handle(
                $request,
                static fn (
                    Request $request,
                ): Response => new Response(
                    'missing',
                    404,
                ),
            );

        self::assertSame(
            'missing',
            $response
                ->getContent(),
        );

        self::assertStringNotContainsString(
            'MWT AI CRAWLER POLICY',
            (string) $response
                ->getContent(),
        );
    }
}
