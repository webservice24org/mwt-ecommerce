<?php

declare(strict_types=1);

namespace App\Http\Requests\Frontend;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateProductReviewRequest extends FormRequest
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
        return [
            'rating' => [
                'required',
                'integer',
                'between:1,5',
            ],

            'title' => [
                'nullable',
                'string',
                'max:160',
            ],

            'body' => [
                'required',
                'string',
                'min:10',
                'max:5000',
            ],

            /*
             * These values may never be changed
             * from the storefront.
             */
            'product_id' => [
                'prohibited',
            ],

            'user_id' => [
                'prohibited',
            ],

            'verified_order_id' => [
                'prohibited',
            ],

            'is_verified_purchase' => [
                'prohibited',
            ],

            'status' => [
                'prohibited',
            ],

            'moderated_by' => [
                'prohibited',
            ],

            'moderation_note' => [
                'prohibited',
            ],

            'moderated_at' => [
                'prohibited',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $title =
            $this->input(
                'title',
            );

        $body =
            $this->input(
                'body',
            );

        $this->merge([
            'title' => is_string($title)
                ? (
                    trim($title) === ''
                    ? null
                    : trim($title)
                )
                : $title,

            'body' => is_string($body)
                ? trim($body)
                : $body,
        ]);
    }
}
