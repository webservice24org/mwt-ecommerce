<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\WebsiteSettings;

use App\Domain\HeaderBuilder\Enums\HeaderTemplate;
use App\Domain\HeaderBuilder\Exceptions\InvalidHeaderConfiguration;
use App\Domain\HeaderBuilder\Schemas\HeaderConfigSchema;
use App\Models\HeaderSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class UpdateHeaderBuilderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $admin =
            $this->user('admin');

        return $admin?->can(
            'updateAny',
            HeaderSetting::class,
        ) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'template' => [
                'required',
                'string',
                Rule::enum(HeaderTemplate::class),
            ],

            'config' => [
                'present',
                'array',
            ],

            'is_enabled' => [
                'required',
                'boolean',
            ],
        ];
    }

    /**
     * Return the selected header template.
     */
    public function headerTemplate(): HeaderTemplate
    {
        return HeaderTemplate::from(
            $this->string('template')->toString(),
        );
    }

    /**
     * Return a normalized, schema-validated
     * Header Builder configuration.
     *
     * @return array<string, mixed>
     */
    public function normalizedConfig(): array
    {
        /** @var array<string, mixed> $config */
        $config = $this->input(
            'config',
            [],
        );

        try {
            return (
                new HeaderConfigSchema
            )->validate(
                template: $this->headerTemplate(),
                config: $config,
            );
        } catch (
            InvalidHeaderConfiguration $exception
        ) {
            throw ValidationException::withMessages(
                $exception->errors,
            );
        }
    }

    public function enabled(): bool
    {
        return $this->boolean(
            'is_enabled',
        );
    }
}
