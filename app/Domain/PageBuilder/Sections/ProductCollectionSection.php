<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Sections;

use App\Domain\PageBuilder\Data\SectionTemplateData;
use App\Domain\PageBuilder\Enums\CatalogSourceType;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Sections\Contracts\SectionConfigSchema;
use App\Domain\PageBuilder\Sections\Contracts\SectionDefinition;
use App\Domain\PageBuilder\Sections\Schemas\ProductCollectionConfigSchema;
use InvalidArgumentException;

final class ProductCollectionSection implements SectionDefinition
{
    public function type(): SectionType
    {
        return SectionType::ProductCollection;
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
                label: 'Product Grid',
                description: 'Display products from a configurable catalog source in a responsive grid.',
                category: 'Products',
            ),

            new SectionTemplateData(
                key: 'cards',
                label: 'Product Cards',
                description: 'Display products from a configurable catalog source as product cards.',
                category: 'Products',
            ),

            new SectionTemplateData(
                key: 'carousel',
                label: 'Product Carousel',
                description: 'Display products from a configurable catalog source in a horizontal carousel.',
                category: 'Products',
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
            ],

            'cards' => [
                ...$this->baseDefaults(),
                'columns' => 3,
            ],

            'carousel' => [
                ...$this->baseDefaults(),
                'columns' => 4,
                'autoplay' => true,
                'autoplay_delay' => 5000,
                'show_arrows' => true,
                'show_dots' => true,
                'effect' => 'fade',
            ],

            default => throw new InvalidArgumentException(
                sprintf(
                    'Unsupported Product Collection template [%s].',
                    $template,
                ),
            ),
        };
    }

    public function configSchema(): SectionConfigSchema
    {
        return new ProductCollectionConfigSchema;
    }

    /**
     * @return array<string, mixed>
     */
    private function baseDefaults(): array
    {
        return [
            'title' => 'Products',
            'limit' => 8,
            'source' => [
                'type' => CatalogSourceType::Latest->value,
            ],
            'show_price' => true,
            'show_rating' => true,
            'show_badges' => true,
        ];
    }
}
