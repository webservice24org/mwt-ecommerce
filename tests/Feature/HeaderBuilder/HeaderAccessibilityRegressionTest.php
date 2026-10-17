<?php

declare(strict_types=1);

namespace Tests\Feature\HeaderBuilder;

use Tests\TestCase;

final class HeaderAccessibilityRegressionTest extends TestCase
{
    public function test_production_header_keeps_accessible_popup_relationships(): void
    {
        $source = $this->productionHeaderSource();

        $this->assertStringContainsString(
            'aria-expanded={mobileMenuOpen}',
            $source,
        );

        $this->assertStringContainsString(
            'aria-controls={mobileMenuId}',
            $source,
        );

        $this->assertStringContainsString(
            'aria-controls={megaMenuId}',
            $source,
        );

        $this->assertStringContainsString(
            'aria-haspopup="true"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-controls={accountMenuId}',
            $source,
        );

        $this->assertStringContainsString(
            'aria-haspopup="menu"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-controls={cartDrawerId}',
            $source,
        );

        $this->assertStringContainsString(
            'aria-haspopup="dialog"',
            $source,
        );
    }

    public function test_production_search_keeps_combobox_and_listbox_semantics(): void
    {
        $source = $this->productionHeaderSource();

        $this->assertStringContainsString(
            'role="combobox"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-autocomplete="list"',
            $source,
        );

        $this->assertStringContainsString(
            'role="listbox"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-label="Search suggestions"',
            $source,
        );

        $this->assertStringContainsString(
            'role="status"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-live="polite"',
            $source,
        );
    }

    public function test_production_cart_keeps_dialog_semantics_and_focus_management(): void
    {
        $source = $this->productionHeaderSource();

        $this->assertStringContainsString(
            'role="dialog"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-modal="true"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-labelledby="cart-drawer-title"',
            $source,
        );

        $this->assertStringContainsString(
            'tabIndex={-1}',
            $source,
        );

        $this->assertStringContainsString(
            'drawerRef.current?.focus()',
            $source,
        );

        $this->assertStringContainsString(
            'cartTriggerRef.current?.focus()',
            $source,
        );
    }

    public function test_production_header_keeps_global_escape_handling(): void
    {
        $source = $this->productionHeaderSource();

        $this->assertStringContainsString(
            "event.key !== 'Escape'",
            $source,
        );

        $this->assertStringContainsString(
            "window.addEventListener('keydown'",
            $source,
        );

        $this->assertStringContainsString(
            "window.removeEventListener('keydown'",
            $source,
        );

        $this->assertStringContainsString(
            'setSearchOpen(false)',
            $source,
        );

        $this->assertStringContainsString(
            'setMegaMenuOpen(false)',
            $source,
        );

        $this->assertStringContainsString(
            'setAccountOpen(false)',
            $source,
        );

        $this->assertStringContainsString(
            'setMobileMenuOpen(false)',
            $source,
        );

        $this->assertStringContainsString(
            'setCartOpen(false)',
            $source,
        );
    }

    public function test_production_cart_locks_background_scrolling(): void
    {
        $source = $this->productionHeaderSource();

        $this->assertStringContainsString(
            'document.body.style.overflow',
            $source,
        );

        $this->assertStringContainsString(
            "document.body.style.overflow = 'hidden'",
            $source,
        );

        $this->assertStringContainsString(
            'document.body.style.overflow = previousOverflow',
            $source,
        );
    }

    public function test_production_header_keeps_mobile_and_popup_scroll_containment(): void
    {
        $source = $this->productionHeaderSource();

        $this->assertStringContainsString(
            'overflow-y-auto',
            $source,
        );

        $this->assertStringContainsString(
            'overscroll-contain',
            $source,
        );

        $this->assertStringContainsString(
            'max-h-[calc(100dvh-4rem)]',
            $source,
        );

        $this->assertStringContainsString(
            'min-w-0',
            $source,
        );

        $this->assertStringContainsString(
            'truncate',
            $source,
        );

        $this->assertStringContainsString(
            'break-words',
            $source,
        );
    }

    public function test_production_header_keeps_minimum_touch_targets_on_primary_controls(): void
    {
        $source = $this->productionHeaderSource();

        $this->assertStringContainsString(
            'h-11 w-11',
            $source,
        );

        $this->assertStringContainsString(
            'min-h-11',
            $source,
        );
    }

