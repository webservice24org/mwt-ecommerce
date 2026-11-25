<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Domain\Seo\Redirects\Services\SeoSlugRedirectHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SeoSlugRedirectHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_rewrites_old_slugs_to_latest_slug_without_redirect_chains(): void
    {
        $history = app(SeoSlugRedirectHistory::class);

        $history->record(
            entityType: SeoSlugRedirectHistory::PRODUCT,
            entityId: 42,
            oldSlug: 'old-product',
            newSlug: 'new-product',
        );

        self::assertSame(
            '/products/new-product',
            $history->redirectPath(
                SeoSlugRedirectHistory::PRODUCT,
                'old-product',
            ),
        );

        $history->record(
            entityType: SeoSlugRedirectHistory::PRODUCT,
            entityId: 42,
            oldSlug: 'new-product',
            newSlug: 'final-product',
        );

        self::assertSame(
            '/products/final-product',
            $history->redirectPath(
                SeoSlugRedirectHistory::PRODUCT,
                'old-product',
            ),
        );

        self::assertSame(
            '/products/final-product',
            $history->redirectPath(
                SeoSlugRedirectHistory::PRODUCT,
                'new-product',
            ),
        );
    }

    public function test_redirect_response_is_permanent_and_internal(): void
    {
        $history = app(SeoSlugRedirectHistory::class);

        $history->record(
            entityType: SeoSlugRedirectHistory::CATEGORY,
            entityId: 7,
            oldSlug: 'old-category',
            newSlug: 'new-category',
        );

        $response = $history->redirectResponse(
            SeoSlugRedirectHistory::CATEGORY,
            'old-category',
        );

        self::assertNotNull($response);
        self::assertSame(301, $response->getStatusCode());
        self::assertSame(
            url('/category/new-category'),
            $response->headers->get('Location'),
        );
    }
}
