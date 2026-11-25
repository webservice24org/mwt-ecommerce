<?php

declare(strict_types=1);

namespace App\Domain\Seo\AiGovernance\Services;

use App\Domain\Seo\AiGovernance\Data\SeoAiUsageData;
use App\Domain\Seo\AiGovernance\Enums\SeoAiApprovalStatus;
use App\Domain\Seo\AiGovernance\Enums\SeoAiGovernanceEventType;
use App\Domain\Seo\AiGovernance\Enums\SeoAiGovernancePurpose;
use App\Domain\Seo\AiGovernance\Enums\SeoAiGovernanceStatus;
use App\Domain\Seo\AiGovernance\Support\SeoAiInputMinimizer;
use App\Models\Admin;
use App\Models\SeoAiGovernanceAttempt;
use App\Models\SeoAiGovernanceEvent;
use App\Models\SeoAiGovernanceRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;

final class SeoAiGovernanceManager
{
    private const MAX_TOKEN_VALUE = 4_294_967_295;

    public function __construct(
        private readonly SeoAiInputMinimizer $inputMinimizer,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function request(
        SeoAiGovernancePurpose $purpose,
        string $provider,
        string $model,
        string $promptVersion,
        array $input,
        ?string $subjectType = null,
        ?string $subjectKey = null,
        ?Admin $requester = null,
    ): SeoAiGovernanceRun {
        $provider = $this->boundedRequired($provider, 80, 'AI provider');
        $model = $this->boundedRequired($model, 120, 'AI model');
        $promptVersion = $this->boundedRequired($promptVersion, 80, 'AI prompt version');
        $subjectType = $this->boundedNullable($subjectType, 40);
        $subjectKey = $this->boundedNullable($subjectKey, 190);
        $safeInput = $this->inputMinimizer->minimize($input);

        return DB::transaction(function () use (
            $purpose,
            $provider,
            $model,
            $promptVersion,
            $safeInput,
            $subjectType,
            $subjectKey,
            $requester,
        ): SeoAiGovernanceRun {
            $run = SeoAiGovernanceRun::query()->create([
                'public_id' => (string) Str::uuid(),
                'purpose' => $purpose,
                'subject_type' => $subjectType,
                'subject_key' => $subjectKey,
                'provider' => $provider,
                'model_name' => $model,
                'prompt_version' => $promptVersion,
                'input_snapshot_hash' => $safeInput->snapshotHash,
                'input_field_names' => $safeInput->fieldNames,
                'status' => SeoAiGovernanceStatus::Queued,
                'approval_status' => SeoAiApprovalStatus::PendingReview,
                'attempt_count' => 0,
                'requested_by_admin_id' => $requester?->getKey(),
                'requested_by_name' => $this->adminName($requester),
                'requested_by_email' => $this->adminEmail($requester),
            ]);

            $this->event($run, SeoAiGovernanceEventType::Requested, $requester, [
                'purpose' => $purpose->value,
                'prompt_version' => $promptVersion,
                'input_snapshot_hash' => $safeInput->snapshotHash,
            ]);

            return $run->fresh() ?? $run;
        });
    }

    public function start(SeoAiGovernanceRun $run): SeoAiGovernanceRun
    {
        return DB::transaction(function () use ($run): SeoAiGovernanceRun {
            $locked = $this->lock($run);
            $status = $this->status($locked);

            if (! in_array($status, [SeoAiGovernanceStatus::Queued, SeoAiGovernanceStatus::Failed], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Only queued or failed AI governance runs may start.',
                ]);
            }

            $locked->forceFill([
                'status' => SeoAiGovernanceStatus::Running,
                'attempt_count' => $locked->attempt_count + 1,
                'failure_code' => null,
                'failure_message' => null,
            ])->save();

            $this->event($locked, SeoAiGovernanceEventType::Started, null, [
                'attempt_count' => $locked->attempt_count,
            ]);

            return $locked->fresh() ?? $locked;
        });
    }

