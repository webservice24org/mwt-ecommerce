<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Queries;

use App\Domain\Seo\Audit\Enums\SeoAuditInternalLinkReviewState;
use App\Domain\Seo\Audit\Enums\SeoAuditRunStatus;
use App\Models\SeoAuditInternalLinkOpportunity;
use App\Models\SeoAuditRun;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final readonly class GetSeoAuditInternalLinkReviewWorkspaceQuery
{
    private const RUN_OPTIONS = 25;

    private const OPPORTUNITIES_PER_PAGE = 25;

    /**
     * @return array<string, mixed>
     */
    public function get(
        ?string $runPublicId,
        ?SeoAuditInternalLinkReviewState $state,
        ?string $search,
        int $page,
    ): array {
        $page = max(1, $page);

        $selectedRun = $runPublicId === null
            ? SeoAuditRun::query()
                ->where('status', SeoAuditRunStatus::Completed->value)
                ->latest('id')
                ->first()
            : SeoAuditRun::query()
                ->where('public_id', $runPublicId)
                ->where('status', SeoAuditRunStatus::Completed->value)
                ->firstOrFail();

        $runOptions = $this->runOptions();
        $rows = [];
        $total = 0;
        $lastPage = 1;
        $summary = [
            'total' => 0,
            'pending' => 0,
            'accepted' => 0,
            'dismissed' => 0,
        ];

        if ($selectedRun !== null) {
            $summary = $this->summary($selectedRun);

            $query = SeoAuditInternalLinkOpportunity::query()
                ->where(
                    'seo_audit_run_id',
                    $selectedRun->getKey(),
                );

            $this->applyFilters(
                query: $query,
                state: $state,
                search: $search,
            );

            $total = (clone $query)->count();
            $lastPage = max(
                1,
                (int) ceil($total / self::OPPORTUNITIES_PER_PAGE),
            );
            $page = min($page, $lastPage);

            $rows = $query
                ->orderByRaw(
                    "CASE review_state WHEN 'pending' THEN 1 WHEN 'accepted' THEN 2 ELSE 3 END",
                )
                ->orderByDesc('score')
                ->orderBy('id')
                ->skip(($page - 1) * self::OPPORTUNITIES_PER_PAGE)
                ->take(self::OPPORTUNITIES_PER_PAGE)
                ->get()
                ->map(
                    fn (SeoAuditInternalLinkOpportunity $opportunity): array => $this->row($opportunity),
                )
                ->values()
                ->all();
        }

        return [
            'runs' => $runOptions,
            'selected_run' => $selectedRun === null
                ? null
                : [
                    'public_id' => $selectedRun->public_id,
                    'requested_at' => $selectedRun->requested_at->toIso8601String(),
                    'completed_at' => $selectedRun->completed_at?->toIso8601String(),
                    'findings_total' => $selectedRun->findings_total,
                ],
            'summary' => $summary,
            'opportunities' => $rows,
            'pagination' => [
                'page' => $page,
                'per_page' => self::OPPORTUNITIES_PER_PAGE,
                'total' => $total,
                'last_page' => $lastPage,
            ],
            'filters' => [
                'run' => $runPublicId ?? '',
                'state' => $state === null ? '' : $state->value,
                'search' => $search ?? '',
            ],
            'options' => [
                'states' => array_map(
                    static fn (SeoAuditInternalLinkReviewState $item): array => [
                        'value' => $item->value,
                        'label' => ucfirst($item->value),
                    ],
                    SeoAuditInternalLinkReviewState::cases(),
                ),
            ],
            'can_review' => $selectedRun !== null,
        ];
    }

    /**
     * @return list<array{public_id:string,requested_at:string,completed_at:string|null,opportunities:int}>
     */
    private function runOptions(): array
    {
        $runs = SeoAuditRun::query()
            ->where('status', SeoAuditRunStatus::Completed->value)
            ->latest('id')
            ->limit(self::RUN_OPTIONS)
            ->get([
                'id',
                'public_id',
                'requested_at',
                'completed_at',
            ]);

        if ($runs->isEmpty()) {
            return [];
        }

        $counts = DB::table('seo_audit_internal_link_opportunities')
            ->whereIn('seo_audit_run_id', $runs->modelKeys())
            ->selectRaw('seo_audit_run_id, COUNT(*) AS aggregate_count')
            ->groupBy('seo_audit_run_id')
            ->pluck('aggregate_count', 'seo_audit_run_id')
            ->map(
                static fn (mixed $value): int => (int) $value,
            );

        return $runs
            ->map(
                static fn (SeoAuditRun $run): array => [
                    'public_id' => $run->public_id,
                    'requested_at' => $run->requested_at->toIso8601String(),
                    'completed_at' => $run->completed_at?->toIso8601String(),
                    'opportunities' => (int) $counts->get($run->getKey(), 0),
                ],
            )
            ->values()
            ->all();
    }

    /**
     * @return array{total:int,pending:int,accepted:int,dismissed:int}
     */
    private function summary(SeoAuditRun $run): array
    {
        $counts = SeoAuditInternalLinkOpportunity::query()
            ->where('seo_audit_run_id', $run->getKey())
            ->selectRaw('review_state, COUNT(*) AS aggregate_count')
            ->groupBy('review_state')
            ->pluck('aggregate_count', 'review_state')
            ->map(
                static fn (mixed $value): int => (int) $value,
            );

        return [
            'total' => (int) $counts->sum(),

            'accepted' => (int) $counts->get(
                SeoAuditInternalLinkReviewState::Accepted->value,
                0,
            ),
            'dismissed' => (int) $counts->get(
                SeoAuditInternalLinkReviewState::Dismissed->value,
                0,
            ),
        ];
    }

    /**
     * @param  Builder<SeoAuditInternalLinkOpportunity>  $query
     */
    private function applyFilters(
        Builder $query,
        ?SeoAuditInternalLinkReviewState $state,
        ?string $search,
    ): void {
        if ($state !== null) {
            $query->where('review_state', $state->value);
        }

        if ($search === null || $search === '') {
            return;
        }

        $like = '%'.addcslashes($search, '\\%_').'%';

        $query->where(
            static function (Builder $nested) use ($like): void {
                $nested
                    ->where('source_url', 'like', $like)
                    ->orWhere('target_url', 'like', $like)
                    ->orWhere('suggested_anchor', 'like', $like)
                    ->orWhere('target_subject_key', 'like', $like)
                    ->orWhere('reason', 'like', $like);
            },
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function row(
        SeoAuditInternalLinkOpportunity $opportunity,
    ): array {
        $reviewedAt = $opportunity->getAttribute('reviewed_at');

        return [
            'fingerprint' => $opportunity->fingerprint,
            'review_state' => $opportunity->review_state->value,
            'score' => $opportunity->score,
            'target_subject_type' => $opportunity->target_subject_type->value,
            'target_subject_key' => $opportunity->target_subject_key,
            'source_url' => $opportunity->source_url,
            'target_url' => $opportunity->target_url,
            'suggested_anchor' => $opportunity->suggested_anchor,
            'reason' => $opportunity->reason,
            'reviewed_by_name' => $this->nullableString(
                $opportunity->getAttribute('reviewed_by_name'),
            ),
            'reviewed_by_email' => $this->nullableString(
                $opportunity->getAttribute('reviewed_by_email'),
            ),
            'reviewed_at' => is_string($reviewedAt)
                ? CarbonImmutable::parse($reviewedAt)->toIso8601String()
                : null,
            'review_note' => $this->nullableString(
                $opportunity->getAttribute('review_note'),
            ),
            'created_at' => $opportunity->created_at?->toIso8601String(),
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== ''
            ? $value
            : null;
    }
}
