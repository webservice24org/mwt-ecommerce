<?php

declare(strict_types=1);

namespace App\Domain\Seo\AiGovernance\Queries;

use App\Domain\Seo\AiGovernance\Enums\SeoAiApprovalStatus;
use App\Domain\Seo\AiGovernance\Enums\SeoAiGovernancePurpose;
use App\Domain\Seo\AiGovernance\Enums\SeoAiGovernanceStatus;
use App\Models\SeoAiGovernanceAttempt;
use App\Models\SeoAiGovernanceEvent;
use App\Models\SeoAiGovernanceRun;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;

final class GetSeoAiGovernanceAuditWorkspaceQuery
{
    private const RUNS_PER_PAGE = 20;

    private const ATTEMPT_LIMIT = 50;

    private const EVENT_LIMIT = 100;

    /**
     * @return array<string, mixed>
     */
    public function get(
        ?string $runPublicId,
        ?string $status,
        ?string $approvalStatus,
        ?string $purpose,
        ?string $provider,
        ?string $search,
        ?string $dateFrom,
        ?string $dateTo,
        int $page,
    ): array {
        $page = max(1, $page);
        $filtered = $this->filteredRuns(
            status: $status,
            approvalStatus: $approvalStatus,
            purpose: $purpose,
            provider: $provider,
            search: $search,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
        );

        $total = (clone $filtered)->count();
        $lastPage = max(1, (int) ceil($total / self::RUNS_PER_PAGE));
        $page = min($page, $lastPage);

        $runs = (clone $filtered)
            ->latest('id')
            ->skip(($page - 1) * self::RUNS_PER_PAGE)
            ->take(self::RUNS_PER_PAGE)
            ->get()
            ->map(fn (SeoAiGovernanceRun $run): array => $this->runSummary($run))
            ->values()
            ->all();

        $selectedRun = $runPublicId === null
            ? (clone $filtered)->latest('id')->first()
            : SeoAiGovernanceRun::query()
                ->where('public_id', $runPublicId)
                ->firstOrFail();

        return [
            'summary' => $this->summary($filtered),
            'runs' => $runs,
            'pagination' => [
                'page' => $page,
                'per_page' => self::RUNS_PER_PAGE,
                'total' => $total,
                'last_page' => $lastPage,
            ],
            'selected_run' => $selectedRun === null ? null : $this->runDetail($selectedRun),
            'attempts' => $selectedRun === null ? [] : $this->attempts($selectedRun),
            'events' => $selectedRun === null ? [] : $this->events($selectedRun),
            'filters' => [
                'run' => $selectedRun === null ? '' : $selectedRun->public_id,
                'status' => $status ?? '',
                'approval' => $approvalStatus ?? '',
                'purpose' => $purpose ?? '',
                'provider' => $provider ?? '',
                'search' => $search ?? '',
                'from' => $dateFrom ?? '',
                'to' => $dateTo ?? '',
            ],
            'options' => [
                'statuses' => array_map(
                    static fn (SeoAiGovernanceStatus $case): string => $case->value,
                    SeoAiGovernanceStatus::cases(),
                ),
                'approval_statuses' => array_map(
                    static fn (SeoAiApprovalStatus $case): string => $case->value,
                    SeoAiApprovalStatus::cases(),
                ),
                'purposes' => array_map(
                    static fn (SeoAiGovernancePurpose $case): string => $case->value,
                    SeoAiGovernancePurpose::cases(),
                ),
                'providers' => $this->providers(),
            ],
            'limits' => [
                'attempts' => self::ATTEMPT_LIMIT,
                'events' => self::EVENT_LIMIT,
            ],
            'workspace_note' => 'Read-only AI governance audit. This workspace never approves, rejects, applies, publishes, retries, or re-runs AI output. Raw AI input and provider credentials are not stored by the governance ledger.',
        ];
    }

    /**
     * @return Builder<SeoAiGovernanceRun>
     */
    private function filteredRuns(
        ?string $status,
        ?string $approvalStatus,
        ?string $purpose,
        ?string $provider,
        ?string $search,
        ?string $dateFrom,
        ?string $dateTo,
    ): Builder {
        return SeoAiGovernanceRun::query()
            ->when(
                $status !== null,
                static fn (Builder $query): Builder => $query->where('status', $status),
            )
            ->when(
                $approvalStatus !== null,
                static fn (Builder $query): Builder => $query->where('approval_status', $approvalStatus),
            )
            ->when(
                $purpose !== null,
                static fn (Builder $query): Builder => $query->where('purpose', $purpose),
            )
            ->when(
                $provider !== null,
                static fn (Builder $query): Builder => $query->where('provider', $provider),
            )
            ->when(
                $dateFrom !== null,
                static fn (Builder $query): Builder => $query->whereDate('created_at', '>=', $dateFrom),
            )
            ->when(
                $dateTo !== null,
                static fn (Builder $query): Builder => $query->whereDate('created_at', '<=', $dateTo),
            )
            ->when(
                $search !== null,
                static function (Builder $query) use ($search): void {
                    $like = '%'.$search.'%';

                    $query->where(static function (Builder $nested) use ($like): void {
                        $nested
                            ->where('public_id', 'like', $like)
                            ->orWhere('subject_type', 'like', $like)
                            ->orWhere('subject_key', 'like', $like)
                            ->orWhere('provider', 'like', $like)
                            ->orWhere('model_name', 'like', $like)
                            ->orWhere('prompt_version', 'like', $like)
                            ->orWhere('requested_by_name', 'like', $like)
                            ->orWhere('reviewed_by_name', 'like', $like)
                            ->orWhere('failure_code', 'like', $like);
                    });
                },
            );
    }

