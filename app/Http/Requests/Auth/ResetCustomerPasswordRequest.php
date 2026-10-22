<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class ResetCustomerPasswordRequest extends FormRequest
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
            'token' => [
                'required',
                'string',
            ],

            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:254',
            ],

            'password' => [
                'required',
                'string',
                'confirmed',
                'max:255',
                Password::min(
                    10,
                )
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ];
    }

    /**
     * @return array{
     *     token: string,
     *     email: string,
     *     password: string,
     *     password_confirmation: string
     * }
     */
    public function resetCredentials(): array
    {
        /** @var array{
         *     token: string,
         *     email: string,
         *     password: string,
         *     password_confirmation: string
         * } $credentials
         */
        $credentials = $this->validated();

        return $credentials;
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
