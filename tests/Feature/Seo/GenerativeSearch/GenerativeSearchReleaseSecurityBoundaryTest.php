<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\GenerativeSearch;

use App\Http\Requests\Admin\Seo\UpdateAiCrawlerPolicyRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class GenerativeSearchReleaseSecurityBoundaryTest extends TestCase
{
    public function test_generativesearch_admin_surfaces_remain_inside_admin_auth_boundary(): void
    {
        foreach (
            [
                'admin.seo.generative-search.crawlers.index',
                'admin.seo.generative-search.crawlers.update',
                'admin.seo.generative-search.readiness',
            ] as $routeName
        ) {
            $route =
                Route::getRoutes()
                    ->getByName(
                        $routeName,
                    );

            self::assertNotNull(
                $route,
                $routeName,
            );

            $middleware =
                $route
                    ->gatherMiddleware();

            self::assertContains(
                'auth:admin',
                $middleware,
                $routeName,
            );

            self::assertContains(
                'admin.active',
                $middleware,
                $routeName,
            );
        }
    }

    public function test_public_robots_endpoint_does_not_gain_admin_authentication(): void
    {
        $route =
            Route::getRoutes()
                ->getByName(
                    'robots.txt',
                );

        self::assertNotNull(
            $route,
        );

        $middleware =
            $route
                ->gatherMiddleware();

        self::assertNotContains(
            'auth:admin',
            $middleware,
        );

        self::assertNotContains(
            'admin.active',
            $middleware,
        );
    }

    public function test_readiness_workspace_has_no_mutating_route_surface(): void
    {
        $route =
            Route::getRoutes()
                ->getByName(
                    'admin.seo.generative-search.readiness',
                );

        self::assertNotNull(
            $route,
        );

        self::assertSame(
            [
                'GET',
                'HEAD',
            ],
            $route
                ->methods(),
        );

        foreach (
            [
                'admin.seo.generative-search.readiness.store',
                'admin.seo.generative-search.readiness.update',
                'admin.seo.generative-search.readiness.apply',
                'admin.seo.generative-search.readiness.generate',
                'admin.seo.generative-search.readiness.delete',
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

    public function test_crawler_policy_update_contract_rejects_untyped_or_stale_shape_before_service_execution(): void
    {
        $request =
            new UpdateAiCrawlerPolicyRequest;

        $validator =
            Validator::make(
                [
                    'allow_oai_search_bot' => 'not-a-boolean',

                    'allow_gpt_bot' => 'not-a-boolean',

                    'allow_google_extended' => 'not-a-boolean',

                    'version' => 0,
                ],
                $request->rules(),
            );

        self::assertTrue(
            $validator->fails(),
        );

        self::assertArrayHasKey(
            'allow_oai_search_bot',
            $validator
                ->errors()
                ->toArray(),
        );

        self::assertArrayHasKey(
            'allow_gpt_bot',
            $validator
                ->errors()
                ->toArray(),
        );

        self::assertArrayHasKey(
            'allow_google_extended',
            $validator
                ->errors()
                ->toArray(),
        );

        self::assertArrayHasKey(
            'version',
            $validator
                ->errors()
                ->toArray(),
        );
    }

    public function test_crawler_policy_route_methods_are_minimal_and_explicit(): void
    {
        $index =
            Route::getRoutes()
                ->getByName(
                    'admin.seo.generative-search.crawlers.index',
                );

        $update =
            Route::getRoutes()
                ->getByName(
                    'admin.seo.generative-search.crawlers.update',
                );

        self::assertNotNull(
            $index,
        );

        self::assertNotNull(
            $update,
        );

        self::assertSame(
            [
                'GET',
                'HEAD',
            ],
            $index
                ->methods(),
        );

        self::assertSame(
            [
                'PUT',
            ],
            $update
                ->methods(),
        );

        foreach (
            [
                'admin.seo.generative-search.crawlers.auto-approve',
                'admin.seo.generative-search.crawlers.auto-optimize',
                'admin.seo.generative-search.crawlers.allow-all-training',
                'admin.seo.generative-search.crawlers.detect',
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
}
