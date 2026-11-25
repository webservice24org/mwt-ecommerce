<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\GenerativeSearch;

use Tests\TestCase;

final class EntityAnswerabilityFrontendContractTest extends TestCase
{
    public function test_product_page_exposes_visible_authoritative_entity_facts_and_relationships(): void
    {
        $page = $this->source('resources/js/Pages/Frontend/Products/Show.tsx');
        $facts = $this->source('resources/js/Components/Frontend/Seo/ProductEntityFacts.tsx');

        self::assertStringContainsString("import ProductEntityFacts from '@/Components/Frontend/Seo/ProductEntityFacts'", $page);
        self::assertStringContainsString('<ProductEntityFacts', $page);
        self::assertStringContainsString('<dl', $facts);
        self::assertStringContainsString("'SKU'", $facts);
        self::assertStringContainsString("'Product type'", $facts);
        self::assertStringContainsString("'Available variants'", $facts);
        self::assertStringContainsString('/brand/${product.brand.slug}', $facts);
        self::assertStringContainsString('/category/${category.slug}', $facts);
    }

    public function test_category_page_exposes_real_brand_relationship_links_from_existing_filter_data(): void
    {
        $source = $this->source('resources/js/Pages/Frontend/Categories/Show.tsx');
        self::assertStringContainsString("import EntityContextLinks from '@/Components/Frontend/Seo/EntityContextLinks'", $source);
        self::assertStringContainsString('filterOptions.brands.map(', $source);
        self::assertStringContainsString('/brand/${brand.slug}', $source);
        self::assertStringContainsString('Brands in ${category.name}', $source);
        self::assertStringContainsString('alt={`${category.name} category`}', $source);
    }

    public function test_brand_page_exposes_real_category_relationship_links_from_existing_filter_data(): void
    {
        $source = $this->source('resources/js/Pages/Frontend/Brands/Show.tsx');
        self::assertStringContainsString("import EntityContextLinks from '@/Components/Frontend/Seo/EntityContextLinks'", $source);
        self::assertStringContainsString('filterOptions.categories.map(', $source);
        self::assertStringContainsString('/category/${category.slug}', $source);
        self::assertStringContainsString('Categories from ${brand.name}', $source);
    }

    public function test_classic_page_keeps_visible_h1_and_meaningful_featured_image_label(): void
    {
        $source = $this->source('resources/js/Pages/Frontend/Pages/Show.tsx');
        self::assertStringContainsString('aria-labelledby="storefront-page-title"', $source);
        self::assertStringContainsString('id="storefront-page-title"', $source);
        self::assertStringContainsString('alt={page.title}', $source);
        self::assertStringContainsString('dangerouslySetInnerHTML', $source);
        self::assertStringContainsString('<StorefrontSectionRenderer', $source);
    }

    public function test_answerability_components_do_not_hide_ai_only_copy_or_invent_schema(): void
    {
        foreach ([
            $this->source('resources/js/Components/Frontend/Seo/EntityContextLinks.tsx'),
            $this->source('resources/js/Components/Frontend/Seo/ProductEntityFacts.tsx'),
        ] as $source) {
            self::assertStringNotContainsString('sr-only', $source);
            self::assertStringNotContainsString('display:none', $source);
            self::assertStringNotContainsString('data-ai', $source);
            self::assertStringNotContainsString('application/ld+json', $source);
            self::assertStringNotContainsString('FAQPage', $source);
        }
    }

    public function test_l3_does_not_replace_existing_product_description_reviews_or_server_authoritative_seo(): void
    {
        $source = $this->source('resources/js/Pages/Frontend/Products/Show.tsx');
        self::assertStringContainsString('<StorefrontSeo', $source);
        self::assertStringContainsString('seo={', $source);
        self::assertStringContainsString('<ProductDescription', $source);
        self::assertStringContainsString('<ProductReviewsSection', $source);
        self::assertStringContainsString('<ProductReviewForm', $source);
    }

    private function source(string $path): string
    {
        $source = file_get_contents(base_path($path));
        self::assertIsString($source, $path);

        return $source;
    }
}
