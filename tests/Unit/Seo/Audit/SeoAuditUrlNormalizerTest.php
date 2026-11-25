<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Audit;

use App\Domain\Seo\Audit\Services\SeoAuditUrlNormalizer;
use DomainException;
use PHPUnit\Framework\TestCase;

final class SeoAuditUrlNormalizerTest extends TestCase
{
    public function test_internal_urls_are_resolved_without_fragments(): void
    {
        $normalizer =
            new SeoAuditUrlNormalizer;

        self::assertSame(
            'https://example.test/category/cables?page=2',
            $normalizer->normalizeInternal(
                reference: '../category/cables?page=2#grid',
                canonicalHost: 'https://EXAMPLE.test',
                baseUrl: 'https://example.test/products/widget',
            ),
        );

        self::assertSame(
            'http://example.test/legacy',
            $normalizer->normalizeInternal(
                reference: 'http://example.test/legacy#old',
                canonicalHost: 'https://example.test',
            ),
        );
    }

    public function test_external_and_non_web_links_are_not_internal_graph_nodes(): void
    {
        $normalizer =
            new SeoAuditUrlNormalizer;

        self::assertNull(
            $normalizer->normalizeInternal(
                reference: 'https://outside.example/path',
                canonicalHost: 'https://example.test',
            ),
        );

        self::assertNull(
            $normalizer->normalizeInternal(
                reference: 'mailto:sales@example.test',
                canonicalHost: 'https://example.test',
            ),
        );
    }

    public function test_invalid_canonical_host_is_rejected(): void
    {
        $this->expectException(
            DomainException::class,
        );

        (new SeoAuditUrlNormalizer)
            ->normalizeInternal(
                reference: '/',
                canonicalHost: 'example.test',
            );
    }
}
