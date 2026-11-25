<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Services;

use App\Domain\Seo\Audit\Data\SeoAuditFindingData;
use App\Domain\Seo\Audit\Data\SeoAuditSummaryData;
use App\Domain\Seo\Audit\Enums\SeoAuditRunStatus;
use App\Domain\Seo\Audit\Enums\SeoAuditSeverity;
use App\Models\Admin;
use App\Models\SeoAuditFinding;
use App\Models\SeoAuditRun;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class SeoAuditRunLifecycle
{
    public function __construct(
        private SeoAuditFindingFingerprint $fingerprints,
        private SeoAuditRuleRegistry $rules,
    ) {}

    public function request(Admin $admin): SeoAuditRun
    {
        return SeoAuditRun::query()->create([
            'public_id' => Str::uuid()->toString(),
            'requested_by_admin_id' => $admin->getKey(),
            'requested_by_name' => $admin->name,
            'requested_by_email' => $admin->email,
            'status' => SeoAuditRunStatus::Requested,
            'rules_version' => SeoAuditRuleRegistry::VERSION,
            'requested_at' => now(),
        ]);
    }

    public function start(SeoAuditRun $run): SeoAuditRun
    {
        return DB::transaction(function () use ($run): SeoAuditRun {
            $current = $this->locked($run);

            if ($current->status === SeoAuditRunStatus::Running) {
                return $current;
            }

            if ($current->status !== SeoAuditRunStatus::Requested) {
                throw new DomainException('Only requested SEO audit runs may start.');
            }

            $current->forceFill([
                'status' => SeoAuditRunStatus::Running,
                'started_at' => now(),
                'failed_at' => null,
                'failure_code' => null,
                'failure_message' => null,
            ])->save();

            return $current;
        });
    }

    public function recordFinding(SeoAuditRun $run, SeoAuditFindingData $data): SeoAuditFinding
    {
        return DB::transaction(function () use ($run, $data): SeoAuditFinding {
            $current = $this->locked($run);

            if ($current->status !== SeoAuditRunStatus::Running) {
                throw new DomainException('SEO audit findings may only be recorded while a run is active.');
            }

            if ($data->severity !== $this->rules->defaultSeverity($data->rule)) {
                throw new DomainException('SEO audit finding severity must match the deterministic rule registry.');
            }

            $fingerprint = $this->fingerprints->make($data);

            return SeoAuditFinding::query()->updateOrCreate(
                [
                    'seo_audit_run_id' => $current->getKey(),
                    'fingerprint' => $fingerprint,
                ],
                [
                    'rule_key' => $data->rule,
                    'severity' => $data->severity,
                    'subject_type' => $data->subjectType,
                    'subject_key' => $data->subjectKey,
                    'url' => $data->url,
                    'source_url' => $data->sourceUrl,
                    'message' => $data->message,
                    'evidence' => $data->evidence,
                    'remediation' => $data->remediation,
                ],
            );
        });
    }

    public function complete(SeoAuditRun $run, int $urlsScanned): SeoAuditRun
    {
        if ($urlsScanned < 0) {
            throw new DomainException('SEO audit URL count cannot be negative.');
        }

        return DB::transaction(function () use ($run, $urlsScanned): SeoAuditRun {
            $current = $this->locked($run);

            if ($current->status === SeoAuditRunStatus::Completed) {
                return $current;
            }

            if ($current->status !== SeoAuditRunStatus::Running) {
                throw new DomainException('Only running SEO audit runs may complete.');
            }

            $summary = $this->summary($current);

            $current->forceFill([
                'status' => SeoAuditRunStatus::Completed,
                'urls_scanned' => $urlsScanned,
                'findings_total' => $summary->findingsTotal,
                'critical_count' => $summary->criticalCount,
                'error_count' => $summary->errorCount,
                'warning_count' => $summary->warningCount,
                'info_count' => $summary->infoCount,
                'completed_at' => now(),
                'failed_at' => null,
                'failure_code' => null,
                'failure_message' => null,
            ])->save();

            return $current;
        });
    }

    public function fail(SeoAuditRun $run, string $code, string $message): SeoAuditRun
    {
        return DB::transaction(function () use ($run, $code, $message): SeoAuditRun {
            $current = $this->locked($run);

            if ($current->status === SeoAuditRunStatus::Completed) {
                throw new DomainException('Completed SEO audit runs cannot fail.');
            }

            if ($current->status === SeoAuditRunStatus::Failed) {
                return $current;
            }

            $current->forceFill([
                'status' => SeoAuditRunStatus::Failed,
                'failure_code' => trim($code),
                'failure_message' => trim($message),
                'failed_at' => now(),
            ])->save();

            return $current;
        });
    }

    private function summary(SeoAuditRun $run): SeoAuditSummaryData
    {
        $counts = SeoAuditFinding::query()
            ->where('seo_audit_run_id', $run->getKey())
            ->selectRaw('severity, COUNT(*) as aggregate')
            ->groupBy('severity')
            ->pluck('aggregate', 'severity');

        return new SeoAuditSummaryData(
            findingsTotal: (int) $counts->sum(),
            criticalCount: (int) ($counts[SeoAuditSeverity::Critical->value] ?? 0),
            errorCount: (int) ($counts[SeoAuditSeverity::Error->value] ?? 0),
            warningCount: (int) ($counts[SeoAuditSeverity::Warning->value] ?? 0),
            infoCount: (int) ($counts[SeoAuditSeverity::Info->value] ?? 0),
        );
    }

    private function locked(SeoAuditRun $run): SeoAuditRun
    {
        return SeoAuditRun::query()->lockForUpdate()->findOrFail($run->getKey());
    }
}