    /**
     * @param  array<string, mixed>  $generatedOutput
     */
    public function succeed(
        SeoAiGovernanceRun $run,
        array $generatedOutput,
        ?SeoAiUsageData $usage = null,
    ): SeoAiGovernanceRun {
        if ($generatedOutput === []) {
            throw ValidationException::withMessages([
                'generated_output' => 'Generated AI output cannot be empty.',
            ]);
        }

        return DB::transaction(function () use ($run, $generatedOutput, $usage): SeoAiGovernanceRun {
            $locked = $this->lock($run);

            if ($this->status($locked) !== SeoAiGovernanceStatus::Running) {
                throw ValidationException::withMessages([
                    'status' => 'Only a running AI governance run may succeed.',
                ]);
            }

            $this->recordAttempt(
                run: $locked,
                outcome: 'succeeded',
                usage: $usage,
                failureCode: null,
            );

            $locked->forceFill([
                'status' => SeoAiGovernanceStatus::Succeeded,
                'generated_output' => $generatedOutput,
                'generated_at' => now(),
                ...$this->aggregateUsage($locked),
                'failure_code' => null,
                'failure_message' => null,
            ])->save();

            $this->event($locked, SeoAiGovernanceEventType::Succeeded, null, [
                'attempt_count' => $locked->attempt_count,
                ...$this->usageMetadata($usage),
            ]);

            return $locked->fresh() ?? $locked;
        });
    }

    public function fail(
        SeoAiGovernanceRun $run,
        string $failureCode,
        string $safeMessage = 'AI generation failed.',
        ?SeoAiUsageData $usage = null,
    ): SeoAiGovernanceRun {
        $failureCode = $this->failureCode($failureCode);
        $safeMessage = $this->safeFailureMessage($safeMessage);

        return DB::transaction(function () use ($run, $failureCode, $safeMessage, $usage): SeoAiGovernanceRun {
            $locked = $this->lock($run);

            if ($this->status($locked) !== SeoAiGovernanceStatus::Running) {
                throw ValidationException::withMessages([
                    'status' => 'Only a running AI governance run may fail.',
                ]);
            }

            $this->recordAttempt(
                run: $locked,
                outcome: 'failed',
                usage: $usage,
                failureCode: $failureCode,
            );

            $locked->forceFill([
                'status' => SeoAiGovernanceStatus::Failed,
                ...$this->aggregateUsage($locked),
                'failure_code' => $failureCode,
                'failure_message' => $safeMessage,
            ])->save();

            $this->event($locked, SeoAiGovernanceEventType::Failed, null, [
                'attempt_count' => $locked->attempt_count,
                'failure_code' => $failureCode,
                ...$this->usageMetadata($usage),
            ]);

            return $locked->fresh() ?? $locked;
        });
    }

    public function approve(SeoAiGovernanceRun $run, Admin $reviewer): SeoAiGovernanceRun
    {
        return $this->review($run, $reviewer, SeoAiApprovalStatus::Approved, SeoAiGovernanceEventType::Approved);
    }

    public function reject(SeoAiGovernanceRun $run, Admin $reviewer): SeoAiGovernanceRun
    {
        return $this->review($run, $reviewer, SeoAiApprovalStatus::Rejected, SeoAiGovernanceEventType::Rejected);
    }

    public function markPublished(SeoAiGovernanceRun $run, Admin $actor): SeoAiGovernanceRun
    {
        return DB::transaction(function () use ($run, $actor): SeoAiGovernanceRun {
            $locked = $this->lock($run);

            if (
                $this->status($locked) !== SeoAiGovernanceStatus::Succeeded
                || $this->approvalStatus($locked) !== SeoAiApprovalStatus::Approved
            ) {
                throw ValidationException::withMessages([
                    'approval_status' => 'Only approved successful AI output may be marked published.',
                ]);
            }

            if ($locked->published_at === null) {
                $locked->forceFill(['published_at' => now()])->save();
                $this->event($locked, SeoAiGovernanceEventType::Published, $actor, []);
            }

            return $locked->fresh() ?? $locked;
        });
    }

