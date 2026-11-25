<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Seo;

use Illuminate\Foundation\Http\FormRequest;

final class SearchConsolePerformanceReportRequest extends FormRequest
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
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'query_search' => ['nullable', 'string', 'max:160'],
            'page_search' => ['nullable', 'string', 'max:500'],
            'runs_page' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'queries_page' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'pages_page' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ];
    }

    public function runPublicId(): ?string
    {
        return $this->nullableTrimmedString('run');
    }

    public function dateFrom(): ?string
    {
        return $this->nullableTrimmedString('from');
    }

    public function dateTo(): ?string
    {
        return $this->nullableTrimmedString('to');
    }

    public function querySearch(): ?string
    {
        return $this->nullableTrimmedString('query_search');
    }

    public function pageSearch(): ?string
    {
        return $this->nullableTrimmedString('page_search');
    }

    public function runsPage(): int
    {
        return max(1, $this->integer('runs_page', 1));
    }

    public function queriesPage(): int
    {
        return max(1, $this->integer('queries_page', 1));
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
