<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Seo;

use App\Domain\Seo\Audit\Enums\SeoAuditRule;
use App\Domain\Seo\Audit\Enums\SeoAuditSeverity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SeoAuditWorkspaceRequest extends FormRequest
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
            'severity' => ['nullable', Rule::enum(SeoAuditSeverity::class)],
            'rule' => ['nullable', Rule::enum(SeoAuditRule::class)],
            'search' => ['nullable', 'string', 'max:160'],
            'runs_page' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'findings_page' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ];
    }

    public function runPublicId(): ?string
    {
        $value = trim($this->string('run')->toString());

        return $value === '' ? null : $value;
    }

    public function severity(): ?SeoAuditSeverity
    {
        return SeoAuditSeverity::tryFrom(
            $this->string('severity')->toString(),
        );
    }

    public function rule(): ?SeoAuditRule
    {
        return SeoAuditRule::tryFrom(
            $this->string('rule')->toString(),
        );
    }

    public function search(): ?string
    {
        $value = trim($this->string('search')->toString());

        return $value === '' ? null : $value;
    }

    public function runsPage(): int
    {
        return max(1, $this->integer('runs_page', 1));
    }

    public function findingsPage(): int
    {
        return max(1, $this->integer('findings_page', 1));
    }
}
