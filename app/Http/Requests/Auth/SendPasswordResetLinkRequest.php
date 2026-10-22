<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class SendPasswordResetLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:254',
            ],
        ];
    }

    public function customerEmail(): string
    {
        return (string) $this->validated(
            'email',
        );
    }

    protected function prepareForValidation(): void
    {
        $email = $this->input(
            'email',
        );

        $this->merge([
            'email' => is_string(
                $email,
            )
                ? mb_strtolower(
                    trim(
                        $email,
                    ),
                )
                : $email,
        ]);
    }
}
