<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Promotion;

use App\Domain\Promotion\Enums\DiscountType;
use App\Models\Promotion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdatePromotionRequest extends FormRequest
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
        /** @var Promotion $promotion */
        $promotion = $this->route('promotion');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique(
                    'promotions',
                    'slug',
                )->ignore($promotion->id),
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'discount_type' => [
                'required',
                Rule::enum(DiscountType::class),
            ],

            'discount_value' => [
                'required',
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
                if (
                    $this->input('discount_type') ===
                    DiscountType::Percentage->value &&
                    (int) $this->input('discount_value') > 10_000
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'discount_value',
                            'Percentage discount cannot exceed 100%.',
                        );
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('slug')) {
            $this->merge([
                'slug' => Str::slug(
                    (string) $this->input('slug'),
                ),
            ]);
        }
    }
}
