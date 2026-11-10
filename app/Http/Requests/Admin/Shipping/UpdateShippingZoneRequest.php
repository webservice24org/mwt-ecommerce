<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Shipping;

use App\Domain\Shipping\Enums\ShippingZoneRuleType;
use App\Models\Admin;
use App\Models\ShippingZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class UpdateShippingZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user(
            'admin',
        ) instanceof Admin;
    }

    protected function prepareForValidation(): void
    {
        $name = trim(
            (string) $this->input(
                'name',
                '',
            ),
        );

        $code = trim(
            (string) $this->input(
                'code',
                '',
            ),
        );

        $rules = $this->input(
            'rules',
            [],
        );

        if (! is_array($rules)) {
            $rules = [];
        }

        $normalizedRules = [];

        foreach ($rules as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $normalizedRules[] = [
                'type' => $rule['type'] ?? null,

                'value' => isset(
                    $rule['value'],
                )
                    ? trim(
                        (string) $rule['value'],
                    )
                    : '',
            ];
        }

        $this->merge([
            'name' => $name,

            'code' => $code !== ''
                ? Str::slug($code)
                : Str::slug($name),

            'rules' => $normalizedRules,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var ShippingZone $zone */
        $zone = $this->route(
            'zone',
        );

        return [
            'name' => [
                'required',
                'string',
                'max:120',
            ],

            'code' => [
                'required',
                'string',
                'max:80',

                Rule::unique(
                    'shipping_zones',
                    'code',
                )->ignore(
                    $zone,
                ),
            ],

            'is_active' => [
                'required',
                'boolean',
            ],

            'is_catch_all' => [
                'required',
                'boolean',
            ],

            'priority' => [
                'required',
                'integer',
                'min:0',
                'max:1000000',
            ],

            'rules' => [
                'array',
                'max:100',
            ],

            'rules.*.type' => [
                'required',
                Rule::enum(
                    ShippingZoneRuleType::class,
                ),
            ],

            'rules.*.value' => [
                'required',
                'string',
                'max:190',
            ],
        ];
    }

    /**
     * @return list<array{
     *     type: string,
     *     value: string
     * }>
     */
    public function zoneRules(): array
    {
        if (
            $this->boolean(
                'is_catch_all',
            )
        ) {
            return [];
        }

        /** @var list<array{type: string, value: string}> $rules */
        $rules = $this->validated(
            'rules',
            [],
        );

        return $rules;
    }
}
