<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Audit;

use App\Domain\Seo\Audit\Enums\SeoAuditRule;
use App\Domain\Seo\Audit\Enums\SeoAuditRunStatus;
use App\Domain\Seo\Audit\Enums\SeoAuditSeverity;
use App\Domain\Seo\Audit\Enums\SeoAuditSubjectType;
use App\Domain\Seo\Audit\Queries\GetSeoAuditAdminWorkspaceQuery;
use App\Models\Admin;
use App\Models\SeoAuditFinding;
use App\Models\SeoAuditRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class SeoAuditAdminWorkspaceQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_query_filters_findings_without_changing_persisted_run_summary(): void
    {
        $admin = Admin::factory()->create();
        $run = $this->run($admin);

        $this->finding(
            run: $run,
            rule: SeoAuditRule::BrokenInternalLink,
            severity: SeoAuditSeverity::Error,
            message: 'Broken product link.',
        );
        $this->finding(
            run: $run,
            rule: SeoAuditRule::MissingAlt,
            severity: SeoAuditSeverity::Warning,
            message: 'Image alt attribute is missing.',
        );

        /** @var array{selected_run: array{findings_total: int}, findings_pagination: array{total: int}, findings: list<array{rule: string}>} $workspace */
        $workspace = app(GetSeoAuditAdminWorkspaceQuery::class)->get(
            runPublicId: $run->public_id,
            severity: SeoAuditSeverity::Error,
            rule: null,
            search: 'product',
            runsPage: 1,
            findingsPage: 1,
        );

        self::assertSame(
            2,
            $workspace['selected_run']['findings_total'],
        );
        self::assertSame(
            1,
            $workspace['findings_pagination']['total'],
        );
        self::assertSame(
            'broken_internal_link',
            $workspace['findings'][0]['rule'],
        );
    }

    private function run(Admin $admin): SeoAuditRun
    {
        return SeoAuditRun::query()->create([
            'public_id' => Str::uuid()->toString(),
            'requested_by_admin_id' => $admin->id,
            'requested_by_name' => $admin->name,
            'requested_by_email' => $admin->email,
            'status' => SeoAuditRunStatus::Completed,
            'rules_version' => 'seo-audit-rules-v1',
            'urls_scanned' => 8,
            'findings_total' => 2,
            'critical_count' => 0,
            'error_count' => 1,
            'warning_count' => 1,
            'info_count' => 0,
            'requested_at' => now(),
            'completed_at' => now(),
        ]);
    }

    private function finding(
        SeoAuditRun $run,
        SeoAuditRule $rule,
        SeoAuditSeverity $severity,
        string $message,
    ): void {
        SeoAuditFinding::query()->create([
            'seo_audit_run_id' => $run->id,
            'fingerprint' => hash('sha256', $rule->value),
            'rule_key' => $rule,
            'severity' => $severity,
            'subject_type' => SeoAuditSubjectType::Url,
            'subject_key' => 'https://example.com/'.$rule->value,
            'url' => 'https://example.com/product/'.$rule->value,
            'source_url' => 'https://example.com/',
            'message' => $message,
            'evidence' => [],
            'remediation' => null,
        ]);
    }
}
