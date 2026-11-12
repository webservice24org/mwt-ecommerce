<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Promotion;

use App\Domain\Promotion\Enums\DiscountType;
use App\Models\PromoCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdatePromoCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var PromoCode $promoCode */
        $promoCode =
            $this->route(
                'promo_code',
            );

        return [
            'promotion_id' => [
                'nullable',
                'integer',
                Rule::exists(
                    'promotions',
                    'id',
                ),
            ],

            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique(
                    'promo_codes',
                    'code',
                )->ignore(
                    $promoCode->id,
                ),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'discount_type' => [
                'nullable',
                Rule::enum(
                    DiscountType::class,
                ),
            ],

            'discount_value' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'maximum_discount' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'minimum_subtotal' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'usage_limit' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'usage_limit_per_customer' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'starts_at' => [
                'nullable',
                'date',
            ],

            'ends_at' => [
                'nullable',
                'date',
                'after_or_equal:starts_at',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],

            'is_stackable' => [
                'required',
                'boolean',
            ],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $hasPromotion =
                    $this->filled(
                        'promotion_id',
                    );

                $hasOwnDiscountType =
                    $this->filled(
                        'discount_type',
                    );

                $hasOwnDiscountValue =
                    $this->filled(
                        'discount_value',
                    );

                if (
                    ! $hasPromotion &&
                    (
                        ! $hasOwnDiscountType ||
                        ! $hasOwnDiscountValue
                    )
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'discount_type',
                            'A standalone promo code requires its own discount type and value.',
                        );
                }

                if (
                    $hasOwnDiscountType !==
                    $hasOwnDiscountValue
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'discount_value',
                            'Discount type and discount value must be provided together.',
                        );
                }

                if (
                    $this->input(
                        'discount_type',
                    ) ===
                    DiscountType::Percentage
                        ->value &&
                    (int) $this->input(
                        'discount_value',
                    ) >
                    10_000
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'discount_value',
                            'Percentage discount cannot exceed 100%.',
                        );
                }

                $globalLimit =
                    $this->integer(
                        'usage_limit',
                    );

                $customerLimit =
                    $this->integer(
                        'usage_limit_per_customer',
                    );

                if (
                    $globalLimit > 0 &&
                    $customerLimit >
                    $globalLimit
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'usage_limit_per_customer',
                            'Per-customer usage limit cannot exceed the global usage limit.',
                        );
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $payload = [];

        if ($this->has('code')) {
            $payload['code'] =
                mb_strtoupper(
                    trim(
                        (string) $this->input(
                            'code',
                        ),
                    ),
                );
        }

        if (
            $this->input(
                'discount_type',
            ) ===
            DiscountType::Fixed->value
        ) {
            $payload['discount_value'] =
                $this->moneyToMinor(
                    $this->input(
                        'discount_value',
                    ),
                );
        }

        $payload['maximum_discount'] =
            $this->moneyToMinor(
                $this->input(
                    'maximum_discount',
                ),
            );

        $payload['minimum_subtotal'] =
            $this->moneyToMinor(
                $this->input(
                    'minimum_subtotal',
                ),
            );

        $this->merge(
            $payload,
        );
    }

    private function moneyToMinor(
        mixed $value,
    ): mixed {
        if (
            $value === null ||
            $value === ''
        ) {
            return null;
        }

        $normalized =
            trim(
                (string) $value,
            );

        if (
            ! preg_match(
                '/^\d+(?:\.\d{1,2})?$/',
                $normalized,
            )
        ) {
            return $value;
        }

        [$whole, $fraction] =
            array_pad(
                explode(
                    '.',
                    $normalized,
                    2,
                ),
                2,
                '',
            );

        $fraction =
            str_pad(
                $fraction,
                2,
                '0',
            );

        return
            ((int) $whole * 100) +
            (int) substr(
                $fraction,
                0,
                2,
            );
    }
}
