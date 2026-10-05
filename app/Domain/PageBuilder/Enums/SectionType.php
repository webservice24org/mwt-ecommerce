<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Enums;

enum SectionType: string
{
    case Hero = 'hero';
    case FeaturedProducts = 'featured_products';
    case ProductCategories = 'product_categories';
    case ProductCollection = 'product_collection';
    case PromotionalBanner = 'promotional_banner';
    case Content = 'content';
    case CallToAction = 'call_to_action';

    case ProductGrid = 'product_grid';
    case CategoryProducts = 'category_products';
    case CategoryShowcase = 'category_showcase';
    case NewArrivals = 'new_arrivals';

    public function label(): string
    {
        return match ($this) {
            self::Hero => 'Hero',
            self::FeaturedProducts => 'Featured Products',
            self::ProductCategories => 'Product Categories',
            self::ProductCollection => 'Product Collection',
            self::PromotionalBanner => 'Promotional Banner',
            self::Content => 'Content',
            self::CallToAction => 'Call to Action',

            self::ProductGrid => 'Product Grid',
            self::CategoryProducts => 'Category Products',
            self::CategoryShowcase => 'Category Showcase',
            self::NewArrivals => 'New Arrivals',
        };
    }
}
