<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Seo\Audit\Enums\SeoAuditRule;
use App\Domain\Seo\Audit\Enums\SeoAuditRunStatus;
use App\Domain\Seo\Audit\Enums\SeoAuditSeverity;
use App\Domain\Seo\Audit\Enums\SeoAuditSubjectType;
use App\Models\Admin;
use App\Models\SeoAuditFinding;
use App\Models\SeoAuditRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class SeoAuditWorkspaceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_editor_can_view_audit_history_and_findings(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $run = $this->completedRun($admin);

        SeoAuditFinding::query()->create([
            'seo_audit_run_id' => $run->id,
            'fingerprint' => hash('sha256', 'missing-title'),
            'rule_key' => SeoAuditRule::IndexableMissingTitle,
            'severity' => SeoAuditSeverity::Error,
            'subject_type' => SeoAuditSubjectType::Url,
            'subject_key' => 'https://example.com/products/widget',
            'url' => 'https://example.com/products/widget',
            'source_url' => null,
            'message' => 'Indexable page is missing a title.',
            'evidence' => ['status' => 200],
            'remediation' => 'Add a unique title.',
        ]);

        $this
            ->actingAs($admin, 'admin')
            ->get(route('admin.seo.audits.index', [
                'run' => $run->public_id,
            ]))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Admin/Seo/Audit/Index')
                    ->where('workspace.selected_run.public_id', $run->public_id)
                    ->where('workspace.selected_run.findings_total', 1)
                    ->where('workspace.findings.0.rule', 'indexable_missing_title')
                    ->where('workspace.findings.0.severity', 'error')
                    ->where('workspace.can_start', true)
                    ->has('workspace.options.rules', 16)
                    ->has('workspace.options.severities', 4),
            );
    }

    public function test_guest_cannot_view_audit_workspace(): void
    {
        $this
            ->get(route('admin.seo.audits.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_invalid_finding_filter_is_rejected(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $this
            ->actingAs($admin, 'admin')
            ->get(route('admin.seo.audits.index', [
                'severity' => 'invented',
            ]))
            ->assertSessionHasErrors('severity');
    }

    private function completedRun(Admin $admin): SeoAuditRun
    {
        return SeoAuditRun::query()->create([
            'public_id' => Str::uuid()->toString(),
            'requested_by_admin_id' => $admin->id,
            'requested_by_name' => $admin->name,
            'requested_by_email' => $admin->email,
            'status' => SeoAuditRunStatus::Completed,
            'rules_version' => 'seo-audit-rules-v1',
            'orchestration_version' => 'seo-audit-orchestration-v1',
            'queue_passes' => 4,
            'urls_scanned' => 12,
            'findings_total' => 1,
            'critical_count' => 0,
            'error_count' => 1,
            'warning_count' => 0,
            'info_count' => 0,
            'requested_at' => now()->subMinute(),
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
            'catalog_seeded_at' => now()->subMinute(),
            'crawl_settled_at' => now()->subSeconds(45),
            'image_health_enriched_at' => now()->subSeconds(30),
            'evaluated_at' => now()->subSeconds(15),
            'last_progress_at' => now(),
        ]);
    }
}
