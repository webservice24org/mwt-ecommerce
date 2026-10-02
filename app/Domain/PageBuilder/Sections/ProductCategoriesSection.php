<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Sections;

use App\Domain\PageBuilder\Data\SectionTemplateData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Sections\Contracts\SectionConfigSchema;
use App\Domain\PageBuilder\Sections\Contracts\SectionDefinition;
use App\Domain\PageBuilder\Sections\Schemas\ProductCategoriesConfigSchema;
use InvalidArgumentException;

final class ProductCategoriesSection implements SectionDefinition
{
    public function type(): SectionType
    {
        return SectionType::ProductCategories;
    }

    public function label(): string
    {
        return $this->type()->label();
    }

    public function templates(): array
    {
        return [
            new SectionTemplateData(
                key: 'grid',
                label: 'Category Grid',
                description: 'Display selected product categories in a responsive grid.',
                category: 'Categories',
            ),

            new SectionTemplateData(
                key: 'cards',
                label: 'Category Cards',
                description: 'Display selected product categories as visual category cards.',
                category: 'Categories',
            ),

            new SectionTemplateData(
                key: 'carousel',
                label: 'Category Carousel',
                description: 'Display selected product categories in a horizontal carousel.',
                category: 'Categories',
            ),
        ];
    }

    public function defaultTemplate(): string
    {
        return 'grid';
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultConfig(): array
    {
        return $this->defaultConfigForTemplate(
            $this->defaultTemplate(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultConfigForTemplate(
        string $template,
    ): array {
        return match ($template) {
            'grid' => [
                ...$this->baseDefaults(),
                'columns' => 4,
                'show_product_count' => false,
            ],

            'cards' => [
                ...$this->baseDefaults(),
                'columns' => 3,
                'show_product_count' => true,
            ],

            'carousel' => [
                ...$this->baseDefaults(),
                'columns' => 4,
                'show_product_count' => false,
                'autoplay' => true,
                'autoplay_delay' => 5000,
                'show_arrows' => true,
                'show_dots' => true,
                'effect' => 'fade',
            ],

            default => throw new InvalidArgumentException(
                sprintf(
                    'Unsupported Product Categories template [%s].',
                    $template,
                ),
            ),
        };
    }

    public function configSchema(): SectionConfigSchema
    {
        return new ProductCategoriesConfigSchema;
    }

    /**
     * @return array<string, mixed>
     */
    private function baseDefaults(): array
    {
        return [
            'title' => 'Shop by Category',
            'category_ids' => [],
            'show_name' => true,
        ];
    }
}
