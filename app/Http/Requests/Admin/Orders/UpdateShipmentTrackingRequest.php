<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Orders;

use App\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateShipmentTrackingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user(
            'admin',
        ) instanceof Admin;
    }

    protected function prepareForValidation(): void
    {
        foreach (
            [
                'carrier',
                'service',
                'tracking_number',
                'tracking_url',
            ] as $key
        ) {
            $value = $this->input(
                $key,
            );

            if (! is_string($value)) {
                continue;
            }

            $value = trim(
                $value,
            );

            $this->merge([
                $key => $value === ''
                    ? null
                    : $value,
            ]);
        }
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'carrier' => [
                'nullable',
                'required_with:service,tracking_number,tracking_url',
                'string',
                'max:120',
            ],

            'service' => [
                'nullable',
                'string',
                'max:120',
            ],

            'tracking_number' => [
                'nullable',
                'string',
                'max:190',
            ],

            'tracking_url' => [
                'nullable',
                'url:http,https',
                'max:2048',
            ],
        ];
    }

    public function carrier(): ?string
    {
        return $this->validatedNullableString(
            'carrier',
        );
    }

    public function service(): ?string
    {
        return $this->validatedNullableString(
            'service',
        );
    }

    public function trackingNumber(): ?string
    {
        return $this->validatedNullableString(
            'tracking_number',
        );
    }

    public function trackingUrl(): ?string
    {
        return $this->validatedNullableString(
            'tracking_url',
        );
    }

    private function validatedNullableString(
        string $key,
    ): ?string {
        $value = $this->validated(
            $key,
        );

        if (
            ! is_string($value) ||
            $value === ''
        ) {
            return null;
        }

        return $value;
    }
}
