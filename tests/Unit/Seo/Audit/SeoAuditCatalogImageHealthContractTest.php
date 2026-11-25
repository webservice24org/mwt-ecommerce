<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Audit;

use App\Domain\Seo\Audit\Enums\SeoAuditImageHealthState;
use App\Domain\Seo\Audit\Services\SeoAuditImageHealthEnricher;
use App\Domain\Seo\Audit\Services\SeoAuditTechnicalRuleEvaluator;
use PHPUnit\Framework\TestCase;

final class SeoAuditCatalogImageHealthContractTest extends TestCase
{
    public function test_n5_uses_authoritative_catalog_and_bounded_same_origin_image_health(): void
    {
        self::assertSame(5000, SeoAuditImageHealthEnricher::MAX_IMAGE_RESOURCES_PER_RUN);
        self::assertSame('skipped_external', SeoAuditImageHealthState::SkippedExternal->value);
        self::assertSame(
            'seo-audit-image-health-v1',
            SeoAuditTechnicalRuleEvaluator::IMAGE_HEALTH_RULE_VERSION,
        );

        $projectRoot = dirname(__DIR__, 4);
        $catalog = file_get_contents(
            $projectRoot.'/app/Domain/Seo/Audit/Services/SeoAuditCatalogCoverageSeeder.php',
        );
        $checker = file_get_contents(
            $projectRoot.'/app/Domain/Seo/Audit/Services/SeoAuditHttpImageHealthChecker.php',
        );
        $evaluator = file_get_contents(
            $projectRoot.'/app/Domain/Seo/Audit/Services/SeoAuditTechnicalRuleEvaluator.php',
        );

        self::assertIsString($catalog);
        self::assertIsString($checker);
        self::assertIsString($evaluator);

        self::assertStringContainsString('Product::query()', $catalog);
        self::assertStringContainsString('->published()', $catalog);
        self::assertStringContainsString("->where('is_active', true)", $catalog);
        self::assertStringContainsString("'products.show'", $catalog);
        self::assertStringContainsString("'categories.show'", $catalog);
        self::assertStringContainsString('SeoAuditCrawlInventoryRecorder::MAX_PAGES_PER_RUN', $catalog);

        self::assertStringContainsString('normalizeInternal(', $checker);
        self::assertStringContainsString('SkippedExternal', $checker);
        self::assertStringContainsString('->withoutRedirecting()', $checker);
        self::assertStringNotContainsString('->withOptions([\'allow_redirects\' => true', $checker);

        self::assertStringContainsString('SeoAuditImageHealthState::Broken', $evaluator);
        self::assertStringContainsString('seo_audit_image_resources as resources', $evaluator);
        self::assertStringNotContainsString('Http::', $evaluator);
        self::assertStringNotContainsString('OpenAI', $evaluator);
        self::assertStringNotContainsString('->complete(', $evaluator);
    }
}
