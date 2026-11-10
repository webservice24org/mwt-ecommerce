<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Orders;

use App\Domain\Shipping\Enums\ShippingProviderCode;
use App\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user(
            'admin',
        ) instanceof Admin;
    }

    protected function prepareForValidation(): void
    {
        $carrier =
            $this->input(
                'carrier',
            );

        $service =
            $this->input(
                'service',
            );

        $providerCode =
            $this->input(
                'provider_code',
            );

        $this->merge([
            /*
             * Backward-compatible default for older tests /
             * clients that pre-date 8.8C.
             */
            'provider_code' => is_string($providerCode)
                && trim($providerCode) !== ''
                ? strtolower(
                    trim(
                        $providerCode,
                    ),
                )
                : ShippingProviderCode::Manual
                    ->value,

            'carrier' => is_string($carrier)
                ? $this
                    ->nullableTrimmedString(
                        $carrier,
                    )
                : $carrier,

            'service' => is_string($service)
                ? $this
                    ->nullableTrimmedString(
                        $service,
                    )
                : $service,
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'shipping_method_id' => [
                'required',
                'integer',
                'exists:shipping_methods,id',
            ],

            'provider_code' => [
                'required',
                Rule::enum(
                    ShippingProviderCode::class,
                ),
            ],

            'carrier' => [
                'nullable',
                'string',
                'max:120',
            ],

            'service' => [
                'nullable',
                'string',
                'max:120',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.order_item_id' => [
                'required',
                'integer',
                'distinct',
                'exists:order_items,id',
            ],

            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],
        ];
    }

    public function shippingMethodId(): int
    {
        return (int) $this->validated(
            'shipping_method_id',
        );
    }

    public function providerCode(
    ): ShippingProviderCode {
        return ShippingProviderCode::from(
            (string) $this->validated(
                'provider_code',
            ),
        );
    }

    public function carrier(): ?string
    {
        return $this
            ->validatedNullableString(
                'carrier',
            );
    }

    public function service(): ?string
    {
        return $this
            ->validatedNullableString(
                'service',
            );
    }

    /**
     * @return list<array{
     *     order_item_id: int,
     *     quantity: int
     * }>
     */
    public function shipmentItems(): array
    {
        $items =
            $this->validated(
                'items',
            );

        if (! is_array($items)) {
            return [];
        }

        $result = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $result[] = [
                'order_item_id' => (int) (
                    $item[
                        'order_item_id'
                    ] ?? 0
                ),

                'quantity' => (int) (
                    $item[
                        'quantity'
                    ] ?? 0
                ),
            ];
        }

        return $result;
    }

    private function validatedNullableString(
        string $key,
    ): ?string {
        $value =
            $this->validated(
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

    private function nullableTrimmedString(
        string $value,
    ): ?string {
        $value =
            trim(
                $value,
            );

        return $value === ''
            ? null
            : $value;
    }
}
