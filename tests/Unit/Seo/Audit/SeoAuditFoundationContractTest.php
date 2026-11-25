<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Audit;

use App\Domain\Seo\Audit\Enums\SeoAuditRule;
use PHPUnit\Framework\TestCase;

final class SeoAuditFoundationContractTest extends TestCase
{
    public function test_n1_rule_contract_covers_the_roadmap_checks_without_running_a_crawler(): void
    {
        $values = array_map(static fn (SeoAuditRule $rule): string => $rule->value, SeoAuditRule::cases());

        foreach ([
            'orphan_product', 'orphan_category', 'broken_internal_link', 'redirect_chain',
            'duplicate_canonical', 'canonical_to_non_200', 'indexable_missing_canonical',
            'indexable_missing_title', 'empty_description', 'duplicate_title',
            'duplicate_description', 'missing_h1', 'multiple_h1', 'broken_image',
            'missing_alt', 'thin_category_page',
        ] as $expected) {
            self::assertContains($expected, $values);
        }

        $projectRoot = dirname(__DIR__, 4);

        $source = file_get_contents(
            $projectRoot.'/app/Domain/Seo/Audit/Services/SeoAuditRunLifecycle.php',
        );

        self::assertIsString($source);
        self::assertStringNotContainsString('Http::', $source);
        self::assertStringNotContainsString('dispatch(', $source);
        self::assertStringNotContainsString('OpenAI', $source);
        self::assertStringNotContainsString('Inertia', $source);
    }
}
