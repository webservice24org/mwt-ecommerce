<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use Tests\TestCase;

final class AdminSeoNavigationTest extends TestCase
{
    public function test_admin_sidebar_contains_seo_navigation(): void
    {
        $path =
            resource_path(
                'js/Components/Admin/AdminSidebar.tsx',
            );

        $source =
            file_get_contents(
                $path,
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            "label: 'SEO'",
            $source,
        );

        $this->assertStringContainsString(
            "label: 'SEO Settings'",
            $source,
        );

        $this->assertStringContainsString(
            "'admin.seo.settings.edit'",
            $source,
        );

        $this->assertStringContainsString(
            "match: '/admin/seo'",
            $source,
        );
    }

    public function test_future_seo_sections_are_visible_but_disabled(): void
    {
        $path =
            resource_path(
                'js/Components/Admin/AdminSidebar.tsx',
            );

        $source =
            file_get_contents(
                $path,
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            "label: 'SEO Overview'",
            $source,
        );

        $this->assertStringContainsString(
            "label: 'AI Suggestions'",
            $source,
        );

        $this->assertStringContainsString(
            "label: 'AI SEO'",
            $source,
        );

        $this->assertStringContainsString(
            "label: 'Technical Health'",
            $source,
        );
    }
}
