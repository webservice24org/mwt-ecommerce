<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Queries;

use App\Domain\Seo\Audit\Enums\SeoAuditRule;
use App\Domain\Seo\Audit\Enums\SeoAuditRunStatus;
use App\Domain\Seo\Audit\Enums\SeoAuditSeverity;
use App\Domain\Seo\Audit\Services\SeoAuditRuleRegistry;
use App\Models\SeoAuditFinding;
use App\Models\SeoAuditRun;
use Illuminate\Database\Eloquent\Builder;

final readonly class GetSeoAuditAdminWorkspaceQuery
{
    private const RUNS_PER_PAGE = 15;

    private const FINDINGS_PER_PAGE = 25;

    public function __construct(
        private SeoAuditRuleRegistry $rules,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function get(
        ?string $runPublicId,
        ?SeoAuditSeverity $severity,
        ?SeoAuditRule $rule,
        ?string $search,
        int $runsPage,
        int $findingsPage,
    ): array {
        $runsPage = max(1, $runsPage);
        $findingsPage = max(1, $findingsPage);

        $selectedRun = $runPublicId === null
            ? SeoAuditRun::query()->latest('id')->first()
            : SeoAuditRun::query()
                ->where('public_id', $runPublicId)
                ->firstOrFail();

        $runsTotal = SeoAuditRun::query()->count();
        $runsLastPage = max(
            1,
            (int) ceil($runsTotal / self::RUNS_PER_PAGE),
        );
        $runsPage = min($runsPage, $runsLastPage);

        $runs =
            SeoAuditRun::query()
                ->latest('id')
                ->skip(($runsPage - 1) * self::RUNS_PER_PAGE)
                ->take(self::RUNS_PER_PAGE)
                ->get()
                ->map(
                    fn (SeoAuditRun $run): array => $this->runRow($run),
                )
                ->values()
                ->all();

        $findingRows = [];
        $findingsTotal = 0;
        $findingsLastPage = 1;

        if ($selectedRun !== null) {
            $findingQuery =
                SeoAuditFinding::query()
                    ->where(
                        'seo_audit_run_id',
                        $selectedRun->getKey(),
                    );

            $this->applyFindingFilters(
                query: $findingQuery,
                severity: $severity,
                rule: $rule,
                search: $search,
            );

            $findingsTotal = (clone $findingQuery)->count();
            $findingsLastPage = max(
                1,
                (int) ceil(
                    $findingsTotal / self::FINDINGS_PER_PAGE,
                ),
            );
            $findingsPage = min(
                $findingsPage,
                $findingsLastPage,
            );

            $findingRows =
                $findingQuery
                    ->orderByRaw(
                        "CASE severity WHEN 'critical' THEN 1 WHEN 'error' THEN 2 WHEN 'warning' THEN 3 ELSE 4 END",
                    )
                    ->orderBy('rule_key')
                    ->orderBy('id')
                    ->skip(
                        ($findingsPage - 1) * self::FINDINGS_PER_PAGE,
                    )
                    ->take(self::FINDINGS_PER_PAGE)
                    ->get()
                    ->map(
                        fn (SeoAuditFinding $finding): array => $this->findingRow($finding),
                    )
                    ->values()
                    ->all();
        }

        $activeRun =
            SeoAuditRun::query()
                ->whereIn(
                    'status',
                    [
                        SeoAuditRunStatus::Requested->value,
                        SeoAuditRunStatus::Running->value,
                    ],
                )
                ->latest('id')
                ->first();

        return [
            'runs' => $runs,
            'runs_pagination' => [
                'page' => $runsPage,
                'per_page' => self::RUNS_PER_PAGE,
                'total' => $runsTotal,
                'last_page' => $runsLastPage,
            ],
            'selected_run' => $selectedRun === null
                ? null
                : $this->runRow($selectedRun),
            'findings' => $findingRows,
            'findings_pagination' => [
                'page' => $findingsPage,
                'per_page' => self::FINDINGS_PER_PAGE,
                'total' => $findingsTotal,
                'last_page' => $findingsLastPage,
            ],
            'filters' => [
                'run' => $runPublicId ?? '',
                'severity' => $severity === null ? '' : $severity->value,
                'rule' => $rule === null ? '' : $rule->value,
                'search' => $search ?? '',
            ],
            'options' => [
                'severities' => array_map(
                    static fn (SeoAuditSeverity $item): array => [
                        'value' => $item->value,
                        'label' => ucfirst($item->value),
                    ],
                    SeoAuditSeverity::cases(),
                ),
                'rules' => array_map(
                    fn (SeoAuditRule $item): array => [
                        'value' => $item->value,
                        'label' => $this->rules->label($item),
                    ],
                    SeoAuditRule::cases(),
                ),
            ],
            'active_run' => $activeRun === null
                ? null
                : [
                    'public_id' => $activeRun->public_id,
                    'status' => $activeRun->status->value,
                ],
            'can_start' => $activeRun === null,
        ];
    }

    /**
     * @param  Builder<SeoAuditFinding>  $query
     */
    private function applyFindingFilters(
        Builder $query,
        ?SeoAuditSeverity $severity,
        ?SeoAuditRule $rule,
        ?string $search,
    ): void {
        if ($severity !== null) {
            $query->where(
                'severity',
                $severity->value,
            );
        }

        if ($rule !== null) {
            $query->where(
                'rule_key',
                $rule->value,
            );
        }

        if ($search === null || $search === '') {
            return;
        }

        $like = '%'.addcslashes($search, '\\%_').'%';

        $query->where(
            static function (Builder $nested) use ($like): void {
                $nested
                    ->where('message', 'like', $like)
                    ->orWhere('url', 'like', $like)
                    ->orWhere('source_url', 'like', $like)
                    ->orWhere('subject_key', 'like', $like);
            },
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function runRow(SeoAuditRun $run): array
    {
        return [
            'public_id' => $run->public_id,
            'status' => $run->status->value,
            'rules_version' => $run->rules_version,
            'orchestration_version' => $run->orchestration_version,
            'queue_passes' => $run->queue_passes,
            'urls_scanned' => $run->urls_scanned,
            'findings_total' => $run->findings_total,
            'critical_count' => $run->critical_count,
            'error_count' => $run->error_count,
            'warning_count' => $run->warning_count,
            'info_count' => $run->info_count,
            'failure_code' => $run->failure_code,
            'failure_message' => $run->failure_message,
            'requested_by_name' => $run->requested_by_name,
            'requested_at' => $run->requested_at->toIso8601String(),
            'started_at' => $run->started_at?->toIso8601String(),
            'completed_at' => $run->completed_at?->toIso8601String(),
            'failed_at' => $run->failed_at?->toIso8601String(),
            'catalog_seeded_at' => $run->catalog_seeded_at?->toIso8601String(),
            'crawl_settled_at' => $run->crawl_settled_at?->toIso8601String(),
            'image_health_enriched_at' => $run->image_health_enriched_at?->toIso8601String(),
            'evaluated_at' => $run->evaluated_at?->toIso8601String(),
            'last_progress_at' => $run->last_progress_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function findingRow(SeoAuditFinding $finding): array
    {
        return [
            'fingerprint' => $finding->fingerprint,
            'rule' => $finding->rule_key->value,
            'rule_label' => $this->rules->label($finding->rule_key),
            'severity' => $finding->severity->value,
            'subject_type' => $finding->subject_type->value,
            'subject_key' => $finding->subject_key,
            'url' => $finding->url,
            'source_url' => $finding->source_url,
            'message' => $finding->message,
            'evidence' => $finding->evidence,
            'remediation' => $finding->remediation,
        ];
    }
}
