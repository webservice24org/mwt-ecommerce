<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Seo;

use App\Domain\Seo\Audit\Enums\SeoAuditInternalLinkReviewState;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ReviewSeoAuditInternalLinkOpportunityRequest extends FormRequest
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
            'action' => [
                'required',
                Rule::in([
                    SeoAuditInternalLinkReviewState::Accepted->value,
                    SeoAuditInternalLinkReviewState::Dismissed->value,
                ]),
            ],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function reviewState(): SeoAuditInternalLinkReviewState
    {
        return SeoAuditInternalLinkReviewState::from(
            $this->string('action')->toString(),
        );
    }

    public function note(): ?string
    {
        $value = trim($this->string('note')->toString());

        return $value === '' ? null : $value;
    }
}
