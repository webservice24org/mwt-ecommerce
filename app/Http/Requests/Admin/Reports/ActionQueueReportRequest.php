<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Reports;

use App\Domain\Reporting\Enums\ActionQueueSeverity;
use App\Domain\Reporting\Enums\ActionQueueSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ActionQueueReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'source' => [
                'sometimes',
                'array',
            ],

            'source.*' => [
                Rule::enum(
                    ActionQueueSource::class,
                ),
            ],

            'severity' => [
                'sometimes',
                'array',
            ],

            'severity.*' => [
                Rule::enum(
                    ActionQueueSeverity::class,
                ),
            ],

            'requires_action' => [
                'nullable',
                'boolean',
            ],

            'search' => [
                'nullable',
                'string',
                'max:150',
            ],

            'per_page' => [
                'nullable',
                'integer',
                Rule::in([
                    10,
                    25,
                    50,
                    100,
                ]),
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ];
    }
}
