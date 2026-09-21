<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\PageBuilder\Enums\SectionType;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PageSection>
 */
final class PageSectionFactory extends Factory
{
    protected $model = PageSection::class;

    public function definition(): array
    {
        return [
            'page_id' => Page::factory(),
            'type' => SectionType::FeaturedProducts,
            'template' => 'grid',
            'config' => [
                'title' => 'Featured Products',
                'limit' => 8,
            ],
            'position' => 0,
            'is_enabled' => true,
        ];
    }
}
