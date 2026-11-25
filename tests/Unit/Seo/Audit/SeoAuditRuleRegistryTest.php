<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Audit;

use App\Domain\Seo\Audit\Enums\SeoAuditRule;
use App\Domain\Seo\Audit\Enums\SeoAuditSeverity;
use App\Domain\Seo\Audit\Services\SeoAuditRuleRegistry;
use PHPUnit\Framework\TestCase;

final class SeoAuditRuleRegistryTest extends TestCase
{
    public function test_every_declared_rule_has_a_label_and_default_severity(): void
    {
        $registry = new SeoAuditRuleRegistry;

        foreach (SeoAuditRule::cases() as $rule) {
            self::assertNotSame('', $registry->label($rule));
            self::assertContains($registry->defaultSeverity($rule), SeoAuditSeverity::cases());
        }

        self::assertSame('seo-audit-rules-v1', SeoAuditRuleRegistry::VERSION);
    }
}
