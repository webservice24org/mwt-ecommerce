<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Seo;

use App\Domain\Seo\AiGovernance\Enums\SeoAiApprovalStatus;
use App\Domain\Seo\AiGovernance\Enums\SeoAiGovernancePurpose;
use App\Domain\Seo\AiGovernance\Enums\SeoAiGovernanceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SeoAiGovernanceAuditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'run' => ['nullable', 'uuid'],
            'status' => ['nullable', Rule::enum(SeoAiGovernanceStatus::class)],
            'approval' => ['nullable', Rule::enum(SeoAiApprovalStatus::class)],
            'purpose' => ['nullable', Rule::enum(SeoAiGovernancePurpose::class)],
            'provider' => ['nullable', 'string', 'max:80'],
            'search' => ['nullable', 'string', 'max:160'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'page' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ];
    }

    public function runPublicId(): ?string
    {
        return $this->nullableTrimmedString('run');
    }

    public function status(): ?string
    {
        return $this->nullableTrimmedString('status');
    }

    public function approvalStatus(): ?string
    {
        return $this->nullableTrimmedString('approval');
    }

    public function purpose(): ?string
    {
        return $this->nullableTrimmedString('purpose');
    }

    public function provider(): ?string
    {
        return $this->nullableTrimmedString('provider');
    }

    public function search(): ?string
    {
        return $this->nullableTrimmedString('search');
    }

    public function dateFrom(): ?string
    {
        return $this->nullableTrimmedString('from');
    }

    public function dateTo(): ?string
    {
        return $this->nullableTrimmedString('to');
    }

    public function page(): int
    {
        return max(1, $this->integer('page', 1));
    }

    private function nullableTrimmedString(string $key): ?string
    {
        $value = trim($this->string($key)->toString());

        return $value === '' ? null : $value;
    }
}
