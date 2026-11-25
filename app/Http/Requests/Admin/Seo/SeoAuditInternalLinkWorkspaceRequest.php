<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Seo;

use App\Domain\Seo\Audit\Enums\SeoAuditInternalLinkReviewState;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SeoAuditInternalLinkWorkspaceRequest extends FormRequest
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
            'state' => [
                'nullable',
                Rule::enum(SeoAuditInternalLinkReviewState::class),
            ],
            'search' => ['nullable', 'string', 'max:160'],
            'page' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ];
    }

    public function runPublicId(): ?string
    {
        $value = trim($this->string('run')->toString());

        return $value === '' ? null : $value;
    }

    public function state(): ?SeoAuditInternalLinkReviewState
    {
        return SeoAuditInternalLinkReviewState::tryFrom(
            $this->string('state')->toString(),
        );
    }

    public function search(): ?string
    {
        $value = trim($this->string('search')->toString());

        return $value === '' ? null : $value;
    }

    public function page(): int
    {
        return max(1, $this->integer('page', 1));
    }
}
