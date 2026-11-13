<?php

declare(strict_types=1);

namespace Tests\Feature\Wishlist;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class WishlistFrontendSynchronizationTest extends TestCase
{
    #[Test]
    public function wishlist_event_helper_uses_explicit_product_and_reset_contracts(): void
    {
        $source = $this->source(
            'resources/js/lib/storefrontWishlist.ts',
        );

        $this->assertStringContainsString(
            "type: 'product'",
            $source,
        );

        $this->assertStringContainsString(
            "type: 'reset'",
            $source,
        );

        $this->assertStringContainsString(
            'dispatchWishlistProductChanged',
            $source,
        );

        $this->assertStringContainsString(
            'dispatchWishlistReset',
            $source,
        );

        $this->assertStringContainsString(
            'productIds: number[]',
            $source,
        );

        $this->assertStringNotContainsString(
            'dispatchWishlistChanged(',
            $source,
        );
    }

    #[Test]
    public function wishlist_toggle_button_handles_product_and_reset_events(): void
    {
        $source = $this->source(
            'resources/js/Components/Frontend/Wishlist/WishlistToggleButton.tsx',
        );

        $this->assertStringContainsString(
            'STOREFRONT_WISHLIST_CHANGED',
            $source,
        );

        $this->assertStringContainsString(
            'customEvent.detail.type ===',
            $source,
        );

        $this->assertStringContainsString(
            "'product'",
            $source,
        );

        $this->assertStringContainsString(
            'customEvent.detail.productId !==',
            $source,
        );

        $this->assertStringContainsString(
            'customEvent.detail.productIds.includes(',
            $source,
        );

        $this->assertStringContainsString(
            'dispatchWishlistProductChanged(',
            $source,
        );

        /*
         * The mutation result is applied only after
         * the server response has been decoded.
         */
        $responsePosition = strpos(
            $source,
            'await response.json()',
        );

        $dispatchPosition = strpos(
            $source,
            'dispatchWishlistProductChanged(',
        );

        $this->assertNotFalse(
            $responsePosition,
        );

        $this->assertNotFalse(
            $dispatchPosition,
        );

        $this->assertGreaterThan(
            $responsePosition,
            $dispatchPosition,
        );
    }

    #[Test]
    public function header_resynchronizes_from_server_prop_and_browser_events(): void
    {
        $source =
            $this->megaMenuHeaderSource();

        /*
         * Server-provided count remains the default
         * authoritative value. Browser events only
         * provide a temporary local override.
         */
        $this->assertStringContainsString(
            'wishlistCountOverride',
            $source,
        );

        $this->assertStringContainsString(
            'wishlistCountOverride ??',
            $source,
        );

        $this->assertStringContainsString(
            'wishlist?.count ??',
            $source,
        );

        /*
         * Wishlist browser events update only the
         * temporary count override.
         */
        $this->assertStringContainsString(
            'STOREFRONT_WISHLIST_CHANGED',
            $source,
        );

        $this->assertStringContainsString(
            'setWishlistCountOverride(',
            $source,
        );

        $this->assertStringContainsString(
            'customEvent.detail.count',
            $source,
        );

        /*
         * After an Inertia request completes, release
         * the temporary override so the latest server
         * prop becomes authoritative again.
         */
        $this->assertStringContainsString(
            'router.on(',
            $source,
        );

        $this->assertStringContainsString(
            "'finish'",
            $source,
        );

        $this->assertStringContainsString(
            'setWishlistCountOverride(',
            $source,
        );

        $this->assertStringContainsString(
            'null',
            $source,
        );

        /*
         * Regression guard: do not restore the
         * lint-invalid prop-to-state effect.
         */
        $this->assertStringNotContainsString(
            'useState(wishlist?.count ?? 0)',
            $source,
        );

        $this->assertStringNotContainsString(
            'setWishlistCount(wishlist?.count ?? 0)',
            $source,
        );

        /*
         * Header deliberately consumes only the count
         * from product/reset events.
         */
        $listener =
            $this->headerWishlistListener(
                $source,
            );

        $this->assertStringContainsString(
            'customEvent.detail.count',
            $listener,
        );

        $this->assertStringNotContainsString(
            'customEvent.detail.productId',
            $listener,
        );

        $this->assertStringNotContainsString(
            'customEvent.detail.productIds',
            $listener,
        );
    }

