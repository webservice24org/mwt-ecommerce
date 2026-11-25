<?php

declare(strict_types=1);

namespace App\Domain\Seo\GenerativeSearch;

final class GenerativeSearchReadinessPolicy
{
    public const VERSION = 'generative-search-readiness-v1';

    public const THIN_CONTENT_CHARACTERS = 80;

    public const SUBSTANTIVE_CONTENT_CHARACTERS = 200;

    /**
     * @return list<string>
     */
    public static function recognizedEntityTypes(): array
    {
        return [
            'product',
            'category',
            'brand',
            'page',
        ];
    }

    /**
     * Structured data is supporting evidence only.
     *
     * It is not treated as a guaranteed AI-search inclusion
     * mechanism or as an indexing requirement.
     *
     * @return list<string>
     */
    public static function expectedStructuredDataTypes(
        string $entityType,
    ): array {
        return match ($entityType) {
            'product' => [
                'Product',
                'ProductGroup',
            ],

            'category',
            'brand',
            'page' => [
                'BreadcrumbList',
            ],

            default => [],
        };
    }
}