    private function review(
        SeoAiGovernanceRun $run,
        Admin $reviewer,
        SeoAiApprovalStatus $decision,
        SeoAiGovernanceEventType $eventType,
    ): SeoAiGovernanceRun {
        return DB::transaction(function () use ($run, $reviewer, $decision, $eventType): SeoAiGovernanceRun {
            $locked = $this->lock($run);

            if ($this->status($locked) !== SeoAiGovernanceStatus::Succeeded) {
                throw ValidationException::withMessages([
                    'status' => 'Only successful AI output may be reviewed.',
                ]);
            }

            $approvalStatus = $this->approvalStatus($locked);

            if ($approvalStatus !== SeoAiApprovalStatus::PendingReview) {
                if ($approvalStatus === $decision) {
                    return $locked;
                }

                throw ValidationException::withMessages([
                    'approval_status' => 'AI review decisions are final.',
                ]);
            }

            $locked->forceFill([
                'approval_status' => $decision,
                'reviewed_by_admin_id' => $reviewer->getKey(),
                'reviewed_by_name' => $this->adminName($reviewer),
                'reviewed_by_email' => $this->adminEmail($reviewer),
                'reviewed_at' => now(),
            ])->save();

            $this->event($locked, $eventType, $reviewer, []);

            return $locked->fresh() ?? $locked;
        });
    }

    private function recordAttempt(
        SeoAiGovernanceRun $run,
        string $outcome,
        ?SeoAiUsageData $usage,
        ?string $failureCode,
    ): void {
        $attemptNumber = $run->getAttribute('attempt_count');

        if (! is_int($attemptNumber) || $attemptNumber < 1) {
            throw new LogicException('AI governance attempt counter is invalid.');
        }

        SeoAiGovernanceAttempt::query()->create([
            'governance_run_id' => $run->getKey(),
            'attempt_number' => $attemptNumber,
            'outcome' => $outcome,
            'input_tokens' => $usage?->inputTokens,
            'output_tokens' => $usage?->outputTokens,
            'total_tokens' => $usage?->totalTokens,
            'cost_amount_minor' => $usage?->costAmountMinor,
            'cost_currency' => $usage?->costCurrency,
            'failure_code' => $failureCode,
            'recorded_at' => now(),
        ]);
    }

    /**
     * Run-level values are the sum of provider-reported values across terminal
     * attempts. Unknown values are not estimated. Mixed reported currencies
     * intentionally produce a null aggregate cost while per-attempt rows retain
     * the original reported amounts.
     *
     * @return array{
     *   input_tokens: ?int,
     *   output_tokens: ?int,
     *   total_tokens: ?int,
     *   cost_amount_minor: ?int,
     *   cost_currency: ?string
     * }
     */
    private function aggregateUsage(SeoAiGovernanceRun $run): array
    {
        $attempts = SeoAiGovernanceAttempt::query()
            ->where('governance_run_id', $run->getKey())
            ->orderBy('attempt_number')
            ->get();

        $inputTokens = null;
        $outputTokens = null;
        $totalTokens = null;
        $costAmountMinor = null;
        $costCurrency = null;
        $mixedCostCurrency = false;

        foreach ($attempts as $attempt) {
            $inputTokens = $this->addReportedToken($inputTokens, $this->integerAttribute($attempt, 'input_tokens'));
            $outputTokens = $this->addReportedToken($outputTokens, $this->integerAttribute($attempt, 'output_tokens'));
            $totalTokens = $this->addReportedToken($totalTokens, $this->integerAttribute($attempt, 'total_tokens'));

            $attemptCost = $this->integerAttribute($attempt, 'cost_amount_minor');
            $attemptCurrency = $this->stringAttribute($attempt, 'cost_currency');

            if ($attemptCost === null || $attemptCurrency === null) {
                continue;
            }

            if ($costCurrency !== null && $costCurrency !== $attemptCurrency) {
                $mixedCostCurrency = true;

                continue;
            }

            $costCurrency = $attemptCurrency;
            $costAmountMinor = $this->safeAdd($costAmountMinor, $attemptCost, PHP_INT_MAX);
        }

        if ($mixedCostCurrency) {
            $costAmountMinor = null;
            $costCurrency = null;
        }

        return [
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'total_tokens' => $totalTokens,
            'cost_amount_minor' => $costAmountMinor,
            'cost_currency' => $costCurrency,
        ];
    }

    private function addReportedToken(?int $current, ?int $next): ?int
    {
        return $this->safeAdd($current, $next, self::MAX_TOKEN_VALUE);
    }

