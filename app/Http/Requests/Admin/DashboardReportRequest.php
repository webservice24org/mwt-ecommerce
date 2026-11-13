<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\Reporting\Enums\ReportDatePreset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class DashboardReportRequest extends FormRequest
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
            'preset' => [
                'nullable',
                'string',
                Rule::enum(
                    ReportDatePreset::class,
                ),
            ],

            'from' => [
                'nullable',
                'date_format:Y-m-d',
                'required_if:preset,custom',
            ],

            'to' => [
                'nullable',
                'date_format:Y-m-d',
                'required_if:preset,custom',
                'after_or_equal:from',
            ],

            'timezone' => [
                'nullable',
                'string',
                'timezone',
            ],
        ];
    }

    public function preset(): ReportDatePreset
    {
        $value =
            $this->string(
                'preset',
            )
                ->trim()
                ->toString();

        if ($value === '') {
            return ReportDatePreset::ThisMonth;
        }

        return ReportDatePreset::from(
            $value,
        );
    }

    public function fromDate(): ?string
    {
        return $this->optionalString(
            'from',
        );
    }

    public function toDate(): ?string
    {
        return $this->optionalString(
            'to',
        );
    }

    public function timezone(): ?string
    {
        return $this->optionalString(
            'timezone',
        );
    }

    private function optionalString(
        string $key,
    ): ?string {
        $value =
            $this->string(
                $key,
            )
                ->trim()
                ->toString();

        return $value !== ''
            ? $value
            : null;
    }
}
