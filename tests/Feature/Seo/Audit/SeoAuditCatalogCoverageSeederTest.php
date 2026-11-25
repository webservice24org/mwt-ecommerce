<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Audit;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Seo\Audit\Enums\SeoAuditCrawlState;
use App\Domain\Seo\Audit\Enums\SeoAuditSubjectType;
use App\Domain\Seo\Audit\Services\SeoAuditCatalogCoverageSeeder;
use App\Domain\Seo\Audit\Services\SeoAuditRunLifecycle;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\SeoAuditCrawlPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SeoAuditCatalogCoverageSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_only_public_products_and_active_categories_with_authoritative_subjects(): void
    {
        $lifecycle = app(SeoAuditRunLifecycle::class);
        $run = $lifecycle->start(
            $lifecycle->request(Admin::factory()->create()),
        );

        $product = Product::factory()->create([
            'slug' => 'audit-public-product',
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        Product::factory()->create([
            'slug' => 'audit-future-product',
            'status' => ProductStatus::Published,
            'published_at' => now()->addDay(),
        ]);

        Product::factory()->create([
            'slug' => 'audit-draft-product',
            'status' => ProductStatus::Draft,
        ]);

        $category = Category::factory()->create([
            'slug' => 'audit-active-category',
            'is_active' => true,
        ]);

        Category::factory()->create([
            'slug' => 'audit-inactive-category',
            'is_active' => false,
        ]);

        $summary = app(SeoAuditCatalogCoverageSeeder::class)->seed(
            run: $run,
            canonicalHost: 'https://example.test',
        );

        self::assertSame(1, $summary->publishedProducts);
        self::assertSame(1, $summary->activeCategories);
        self::assertSame(2, $summary->catalogUrls);
        self::assertSame(2, $summary->newPages);

        $productUrl = 'https://example.test'.route(
            'products.show',
            ['slug' => $product->slug],
            false,
        );

        $categoryUrl = 'https://example.test'.route(
            'categories.show',
            ['slug' => $category->slug],
            false,
        );

        $productPage = SeoAuditCrawlPage::query()
            ->where('seo_audit_run_id', $run->id)
            ->where('url', $productUrl)
            ->firstOrFail();

        self::assertSame(SeoAuditCrawlState::Discovered, $productPage->state);
        self::assertSame(SeoAuditSubjectType::Product, $productPage->subject_type);
        self::assertSame('product:'.$product->id, $productPage->subject_key);

        $categoryPage = SeoAuditCrawlPage::query()
            ->where('seo_audit_run_id', $run->id)
            ->where('url', $categoryUrl)
            ->firstOrFail();

        self::assertSame(SeoAuditSubjectType::Category, $categoryPage->subject_type);
        self::assertSame('category:'.$category->id, $categoryPage->subject_key);

        self::assertDatabaseMissing('seo_audit_crawl_pages', [
            'url' => 'https://example.test/products/audit-future-product',
        ]);
        self::assertDatabaseMissing('seo_audit_crawl_pages', [
            'url' => 'https://example.test/products/audit-draft-product',
        ]);
        self::assertDatabaseMissing('seo_audit_crawl_pages', [
            'url' => 'https://example.test/category/audit-inactive-category',
        ]);
    }
}
