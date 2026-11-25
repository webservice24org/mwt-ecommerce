<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Audit;

use App\Domain\Seo\Audit\Data\SeoAuditFindingData;
use App\Domain\Seo\Audit\Enums\SeoAuditRule;
use App\Domain\Seo\Audit\Enums\SeoAuditRunStatus;
use App\Domain\Seo\Audit\Enums\SeoAuditSeverity;
use App\Domain\Seo\Audit\Enums\SeoAuditSubjectType;
use App\Domain\Seo\Audit\Services\SeoAuditRunLifecycle;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SeoAuditRunLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_run_records_deduplicated_findings_and_completes_with_authoritative_counts(): void
    {
        $admin = Admin::factory()->create();
        $lifecycle = app(SeoAuditRunLifecycle::class);
        $run = $lifecycle->request($admin);

        self::assertSame(SeoAuditRunStatus::Requested, $run->status);
        self::assertNotSame('', $run->public_id);

        $run = $lifecycle->start($run);

        $finding = new SeoAuditFindingData(
            rule: SeoAuditRule::BrokenInternalLink,
            severity: SeoAuditSeverity::Error,
            subjectType: SeoAuditSubjectType::Link,
            subjectKey: 'footer:/missing',
            url: 'https://example.test/missing',
            sourceUrl: 'https://example.test/',
            message: 'Internal link returned a non-success response.',
            evidence: ['status' => 404, 'anchor' => 'Missing'],
            remediation: 'Update or remove the broken internal link.',
        );

        $lifecycle->recordFinding($run, $finding);
        $lifecycle->recordFinding($run, $finding);

        self::assertSame(1, $run->findings()->count());

        $run = $lifecycle->complete($run, 12)->refresh();

        self::assertSame(SeoAuditRunStatus::Completed, $run->status);
        self::assertSame(12, $run->urls_scanned);
        self::assertSame(1, $run->findings_total);
        self::assertSame(1, $run->error_count);
        self::assertSame(0, $run->warning_count);
    }

    public function test_finding_severity_cannot_override_deterministic_rule_registry(): void
    {
        $this->expectException(\DomainException::class);

        $admin = Admin::factory()->create();
        $lifecycle = app(SeoAuditRunLifecycle::class);
        $run = $lifecycle->start($lifecycle->request($admin));

        $lifecycle->recordFinding($run, new SeoAuditFindingData(
            rule: SeoAuditRule::MissingAlt,
            severity: SeoAuditSeverity::Critical,
            subjectType: SeoAuditSubjectType::Image,
            message: 'Missing alt text.',
        ));
    }
}