    /**
     * @param  Builder<SeoAiGovernanceRun>  $filtered
     * @return array<string, int>
     */
    private function summary(Builder $filtered): array
    {
        return [
            'total' => (clone $filtered)->count(),
            'queued' => (clone $filtered)->where('status', SeoAiGovernanceStatus::Queued->value)->count(),
            'running' => (clone $filtered)->where('status', SeoAiGovernanceStatus::Running->value)->count(),
            'succeeded' => (clone $filtered)->where('status', SeoAiGovernanceStatus::Succeeded->value)->count(),
            'failed' => (clone $filtered)->where('status', SeoAiGovernanceStatus::Failed->value)->count(),
            'pending_review' => (clone $filtered)->where('approval_status', SeoAiApprovalStatus::PendingReview->value)->count(),
            'approved' => (clone $filtered)->where('approval_status', SeoAiApprovalStatus::Approved->value)->count(),
            'rejected' => (clone $filtered)->where('approval_status', SeoAiApprovalStatus::Rejected->value)->count(),
            'published' => (clone $filtered)->whereNotNull('published_at')->count(),
        ];
    }

    /**
     * @return list<string>
     */
    private function providers(): array
    {
        /** @var list<string> $providers */
        $providers = SeoAiGovernanceRun::query()
            ->whereNotNull('provider')
            ->distinct()
            ->orderBy('provider')
            ->limit(100)
            ->pluck('provider')
            ->filter(static fn (mixed $value): bool => is_string($value) && $value !== '')
            ->values()
            ->all();

        return $providers;
    }

    /**
     * @return array<string, mixed>
     */
    private function runSummary(SeoAiGovernanceRun $run): array
    {
        return [
            'public_id' => $run->public_id,
            'purpose' => $this->enumValue($run->getAttribute('purpose')),
            'subject_type' => $run->subject_type,
            'subject_key' => $run->subject_key,
            'provider' => $run->provider,
            'model_name' => $run->model_name,
            'prompt_version' => $run->prompt_version,
            'status' => $this->enumValue($run->getAttribute('status')),
            'approval_status' => $this->enumValue($run->getAttribute('approval_status')),
            'attempt_count' => $run->attempt_count,
            'total_tokens' => $run->total_tokens,
            'cost_amount_minor' => $run->cost_amount_minor,
            'cost_currency' => $run->cost_currency,
            'failure_code' => $run->failure_code,
            'requested_by_name' => $run->requested_by_name,
            'reviewed_by_name' => $run->reviewed_by_name,
            'published' => $run->published_at !== null,
            'created_at' => $this->isoDateTime($run->getAttribute('created_at')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function runDetail(SeoAiGovernanceRun $run): array
    {
        return [
            ...$this->runSummary($run),
            'input_snapshot_hash' => $run->input_snapshot_hash,
            'input_field_names' => $this->stringList($run->input_field_names),
            'generated_output' => $this->arrayValue($run->generated_output),
            'generated_at' => $this->isoDateTime($run->getAttribute('generated_at')),
            'requested_by_email' => $run->requested_by_email,
            'reviewed_by_email' => $run->reviewed_by_email,
            'reviewed_at' => $this->isoDateTime($run->getAttribute('reviewed_at')),
            'published_at' => $this->isoDateTime($run->getAttribute('published_at')),
            'input_tokens' => $run->input_tokens,
            'output_tokens' => $run->output_tokens,
            'failure_message' => $run->failure_message,
            'updated_at' => $this->isoDateTime($run->getAttribute('updated_at')),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function attempts(SeoAiGovernanceRun $run): array
    {
        $rows = [];

        foreach (SeoAiGovernanceAttempt::query()
            ->where('governance_run_id', $run->getKey())
            ->orderByDesc('attempt_number')
            ->limit(self::ATTEMPT_LIMIT)
            ->get() as $attempt) {
            $rows[] = [
                'attempt_number' => $attempt->attempt_number,
                'outcome' => $attempt->outcome,
                'input_tokens' => $attempt->input_tokens,
                'output_tokens' => $attempt->output_tokens,
                'total_tokens' => $attempt->total_tokens,
                'cost_amount_minor' => $attempt->cost_amount_minor,
                'cost_currency' => $attempt->cost_currency,
                'failure_code' => $attempt->failure_code,
                'recorded_at' => $this->isoDateTime($attempt->getAttribute('recorded_at')),
            ];
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function events(SeoAiGovernanceRun $run): array
    {
        $rows = [];

        foreach (SeoAiGovernanceEvent::query()
            ->where('governance_run_id', $run->getKey())
            ->latest('id')
            ->limit(self::EVENT_LIMIT)
            ->get() as $event) {
            $rows[] = [
                'event_type' => $this->enumValue($event->getAttribute('event_type')),
                'actor_name' => $event->actor_name,
                'actor_email' => $event->actor_email,
                'metadata' => $this->arrayValue($event->metadata),
                'occurred_at' => $this->isoDateTime($event->getAttribute('occurred_at')),
            ];
        }

        return $rows;
    }

    private function isoDateTime(mixed $value): ?string
    {
        if (! $value instanceof DateTimeInterface) {
            return null;
        }

        return $value->format(DATE_ATOM);
    }

    private function enumValue(mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        return is_string($value) ? $value : '';
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $result = [];

        foreach ($value as $item) {
            if (is_string($item)) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * @return array<string, mixed>|list<mixed>|null
     */
    private function arrayValue(mixed $value): ?array
    {
        return is_array($value) ? $value : null;
    }
}
