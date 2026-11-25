<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Enums;

enum AiSeoSuggestionField: string
{
    /*
     * Safe draft-only text fields.
     *
     * Intentionally excluded:
     * - canonical_url
     * - robots_index / robots_follow
     * - og_image / twitter_image
     * - structured data
     * - publication/catalog/business fields
     */
    case MetaTitle = 'meta_title';
    case MetaDescription = 'meta_description';
    case OpenGraphTitle = 'og_title';
    case OpenGraphDescription = 'og_description';
    case TwitterTitle = 'twitter_title';
    case TwitterDescription = 'twitter_description';
}