    #[Test]
    public function wishlist_page_dispatches_explicit_remove_and_reset_events(): void
    {
        $source = $this->source(
            'resources/js/Pages/Frontend/Wishlist/Index.tsx',
        );

        $this->assertStringContainsString(
            'dispatchWishlistProductChanged(',
            $source,
        );

        $this->assertStringContainsString(
            'item.product.id',
            $source,
        );

        $this->assertStringContainsString(
            'dispatchWishlistReset(',
            $source,
        );

        $this->assertStringContainsString(
            '[]',
            $source,
        );

        /*
         * Clear must no longer fake product ID 0.
         */
        $this->assertStringNotContainsString(
            'dispatchWishlistChanged(',
            $source,
        );

        $this->assertStringNotContainsString(
            "dispatchWishlistProductChanged(\n                            0,",
            $source,
        );
    }

    #[Test]
    public function wishlist_add_to_cart_uses_cart_event_not_wishlist_event(): void
    {
        $source = $this->source(
            'resources/js/Pages/Frontend/Wishlist/Index.tsx',
        );

        $addToCartPosition = strpos(
            $source,
            'const addToCart',
        );

        $returnPosition = strpos(
            $source,
            "\n    return (",
            $addToCartPosition === false
            ? 0
            : $addToCartPosition,
        );

        $this->assertNotFalse(
            $addToCartPosition,
        );

        $this->assertNotFalse(
            $returnPosition,
        );

        $addToCartSource = substr(
            $source,
            $addToCartPosition,
            $returnPosition - $addToCartPosition,
        );

        $this->assertStringContainsString(
            'dispatchCartChanged(',
            $addToCartSource,
        );

        $this->assertStringNotContainsString(
            'dispatchWishlistProductChanged(',
            $addToCartSource,
        );

        $this->assertStringNotContainsString(
            'dispatchWishlistReset(',
            $addToCartSource,
        );
    }

    #[Test]
    public function product_detail_uses_shared_wishlist_toggle_button(): void
    {
        $source = $this->source(
            'resources/js/Pages/Frontend/Products/Show.tsx',
        );

        $this->assertStringContainsString(
            "import WishlistToggleButton from '@/Components/Frontend/Wishlist/WishlistToggleButton'",
            $source,
        );

        $this->assertStringContainsString(
            '<WishlistToggleButton',
            $source,
        );

        $this->assertStringContainsString(
            'productId={',
            $source,
        );

        $this->assertStringContainsString(
            'product.id',
            $source,
        );
    }

    private function megaMenuHeaderSource(): string
    {
        $candidates = [
            'resources/js/Components/Frontend/HeaderBuilder/MegaMenuHeader.tsx',
            'resources/js/Components/Frontend/Header/MegaMenuHeader.tsx',
            'resources/js/Components/Frontend/MegaMenuHeader.tsx',
        ];

        foreach ($candidates as $path) {
            if (is_file(base_path($path))) {
                return $this->source($path);
            }
        }

        self::fail(
            'MegaMenuHeader.tsx was not found in the expected storefront component paths.',
        );
    }

    private function headerWishlistListener(
        string $source,
    ): string {
        $start = strpos(
            $source,
            'const handleWishlistChanged',
        );

        $this->assertNotFalse(
            $start,
        );

        $end = strpos(
            $source,
            '}, [])',
            $start,
        );

        $this->assertNotFalse(
            $end,
        );

        return substr(
            $source,
            $start,
            ($end - $start) + 6,
        );
    }

    private function source(
        string $relativePath,
    ): string {
        $path = base_path(
            $relativePath,
        );

        $this->assertFileExists(
            $path,
        );

        $source = file_get_contents(
            $path,
        );

        $this->assertNotFalse(
            $source,
        );

        return $source;
    }
}