    private function safeAdd(?int $current, ?int $next, int $max): ?int
    {
        if ($next === null) {
            return $current;
        }

        if ($current === null) {
            return $next <= $max ? $next : null;
        }

        if ($current > $max - $next) {
            return null;
        }

        return $current + $next;
    }

    /** @return array<string, int|string|null> */
    private function usageMetadata(?SeoAiUsageData $usage): array
    {
        if ($usage === null) {
            return [];
        }

        return [
            'input_tokens' => $usage->inputTokens,
            'output_tokens' => $usage->outputTokens,
            'total_tokens' => $usage->totalTokens,
            'cost_amount_minor' => $usage->costAmountMinor,
            'cost_currency' => $usage->costCurrency,
        ];
    }

    private function integerAttribute(SeoAiGovernanceAttempt $attempt, string $key): ?int
    {
        $value = $attempt->getAttribute($key);

        return is_int($value) ? $value : null;
    }

    private function stringAttribute(SeoAiGovernanceAttempt $attempt, string $key): ?string
    {
        $value = $attempt->getAttribute($key);

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function status(SeoAiGovernanceRun $run): SeoAiGovernanceStatus
    {
        $status = $run->getAttribute('status');

        if (! $status instanceof SeoAiGovernanceStatus) {
            throw new LogicException('AI governance status cast is invalid.');
        }

        return $status;
    }

    private function approvalStatus(SeoAiGovernanceRun $run): SeoAiApprovalStatus
    {
        $approvalStatus = $run->getAttribute('approval_status');

        if (! $approvalStatus instanceof SeoAiApprovalStatus) {
            throw new LogicException('AI governance approval status cast is invalid.');
        }

        return $approvalStatus;
    }

    private function lock(SeoAiGovernanceRun $run): SeoAiGovernanceRun
    {
        return SeoAiGovernanceRun::query()
            ->whereKey($run->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    /** @param array<string, mixed> $metadata */
    private function event(
        SeoAiGovernanceRun $run,
        SeoAiGovernanceEventType $type,
        ?Admin $actor,
        array $metadata,
    ): void {
        SeoAiGovernanceEvent::query()->create([
            'governance_run_id' => $run->getKey(),
            'event_type' => $type,
            'actor_admin_id' => $actor?->getKey(),
            'actor_name' => $this->adminName($actor),
            'actor_email' => $this->adminEmail($actor),
            'metadata' => $metadata === [] ? null : $metadata,
            'occurred_at' => now(),
        ]);
    }

    private function adminName(?Admin $admin): ?string
    {
        if ($admin === null) {
            return null;
        }

        $value = $admin->getAttribute('name');

        return is_string($value) && $value !== '' ? mb_substr($value, 0, 120) : null;
    }

    private function adminEmail(?Admin $admin): ?string
    {
        if ($admin === null) {
            return null;
        }

        $value = $admin->getAttribute('email');

        return is_string($value) && $value !== '' ? mb_substr($value, 0, 190) : null;
    }

    private function boundedRequired(string $value, int $max, string $label): string
    {
        $value = trim($value);

        if ($value === '' || mb_strlen($value) > $max) {
            throw ValidationException::withMessages([
                'ai' => "{$label} is required and must be at most {$max} characters.",
            ]);
        }

        return $value;
    }

    private function boundedNullable(?string $value, int $max): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (mb_strlen($value) > $max) {
            throw ValidationException::withMessages(['ai' => 'AI governance subject metadata is too long.']);
        }

        return $value;
    }

    private function failureCode(string $value): string
    {
        $value = trim($value);

        if (preg_match('/^[a-z0-9][a-z0-9._-]{0,79}$/', $value) !== 1) {
            throw ValidationException::withMessages([
                'failure_code' => 'AI failure code must be a bounded machine-safe identifier.',
            ]);
        }

        return $value;
    }

    private function safeFailureMessage(string $value): string
    {
        $value = trim(preg_replace('/[\x00-\x1F\x7F]/u', ' ', $value) ?? '');
        $lower = strtolower($value);

        foreach (['private_key', 'access_token', 'refresh_token', 'authorization:', 'bearer ', 'password=', 'secret='] as $needle) {
            if (str_contains($lower, $needle)) {
                return 'AI generation failed.';
            }
        }

        return $value === '' ? 'AI generation failed.' : mb_substr($value, 0, 255);
    }
}
