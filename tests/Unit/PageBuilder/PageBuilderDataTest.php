<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Data\PageData;
use App\Domain\PageBuilder\Data\PageSectionData;
use App\Domain\PageBuilder\Data\PageSeoData;
use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\PageBuilder\Enums\PageType;
use App\Domain\PageBuilder\Enums\SectionType;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

final class PageBuilderDataTest extends TestCase
{
    public function test_page_section_serializes_to_public_contract(): void
    {
        $data = new PageSectionData(
            id: 10,
            type: SectionType::FeaturedProducts,
            template: 'grid',
            config: [
                'title' => 'Featured Products',
                'limit' => 8,
            ],
            position: 20,
            isEnabled: true,
        );

        $this->assertSame(
            [
                'id' => 10,
                'type' => 'featured_products',
                'template' => 'grid',
                'config' => [
                    'title' => 'Featured Products',
                    'limit' => 8,
                ],
                'position' => 20,
                'is_enabled' => true,
            ],
            $data->toArray(),
        );
    }

    public function test_page_serializes_to_expected_contract(): void
    {
        $publishedAt = CarbonImmutable::parse(
            '2026-09-21 12:00:00',
        );

        $section = new PageSectionData(
            id: 10,
            type: SectionType::FeaturedProducts,
            template: 'grid',
            config: [
                'title' => 'Featured Products',
                'limit' => 8,
            ],
            position: 0,
            isEnabled: true,
        );

        $data = new PageData(
            id: 1,
            type: PageType::Home,
            title: 'Home',
            slug: 'home',
            status: PageStatus::Published,
            publishedAt: $publishedAt,
            seo: new PageSeoData(
                metaTitle: 'Store Home',
                metaDescription: 'Store homepage.',
            ),
            sections: [$section],
        );

        $payload = $data->toArray();

        $this->assertSame(1, $payload['id']);
        $this->assertSame('home', $payload['type']);
        $this->assertSame('published', $payload['status']);
        $this->assertSame(
            $publishedAt->toIso8601String(),
            $payload['published_at'],
        );

        $this->assertSame(
            [
                'meta_title' => 'Store Home',
                'meta_description' => 'Store homepage.',
            ],
            $payload['seo'],
        );

        $this->assertCount(1, $payload['sections']);

        $this->assertSame(
            'featured_products',
            $payload['sections'][0]['type'],
        );
    }

    public function test_new_page_contract_can_exist_without_database_identity(): void
    {
        $data = new PageData(
            id: null,
            type: PageType::Standard,
            title: 'About Us',
            slug: 'about-us',
            status: PageStatus::Draft,
            publishedAt: null,
            seo: new PageSeoData(
                metaTitle: null,
                metaDescription: null,
            ),
        );

        $payload = $data->toArray();

        $this->assertNull($payload['id']);
        $this->assertNull($payload['published_at']);
        $this->assertSame([], $payload['sections']);
    }
}
