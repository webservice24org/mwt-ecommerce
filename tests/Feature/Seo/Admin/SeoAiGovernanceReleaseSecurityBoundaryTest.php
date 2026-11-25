<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Seo\AiGovernance\Enums\SeoAiGovernancePurpose;
use App\Domain\Seo\AiGovernance\Queries\GetSeoAiGovernanceAuditWorkspaceQuery;
use App\Domain\Seo\AiGovernance\Services\SeoAiGovernanceManager;
use App\Domain\Seo\AiGovernance\Support\SeoAiInputMinimizer;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SeoAiGovernanceReleaseSecurityBoundaryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function governance_audit_workspace_requires_admin_authentication_and_public_uuid_selection(): void
    {
        $this
            ->get(route('admin.seo.ai-governance.index'))
            ->assertRedirect(route('admin.login'));

        $admin = Admin::factory()->create(['role' => AdminRole::Admin]);

        $this
            ->actingAs($admin, 'admin')
            ->get(route('admin.seo.ai-governance.index', ['run' => '1']))
            ->assertSessionHasErrors('run');
    }

    #[Test]
    public function raw_ai_input_values_are_not_searchable_from_the_audit_workspace(): void
    {
        $manager = app(SeoAiGovernanceManager::class);
        $marker = 'RAW-INPUT-NOT-AUDIT-SEARCHABLE-9F84';

        $run = $manager->request(
            purpose: SeoAiGovernancePurpose::SeoSuggestion,
            provider: 'provider-a',
            model: 'model-a',
            promptVersion: 'seo-closure-v1',
            input: [
                'entity_type' => 'product',
                'entity_key' => '42',
                'title' => $marker,
            ],
            subjectType: 'product',
            subjectKey: '42',
        );

        $workspace = app(GetSeoAiGovernanceAuditWorkspaceQuery::class)->get(
            runPublicId: null,
            status: null,
            approvalStatus: null,
            purpose: null,
            provider: null,
            search: $marker,
            dateFrom: null,
            dateTo: null,
            page: 1,
        );

        self::assertSame(0, $workspace['summary']['total']);
        self::assertSame([], $workspace['runs']);

        $detail = app(GetSeoAiGovernanceAuditWorkspaceQuery::class)->get(
            runPublicId: $run->public_id,
            status: null,
            approvalStatus: null,
            purpose: null,
            provider: null,
            search: null,
            dateFrom: null,
            dateTo: null,
            page: 1,
        );

        self::assertArrayNotHasKey('input_payload', $detail['selected_run']);
        self::assertArrayNotHasKey('raw_input', $detail['selected_run']);
        self::assertSame(['entity_key', 'entity_type', 'title'], $detail['selected_run']['input_field_names']);
    }

    #[Test]
    public function data_minimizer_rejects_customer_order_payment_and_private_admin_fields_recursively(): void
    {
        $minimizer = app(SeoAiInputMinimizer::class);

        foreach (['customer_name', 'order_id', 'payment_token', 'private_admin_note'] as $forbiddenKey) {
            try {
                $minimizer->minimize([
                    'title' => 'Safe public product title',
                    'attributes' => [
                        $forbiddenKey => 'must-not-leave-app',
                    ],
                ]);

                self::fail("Expected [{$forbiddenKey}] to be rejected by AI data minimization.");
            } catch (InvalidArgumentException $exception) {
                self::assertStringContainsString('forbidden', $exception->getMessage());
            }
        }
    }
}
