<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\AiGovernance;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SeoAiGovernanceReleaseSourceContractTest extends TestCase
{
    #[Test]
    public function persistence_contract_keeps_raw_input_credentials_and_provider_bodies_out_of_governance_tables(): void
    {
        $migration = $this->source('database/migrations/2026_11_24_231500_create_seo_ai_governance_tables.php');
        $attemptMigration = $this->source('database/migrations/2026_11_25_103000_create_seo_ai_governance_attempts_table.php');

        foreach ([
            "'input_payload'",
            "'raw_input'",
            "'raw_response'",
            "'access_token'",
            "'refresh_token'",
            "'private_key'",
            "'client_secret'",
        ] as $forbiddenColumn) {
            self::assertStringNotContainsString($forbiddenColumn, $migration);
            self::assertStringNotContainsString($forbiddenColumn, $attemptMigration);
        }

        self::assertStringContainsString("->char('input_snapshot_hash', 64)", $migration);
        self::assertStringContainsString("->json('generated_output')", $migration);
        self::assertStringContainsString("['governance_run_id', 'attempt_number']", $attemptMigration);
        self::assertStringContainsString("'saiga_run_attempt_uidx'", $attemptMigration);
    }

    #[Test]
    public function generation_review_and_publication_contract_preserves_human_governance_boundaries(): void
    {
        $generation = $this->source('app/Domain/Seo/Ai/Services/AiSeoSuggestionGenerationService.php');
        $manager = $this->source('app/Domain/Seo/AiGovernance/Services/SeoAiGovernanceManager.php');
        $reviewController = $this->source('app/Http/Controllers/Admin/Seo/AiSeoSuggestionReviewController.php');
        $bulkApplication = $this->source('app/Domain/Seo/Ai/Bulk/Services/AiSeoBulkApplicationService.php');

        self::assertStringContainsString('SeoAiProviderUsageExtractor', $generation);
        self::assertStringContainsString('validatedProviderPayload: $result->payload', $generation);
        self::assertStringContainsString('rawResponse: $result->rawResponse', $generation);
        self::assertStringContainsString("safeMessage: 'AI SEO generation failed.'", $this->source('app/Domain/Seo/AiGovernance/Services/SeoAiLegacyRequestGovernance.php'));

        self::assertStringContainsString('SeoAiGovernanceStatus::Succeeded', $manager);
        self::assertStringContainsString('SeoAiApprovalStatus::Approved', $manager);
        self::assertStringContainsString('Only approved successful AI output may be marked published.', $manager);

        self::assertStringContainsString('SeoAiLegacySuggestionGovernance', $reviewController);
        self::assertStringContainsString('->approve(', $reviewController);
        self::assertStringContainsString('->reject(', $reviewController);
        self::assertStringNotContainsString('->markPublished(', $reviewController);

        self::assertStringContainsString('SeoAiLegacySuggestionGovernance', $bulkApplication);
        self::assertStringContainsString('->markPublished(', $bulkApplication);
    }

    #[Test]
    public function privacy_usage_and_audit_workspace_contract_remain_bounded_and_read_only(): void
    {
        $minimizer = $this->source('app/Domain/Seo/AiGovernance/Support/SeoAiInputMinimizer.php');
        $usageExtractor = $this->source('app/Domain/Seo/AiGovernance/Support/SeoAiProviderUsageExtractor.php');
        $query = $this->source('app/Domain/Seo/AiGovernance/Queries/GetSeoAiGovernanceAuditWorkspaceQuery.php');
        $controller = $this->source('app/Http/Controllers/Admin/Seo/SeoAiGovernanceAuditController.php');
        $page = $this->source('resources/js/Pages/Admin/Seo/AiGovernance/Index.tsx');

        foreach (['customer', 'order', 'payment', 'access_token', 'private_key', 'private_admin'] as $forbiddenFragment) {
            self::assertStringContainsString("'{$forbiddenFragment}'", $minimizer);
        }

        self::assertStringContainsString("\$root['_mwt_usage']", $usageExtractor);
        self::assertStringContainsString('Pricing is never inferred', $usageExtractor);
        self::assertStringContainsString('private const RUNS_PER_PAGE = 20;', $query);
        self::assertStringContainsString('private const ATTEMPT_LIMIT = 50;', $query);
        self::assertStringContainsString('private const EVENT_LIMIT = 100;', $query);
        self::assertStringContainsString('Read-only AI governance audit', $query);
        self::assertStringContainsString("\$this->authorize('viewAny', Product::class);", $controller);
        self::assertStringContainsString('Raw AI input is intentionally not stored', $page);
        self::assertStringNotContainsString('router.post(', $page);
        self::assertStringNotContainsString('router.put(', $page);
        self::assertStringNotContainsString('router.delete(', $page);
    }

    private function source(string $relativePath): string
    {
        $source = file_get_contents(base_path($relativePath));

        self::assertIsString($source, "Unable to read source file: {$relativePath}");

        return $source;
    }
}
