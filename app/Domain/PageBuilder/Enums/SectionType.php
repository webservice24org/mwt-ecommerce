<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Enums;

enum SectionType: string
{
    case Hero = 'hero';
    case FeaturedProducts = 'featured_products';
    case ProductCategories = 'product_categories';

    case ProductGrid = 'product_grid';
    case CategoryProducts = 'category_products';
    case CategoryShowcase = 'category_showcase';
    case PromotionalBanner = 'promotional_banner';
    case NewArrivals = 'new_arrivals';

    public function label(): string
    {
        return match ($this) {
            self::Hero => 'Hero',
            self::FeaturedProducts => 'Featured Products',
            self::ProductCategories => 'Product Categories',

            self::ProductGrid => 'Product Grid',
            self::CategoryProducts => 'Category Products',
            self::CategoryShowcase => 'Category Showcase',
            self::PromotionalBanner => 'Promotional Banner',
            self::NewArrivals => 'New Arrivals',
        };
    }
}
