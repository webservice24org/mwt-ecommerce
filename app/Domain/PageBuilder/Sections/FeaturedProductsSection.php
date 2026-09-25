<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Sections;

use App\Domain\PageBuilder\Data\SectionTemplateData;
use App\Domain\PageBuilder\Enums\CatalogSourceType;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Sections\Contracts\SectionConfigSchema;
use App\Domain\PageBuilder\Sections\Contracts\SectionDefinition;
use App\Domain\PageBuilder\Sections\Schemas\FeaturedProductsConfigSchema;

final class FeaturedProductsSection implements SectionDefinition
{
    public function type(): SectionType
    {
        return SectionType::FeaturedProducts;
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
                description: 'Display a curated selection of featured products in a responsive grid.',
                category: 'Products',
            ),
        ];
    }

    public function defaultTemplate(): string
    {
        return 'grid';
    }

    public function defaultConfig(): array
    {
        return [
            'title' => 'Featured Products',
            'limit' => 8,
            'source' => [
                'type' => CatalogSourceType::Featured->value,
            ],
        ];
    }

    public function configSchema(): SectionConfigSchema
    {
        return new FeaturedProductsConfigSchema;
    }
}
