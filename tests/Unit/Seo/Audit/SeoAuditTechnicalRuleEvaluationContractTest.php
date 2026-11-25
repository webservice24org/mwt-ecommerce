<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Audit;

use App\Domain\Seo\Audit\Enums\SeoAuditRule;
use App\Domain\Seo\Audit\Services\SeoAuditTechnicalRuleEvaluator;
use PHPUnit\Framework\TestCase;

final class SeoAuditTechnicalRuleEvaluationContractTest extends TestCase
{
    public function test_n4_is_deterministic_bounded_and_does_not_invent_image_health(): void
    {
        self::assertSame(
            'seo-audit-technical-rules-v1',
            SeoAuditTechnicalRuleEvaluator::VERSION,
        );
        self::assertSame(
            150,
            SeoAuditTechnicalRuleEvaluator::THIN_CATEGORY_WORD_THRESHOLD,
        );

        $projectRoot = dirname(__DIR__, 4);
        $source = file_get_contents(
            $projectRoot.'/app/Domain/Seo/Audit/Services/SeoAuditTechnicalRuleEvaluator.php',
        );

        self::assertIsString($source);
        self::assertStringContainsString(
            'SeoAuditCrawlInventoryRecorder::MAX_PAGES_PER_RUN',
            $source,
        );
        self::assertStringContainsString(
            'SeoAuditCrawlInventoryRecorder::MAX_LINKS_PER_RUN',
            $source,
        );
        self::assertStringContainsString(
            'SeoAuditPageImageRecorder::MAX_IMAGE_OBSERVATIONS_PER_RUN',
            $source,
        );
        self::assertStringContainsString(
            'where(\'images.alt_attribute_present\', false)',
            $source,
        );
        self::assertStringContainsString(
            'deferredRules: [SeoAuditRule::BrokenImage]',
            $source,
        );
        self::assertStringNotContainsString(
            'Http::',
            $source,
        );
        self::assertStringNotContainsString(
            'OpenAI',
            $source,
        );
        self::assertStringNotContainsString(
            '->complete(',
            $source,
        );
        self::assertSame(
            'broken_image',
            SeoAuditRule::BrokenImage->value,
        );
    }
}
