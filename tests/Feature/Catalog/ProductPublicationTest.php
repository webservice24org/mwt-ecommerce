<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class ProductPublicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_product_without_schedule_is_public(): void
    {
        $product = Product::factory()->create([
            'status' => ProductStatus::Published,
            'published_at' => null,
        ]);

        $this->assertTrue(
            $product->isPublished(),
        );

        $this->assertTrue(
            Product::query()
                ->published()
                ->whereKey($product->id)
                ->exists(),
        );
    }

    public function test_published_product_with_past_schedule_is_public(): void
    {
        $product = Product::factory()->create([
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $this->assertTrue(
            $product->isPublished(),
        );

        $this->assertTrue(
            Product::query()
                ->published()
                ->whereKey($product->id)
                ->exists(),
        );
    }

    public function test_future_scheduled_product_is_not_public(): void
    {
        $product = Product::factory()->create([
            'status' => ProductStatus::Published,
            'published_at' => now()->addHour(),
        ]);

        $this->assertFalse(
            $product->isPublished(),
        );

        $this->assertFalse(
            Product::query()
                ->published()
                ->whereKey($product->id)
                ->exists(),
        );
    }

    public function test_draft_product_is_not_public(): void
    {
        $product = Product::factory()->create([
            'status' => ProductStatus::Draft,
            'published_at' => null,
        ]);

        $this->assertFalse(
            $product->isPublished(),
        );

        $this->assertFalse(
            Product::query()
                ->published()
                ->whereKey($product->id)
                ->exists(),
        );
    }

    public function test_archived_product_is_not_public(): void
    {
        $product = Product::factory()->create([
            'status' => ProductStatus::Archived,
            'published_at' => now()->subDay(),
        ]);

        $this->assertFalse(
            $product->isPublished(),
        );

        $this->assertFalse(
            Product::query()
                ->published()
                ->whereKey($product->id)
                ->exists(),
        );
    }

    public function test_product_becomes_public_at_exact_scheduled_time(): void
    {
        $now = Carbon::parse(
            '2026-09-15 12:00:00',
        );

        Carbon::setTestNow($now);

        try {
            $product = Product::factory()->create([
                'status' => ProductStatus::Published,
                'published_at' => $now,
            ]);

            $this->assertTrue(
                $product->isPublished(),
            );

            $this->assertTrue(
                Product::query()
                    ->published()
                    ->whereKey($product->id)
                    ->exists(),
            );
        } finally {
            Carbon::setTestNow();
        }
    }
}
