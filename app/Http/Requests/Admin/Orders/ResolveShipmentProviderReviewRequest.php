<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Orders;

use App\Domain\Shipping\Enums\ShipmentProviderReviewResolution;
use App\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ResolveShipmentProviderReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') instanceof Admin;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'resolution' => [
                'required',
                Rule::enum(ShipmentProviderReviewResolution::class),
            ],

            'external_reference' => [
                'nullable',
                'string',
                'max:190',
                'required_if:resolution,'.ShipmentProviderReviewResolution::ConfirmExisting->value,
            ],

            'note' => [
                'required',
                'string',
                'min:3',
                'max:500',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $reference = $this->input('external_reference');
        $note = $this->input('note');

        $this->merge([
            'external_reference' => is_string($reference)
                ? ($reference = trim($reference)) !== '' ? $reference : null
                : null,

            'note' => is_string($note)
                ? trim($note)
                : $note,
        ]);
    }

    public function resolution(): ShipmentProviderReviewResolution
    {
        return ShipmentProviderReviewResolution::from(
            (string) $this->validated('resolution'),
        );
    }

    public function externalReference(): ?string
    {
        $value = $this->validated('external_reference');

        return is_string($value) && $value !== ''
            ? $value
            : null;
    }

    public function note(): string
    {
        return (string) $this->validated('note');
    }
}