    public function test_production_cart_uses_shared_storefront_money_formatter(): void
    {
        $source = $this->productionHeaderSource();

        $this->assertStringContainsString(
            "import { formatMoney } from '@/lib/money'",
            $source,
        );

        $this->assertStringContainsString(
            'formatMoney(item.unit_price)',
            $source,
        );

        $this->assertStringContainsString(
            'formatMoney(item.line_total)',
            $source,
        );

        $this->assertStringContainsString(
            'formatMoney(cart.subtotal)',
            $source,
        );

        $this->assertStringNotContainsString(
            "currency: 'USD'",
            $source,
        );
    }

    public function test_builder_preview_keeps_accessible_popup_relationships(): void
    {
        $source = $this->previewSource();

        $this->assertStringContainsString(
            'aria-controls={mobileMenuId}',
            $source,
        );

        $this->assertStringContainsString(
            'aria-controls={megaMenuId}',
            $source,
        );

        $this->assertStringContainsString(
            'aria-controls={accountMenuId}',
            $source,
        );

        $this->assertStringContainsString(
            'aria-controls={cartDrawerId}',
            $source,
        );

        $this->assertStringContainsString(
            'aria-haspopup="menu"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-haspopup="dialog"',
            $source,
        );
    }

    public function test_builder_preview_keeps_search_semantics(): void
    {
        $source = $this->previewSource();

        $this->assertStringContainsString(
            'role="combobox"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-haspopup="listbox"',
            $source,
        );

        $this->assertStringContainsString(
            'role="listbox"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-label="Search suggestions preview"',
            $source,
        );
    }

    public function test_builder_preview_keeps_cart_dialog_and_focus_management(): void
    {
        $source = $this->previewSource();

        $this->assertStringContainsString(
            'role="dialog"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-modal="true"',
            $source,
        );

        $this->assertStringContainsString(
            'tabIndex={-1}',
            $source,
        );

        $this->assertStringContainsString(
            'drawerRef.current?.focus()',
            $source,
        );

        $this->assertStringContainsString(
            'cartTriggerRef.current?.focus()',
            $source,
        );
    }

    public function test_builder_preview_keeps_escape_handling(): void
    {
        $source = $this->previewSource();

        $this->assertStringContainsString(
            "event.key !== 'Escape'",
            $source,
        );

        $this->assertStringContainsString(
            "window.addEventListener('keydown'",
            $source,
        );

        $this->assertStringContainsString(
            "window.removeEventListener('keydown'",
            $source,
        );
    }

    public function test_builder_preview_keeps_responsive_overflow_guards(): void
    {
        $source = $this->previewSource();

        $this->assertStringContainsString(
            'overflow-hidden',
            $source,
        );

        $this->assertStringContainsString(
            'overflow-y-auto',
            $source,
        );

        $this->assertStringContainsString(
            'overscroll-contain',
            $source,
        );

        $this->assertStringContainsString(
            'max-h-[min(32rem,70dvh)]',
            $source,
        );

        $this->assertStringContainsString(
            'min-w-0',
            $source,
        );

        $this->assertStringContainsString(
            'truncate',
            $source,
        );

        $this->assertStringContainsString(
            'break-words',
            $source,
        );
    }

    public function test_builder_preview_uses_shared_storefront_money_formatter(): void
    {
        $source = $this->previewSource();

        $this->assertStringContainsString(
            "import { formatMoney } from '@/lib/money'",
            $source,
        );

        $this->assertStringContainsString(
            'formatMoney(',
            $source,
        );

        $this->assertStringNotContainsString(
            "currency: 'USD'",
            $source,
        );
    }

    public function test_storefront_layout_keeps_horizontal_overflow_guard(): void
    {
        $source = $this->frontendLayoutSource();

        $this->assertStringContainsString(
            'overflow-x-clip',
            $source,
        );

        $this->assertStringContainsString(
            'min-w-0',
            $source,
        );
    }

    private function productionHeaderSource(): string
    {
        return $this->readSource(
            'resources/js/Components/Frontend/HeaderBuilder/MegaMenuHeader.tsx',
        );
    }

    private function previewSource(): string
    {
        return $this->readSource(
            'resources/js/Components/Admin/HeaderBuilder/Preview/HeaderDesign1Preview.tsx',
        );
    }

    private function frontendLayoutSource(): string
    {
        return $this->readSource(
            'resources/js/Layouts/Frontend/FrontendLayout.tsx',
        );
    }

    private function readSource(
        string $relativePath,
    ): string {
        $path = base_path(
            $relativePath,
        );

        $this->assertFileExists(
            $path,
        );

        $contents = file_get_contents(
            $path,
        );

        $this->assertNotFalse(
            $contents,
        );

        return $contents;
    }
}
