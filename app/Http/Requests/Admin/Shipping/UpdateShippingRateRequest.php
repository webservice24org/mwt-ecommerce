<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Shipping;

use App\Models\Admin;
use App\Models\ShippingRate;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class UpdateShippingRateRequest extends FormRequest
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

        $this->merge([
            'name' => $name,

            'code' => $code !== ''
                ? Str::slug($code)
                : Str::slug($name),

            'zone_ids' => $this->input(
                'zone_ids',
                [],
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var ShippingRate $rate */
        $rate = $this->route(
            'rate',
        );

        return [
            'shipping_method_id' => [
                'required',
                'integer',
                'exists:shipping_methods,id',
            ],

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
                    'shipping_rates',
                    'code',
                )
                    ->where(
                        fn (
                            Builder $query,
                        ): Builder => $query->where(
                            'shipping_method_id',
                            $this->integer(
                                'shipping_method_id',
                            ),
                        ),
                    )
                    ->ignore(
                        $rate,
                    ),
            ],

            'amount' => [
                'required',
                'integer',
                'min:0',
            ],

            'min_order_amount' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'max_order_amount' => [
                'nullable',
                'integer',
                'min:0',
                'gte:min_order_amount',
            ],

            'free_shipping_threshold' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],

            'priority' => [
                'required',
                'integer',
                'min:0',
                'max:1000000',
            ],

            'zone_ids' => [
                'array',
                'max:100',
            ],

            'zone_ids.*' => [
                'integer',
                'distinct',
                'exists:shipping_zones,id',
            ],
        ];
    }

    /**
     * @return list<int>
     */
    public function zoneIds(): array
    {
        return array_map(
            'intval',
            $this->validated(
                'zone_ids',
                [],
            ),
        );
    }
}
