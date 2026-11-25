<?php

declare(strict_types=1);

namespace App\Domain\Seo\AiGovernance\Services;

use App\Domain\Seo\AiGovernance\Data\SeoAiUsageData;
use App\Domain\Seo\AiGovernance\Enums\SeoAiGovernancePurpose;
use App\Domain\Seo\AiGovernance\Support\SeoAiLegacyInputMapper;
use App\Models\Admin;
use App\Models\AiSeoRequest;
use App\Models\SeoAiGovernanceRun;
use App\Models\SeoAiLegacyRequestGovernanceLink;
use Illuminate\Support\Facades\DB;

final class SeoAiLegacyRequestGovernance
{
    public function __construct(
        private readonly SeoAiGovernanceManager $governance,
        private readonly SeoAiLegacyInputMapper $inputMapper,
    ) {}

    public function begin(
        AiSeoRequest $request,
        string $provider,
        string $model,
        string $promptVersion,
    ): SeoAiGovernanceRun {
        return DB::transaction(function () use (
            $request,
            $provider,
            $model,
            $promptVersion,
        ): SeoAiGovernanceRun {
            $lockedRequest = AiSeoRequest::query()
                ->whereKey($request->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $link = SeoAiLegacyRequestGovernanceLink::query()
                ->where('ai_seo_request_id', $lockedRequest->getKey())
                ->first();

            if ($link instanceof SeoAiLegacyRequestGovernanceLink) {
                $run = SeoAiGovernanceRun::query()
                    ->findOrFail($link->governance_run_id);

                return $this->governance->start($run);
            }

            /** @var array<string, mixed> $snapshot */
            $snapshot = $lockedRequest->input_snapshot;
            $safeInput = $this->inputMapper->map($snapshot);

            $subjectType = $this->stringValue($safeInput['entity_type'] ?? null)
                ?? 'ai_seo_request';

            $subjectKey = $this->stringValue($safeInput['entity_key'] ?? null)
                ?? (string) $lockedRequest->getKey();

            $run = $this->governance->request(
                purpose: SeoAiGovernancePurpose::SeoSuggestion,
                provider: $provider,
                model: $model,
                promptVersion: $promptVersion,
                input: $safeInput,
                subjectType: $subjectType,
                subjectKey: $subjectKey,
            );

            SeoAiLegacyRequestGovernanceLink::query()->create([
                'ai_seo_request_id' => $lockedRequest->getKey(),
                'governance_run_id' => $run->getKey(),
            ]);

            return $this->governance->start($run);
        });
    }

    /**
     * @param  array<string, mixed>  $validatedProviderPayload
     */
    public function succeed(
        SeoAiGovernanceRun $run,
        array $validatedProviderPayload,
        ?SeoAiUsageData $usage = null,
    ): SeoAiGovernanceRun {
        return $this->governance->succeed(
            run: $run,
            generatedOutput: [
                'suggestions' => $validatedProviderPayload,
            ],
            usage: $usage,
        );
    }

    public function fail(
        SeoAiGovernanceRun $run,
        string $failureCode,
        ?SeoAiUsageData $usage = null,
    ): SeoAiGovernanceRun {
        return $this->governance->fail(
            run: $run,
            failureCode: $failureCode,
            safeMessage: 'AI SEO generation failed.',
            usage: $usage,
        );
    }

    public function approve(
        AiSeoRequest $request,
        Admin $reviewer,
    ): SeoAiGovernanceRun {
        return $this->governance->approve(
            $this->linkedRun($request),
            $reviewer,
        );
    }

    public function reject(
        AiSeoRequest $request,
        Admin $reviewer,
    ): SeoAiGovernanceRun {
        return $this->governance->reject(
            $this->linkedRun($request),
            $reviewer,
        );
    }

    public function markPublished(
        AiSeoRequest $request,
        Admin $actor,
    ): SeoAiGovernanceRun {
        return $this->governance->markPublished(
            $this->linkedRun($request),
            $actor,
        );
    }

    private function linkedRun(
        AiSeoRequest $request,
    ): SeoAiGovernanceRun {
        return DB::transaction(function () use ($request): SeoAiGovernanceRun {
            $lockedRequest = AiSeoRequest::query()
                ->whereKey($request->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $link = SeoAiLegacyRequestGovernanceLink::query()
                ->where('ai_seo_request_id', $lockedRequest->getKey())
                ->lockForUpdate()
                ->first();

            if (! $link instanceof SeoAiLegacyRequestGovernanceLink) {
                throw new \LogicException(
                    'AI SEO request has no governance link. Generate the governed draft before review or publication.',
                );
            }

            return SeoAiGovernanceRun::query()
                ->whereKey($link->governance_run_id)
                ->lockForUpdate()
                ->firstOrFail();
        });
    }

    private function stringValue(mixed $value): ?string
    {
        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
