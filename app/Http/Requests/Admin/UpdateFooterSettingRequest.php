<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\FooterBuilder\Enums\FooterTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateFooterSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_enabled' => $this->boolean(
                'is_enabled',
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'template' => [
                'required',
                Rule::enum(
                    FooterTemplate::class,
                ),
            ],

            'is_enabled' => [
                'required',
                'boolean',
            ],

            'config' => [
                'present',
                'array',
            ],
        ];
    }

    public function footerTemplate(): FooterTemplate
    {
        return FooterTemplate::from(
            $this
                ->string(
                    'template',
                )
                ->toString(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function footerConfig(): array
    {
        $config =
            $this->validated(
                'config',
            );

        if (
            ! is_array(
                $config,
            )
        ) {
            return [];
        }

        return $config;
    }

    public function footerEnabled(): bool
    {
        return $this->boolean(
            'is_enabled',
        );
    }
}
