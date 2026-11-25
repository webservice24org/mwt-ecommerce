<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Seo;

use Illuminate\Foundation\Http\FormRequest;

final class UnifiedSeoPerformanceDashboardRequest extends FormRequest
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
            'search_run' => ['nullable', 'uuid'],
            'analytics_run' => ['nullable', 'uuid'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'page_search' => ['nullable', 'string', 'max:500'],
            'pages_page' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ];
    }

    public function searchRunPublicId(): ?string
    {
        return $this->nullableTrimmedString('search_run');
    }

    public function analyticsRunPublicId(): ?string
    {
        return $this->nullableTrimmedString('analytics_run');
    }

    public function dateFrom(): ?string
    {
        return $this->nullableTrimmedString('from');
    }

    public function dateTo(): ?string
    {
        return $this->nullableTrimmedString('to');
    }

    public function pageSearch(): ?string
    {
        return $this->nullableTrimmedString('page_search');
    }

    public function pagesPage(): int
    {
        return max(1, $this->integer('pages_page', 1));
    }

    private function nullableTrimmedString(string $key): ?string
    {
        $value = trim($this->string($key)->toString());

        return $value === '' ? null : $value;
    }
}
