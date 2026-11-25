<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\FinalClosure;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SeoAiFinalReleaseSourceContractTest extends TestCase
{
    #[Test]
    #[DataProvider('storefrontControllerProvider')]
    public function primary_storefront_controllers_keep_server_authoritative_seo_and_canonical_resolution(
        string $relativePath,
        string $component,
    ): void {
        $source = $this->source($relativePath);

        self::assertStringContainsString(
            'StorefrontSeoResolver',
            $source,
        );

        self::assertStringContainsString(
            'CanonicalUrlResolver',
            $source,
        );

        self::assertStringContainsString(
            "'seo' =>",
            $source,
        );

        self::assertStringContainsString(
            $component,
            $source,
        );
    }

    #[Test]
    public function product_release_path_keeps_structured_data_reviews_variants_and_old_slug_redirects_together(): void
    {
        $source = $this->source(
            'app/Http/Controllers/Frontend/ProductController.php',
        );

        foreach ([
            'ProductStructuredDataResolver',
            'ProductVariantStructuredDataResolver',
            'StructuredDataDocumentMerger',
            'StorefrontProductReviewAggregateQuery',
            'AggregateRatingStructuredDataEnricher',
            'SeoSlugRedirectHistory',
            "'structuredData' =>",
            "'selectedVariantId' =>",
        ] as $requiredFragment) {
            self::assertStringContainsString(
                $requiredFragment,
                $source,
            );
        }

        self::assertStringNotContainsString(
            'redirect()->away(',
            $source,
        );
    }

    #[Test]
    public function category_brand_and_builder_pages_keep_server_breadcrumb_structured_data(): void
    {
        foreach ([
            'app/Http/Controllers/Frontend/CategoryController.php',
            'app/Http/Controllers/Frontend/BrandController.php',
            'app/Http/Controllers/Frontend/PageController.php',
        ] as $relativePath) {
            $source = $this->source(
                $relativePath,
            );

            self::assertStringContainsString(
                'BreadcrumbStructuredDataResolver',
                $source,
            );

            self::assertStringContainsString(
                'breadcrumbStructuredData',
                $source,
            );
        }
    }

    #[Test]
    public function storefront_head_keeps_canonical_robots_open_graph_twitter_and_json_ld_without_browser_side_seo_fetching(): void
    {
        $source = $this->source(
            'resources/js/Components/Frontend/Seo/StorefrontSeo.tsx',
        );

        foreach ([
            'rel="canonical"',
            'name="robots"',
            'property="og:title"',
            'property="og:description"',
            'property="og:url"',
            'name="twitter:card"',
            'type="application/ld+json"',
            'JSON.stringify(',
        ] as $requiredFragment) {
            self::assertStringContainsString(
                $requiredFragment,
                $source,
            );
        }

        foreach ([
            'fetch(',
            'axios.',
            'useEffect(',
        ] as $forbiddenFragment) {
            self::assertStringNotContainsString(
                $forbiddenFragment,
                $source,
            );
        }
    }

    #[Test]
    public function core_web_vitals_image_safeguards_remain_present_on_primary_and_shared_product_images(): void
    {
        $viewer = $this->source(
            'resources/js/Components/Frontend/Catalog/ProductDetails/ProductGalleryViewer.tsx',
        );

        foreach ([
            'width={image.width ?? undefined}',
            'height={image.height ?? undefined}',
            'loading="eager"',
            'fetchPriority="high"',
        ] as $requiredFragment) {
            self::assertStringContainsString(
                $requiredFragment,
                $viewer,
            );
        }

        $shared = $this->source(
            'resources/js/Components/Frontend/Catalog/ProductImage.tsx',
        );

        self::assertStringContainsString(
            'width={image.width ?? undefined}',
            $shared,
        );

        self::assertStringContainsString(
            'height={image.height ?? undefined}',
            $shared,
        );

        self::assertMatchesRegularExpression(
            "/fetchPriority=\{\s*priority\s*\?\s*'high'\s*:\s*'auto'\s*\}/s",
            $shared,
        );
    }

    #[Test]
    public function ai_generation_review_bulk_publication_and_audit_contract_keep_human_governance_boundaries(): void
    {
        $generation = $this->source(
            'app/Domain/Seo/Ai/Services/AiSeoSuggestionGenerationService.php',
        );

        self::assertStringContainsString(
            'SeoAiLegacyRequestGovernance',
            $generation,
        );

        $review = $this->source(
            'app/Http/Controllers/Admin/Seo/AiSeoSuggestionReviewController.php',
        );

        self::assertStringContainsString(
            'SeoAiLegacySuggestionGovernance',
            $review,
        );

        self::assertStringContainsString(
            '->approve(',
            $review,
        );

        self::assertStringContainsString(
            '->reject(',
            $review,
        );

        self::assertStringNotContainsString(
            '->markPublished(',
            $review,
        );

        $bulk = $this->source(
            'app/Domain/Seo/Ai/Bulk/Services/AiSeoBulkApplicationService.php',
        );

        self::assertStringContainsString(
            'SeoAiLegacySuggestionGovernance',
            $bulk,
        );

        self::assertStringContainsString(
            '->markPublished(',
            $bulk,
        );

        $auditQuery = $this->source(
            'app/Domain/Seo/AiGovernance/Queries/GetSeoAiGovernanceAuditWorkspaceQuery.php',
        );

        self::assertStringContainsString(
            'private const RUNS_PER_PAGE = 20;',
            $auditQuery,
        );

        self::assertStringContainsString(
            'private const ATTEMPT_LIMIT = 50;',
            $auditQuery,
        );

        self::assertStringContainsString(
            'private const EVENT_LIMIT = 100;',
            $auditQuery,
        );

        $auditPage = $this->source(
            'resources/js/Pages/Admin/Seo/AiGovernance/Index.tsx',
        );

        self::assertStringNotContainsString(
            'router.post(',
            $auditPage,
        );

        self::assertStringNotContainsString(
            'router.put(',
            $auditPage,
        );

        self::assertStringNotContainsString(
            'router.delete(',
            $auditPage,
        );
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function storefrontControllerProvider(): array
    {
        return [
            'product' => [
                'app/Http/Controllers/Frontend/ProductController.php',
                "'Frontend/Products/Show'",
            ],
            'category' => [
                'app/Http/Controllers/Frontend/CategoryController.php',
                "'Frontend/Categories/Show'",
            ],
            'brand' => [
                'app/Http/Controllers/Frontend/BrandController.php',
                "'Frontend/Brands/Show'",
            ],
            'builder page' => [
                'app/Http/Controllers/Frontend/PageController.php',
                "'Frontend/Pages/Show'",
            ],
        ];
    }

    private function source(string $relativePath): string
    {
        $path = base_path(
            $relativePath,
        );

        self::assertFileExists(
            $path,
        );

        $source = file_get_contents(
            $path,
        );

        self::assertIsString(
            $source,
            "Unable to read source file: {$relativePath}",
        );

        return $source;
    }
}
