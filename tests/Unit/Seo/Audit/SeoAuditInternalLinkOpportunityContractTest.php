<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Audit;

use PHPUnit\Framework\TestCase;

final class SeoAuditInternalLinkOpportunityContractTest extends TestCase
{
    public function test_n8_is_deterministic_bounded_and_never_auto_publishes_links(): void
    {
        $projectRoot = dirname(__DIR__, 4);
        $source = file_get_contents(
            $projectRoot.'/app/Domain/Seo/Audit/Services/SeoAuditInternalLinkOpportunityBuilder.php',
        );

        self::assertIsString($source);
        self::assertStringContainsString(
            "ENGINE_VERSION = 'seo-internal-links-v1'",
            $source,
        );
        self::assertStringContainsString(
            'MAX_TARGETS_PER_RUN = 250',
            $source,
        );
        self::assertStringContainsString(
            'MAX_SOURCES_PER_TARGET = 2',
            $source,
        );
        self::assertStringContainsString(
            'MAX_OPPORTUNITIES_PER_RUN = 500',
            $source,
        );
        self::assertStringContainsString(
            'SeoAuditRule::OrphanProduct',
            $source,
        );
        self::assertStringContainsString(
            'SeoAuditRule::OrphanCategory',
            $source,
        );
        self::assertStringContainsString(
            'Review context and anchor text before publishing.',
            $source,
        );
        self::assertStringNotContainsString(
            'Http::',
            $source,
        );
        self::assertStringNotContainsString(
            'dispatch(',
            $source,
        );
        self::assertStringNotContainsString(
            'OpenAI',
            $source,
        );
        self::assertStringNotContainsString(
            '->update([\'content\'',
            $source,
        );
    }
}
