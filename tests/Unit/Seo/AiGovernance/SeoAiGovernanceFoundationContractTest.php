<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\AiGovernance;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SeoAiGovernanceFoundationContractTest extends TestCase
{
    #[Test]
    public function p1_stores_governance_evidence_without_persisting_raw_ai_input_or_credentials(): void
    {
        $root = dirname(__DIR__, 4);

        $migration = file_get_contents(
            $root.'/database/migrations/2026_11_24_231500_create_seo_ai_governance_tables.php',
        );
        $minimizer = file_get_contents(
            $root.'/app/Domain/Seo/AiGovernance/Support/SeoAiInputMinimizer.php',
        );
        $manager = file_get_contents(
            $root.'/app/Domain/Seo/AiGovernance/Services/SeoAiGovernanceManager.php',
        );

        self::assertIsString($migration);
        self::assertIsString($minimizer);
        self::assertIsString($manager);

        foreach ([
            "'provider'",
            "'model_name'",
            "'prompt_version'",
            "'input_snapshot_hash'",
            "'generated_output'",
            "'generated_at'",
            "'approval_status'",
            "'published_at'",
            "'attempt_count'",
            "'input_tokens'",
            "'output_tokens'",
            "'total_tokens'",
            "'cost_amount_minor'",
        ] as $needle) {
            self::assertStringContainsString($needle, $migration);
        }

        foreach (['input_payload', 'access_token', 'refresh_token', 'private_key', 'client_secret'] as $forbiddenColumn) {
            self::assertStringNotContainsString("\$table->{$forbiddenColumn}", $migration);
        }

        self::assertStringContainsString('ALLOWED_ROOT_KEYS', $minimizer);
        self::assertStringContainsString('FORBIDDEN_KEY_FRAGMENTS', $minimizer);
        self::assertStringContainsString("'customer'", $minimizer);
        self::assertStringContainsString("'payment'", $minimizer);
        self::assertStringContainsString("'private_admin'", $minimizer);
        self::assertStringContainsString("hash('sha256'", $minimizer);
        self::assertStringContainsString('lockForUpdate()', $manager);
        self::assertStringContainsString('AI review decisions are final.', $manager);
        self::assertStringContainsString('Only approved successful AI output may be marked published.', $manager);
    }
}
