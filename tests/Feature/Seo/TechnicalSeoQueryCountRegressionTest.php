<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Queries\Storefront\StorefrontProductDetailQuery;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TechnicalSeoQueryCountRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_authoritative_product_detail_lookup_stays_within_bounded_query_budget(): void
    {
        $product = Product::factory()->create([
            'status' => ProductStatus::Published->value,
            'published_at' => now()->subMinute(),
        ]);

        $query = app(StorefrontProductDetailQuery::class);

        $queryCount = 0;

        DB::listen(
            static function () use (&$queryCount): void {
                $queryCount++;
            },
        );

        $resolved = $query->findBySlugOrFail(
            $product->slug,
        );

        self::assertSame(
            $product->id,
            $resolved->id,
        );

        /*
         * StorefrontProductDetailQuery intentionally eager-loads
         * SEO metadata plus bounded product-detail relations.
         * Keep generous headroom for framework/driver differences,
         * while still catching N+1-style query explosions.
         */
        self::assertLessThanOrEqual(
            12,
            $queryCount,
            sprintf(
                'Authoritative product detail lookup executed %d queries; expected at most 12.',
                $queryCount,
            ),
        );
    }

    public function test_cached_product_detail_second_read_does_not_hit_database(): void
    {
        $product = Product::factory()->create([
            'status' => ProductStatus::Published->value,
            'published_at' => now()->subMinute(),
        ]);

        $query = app(StorefrontProductDetailQuery::class);

        self::assertNotNull(
            $query->findBySlug(
                $product->slug,
            ),
        );

        $queryCount = 0;

        DB::listen(
            static function () use (&$queryCount): void {
                $queryCount++;
            },
        );

        self::assertNotNull(
            $query->findBySlug(
                $product->slug,
            ),
        );

        self::assertSame(
            0,
            $queryCount,
            'A warmed product-detail cache read must not issue database queries.',
        );
    }
}
