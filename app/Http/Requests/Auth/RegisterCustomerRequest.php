<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class RegisterCustomerRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:120',
            ],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email:rfc',
                'max:254',
                Rule::unique(
                    User::class,
                    'email',
                ),
            ],

            'password' => [
                'required',
                'string',
                'confirmed',
                'max:255',
                Password::min(10)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ];
    }

    public function customerName(): string
    {
        return (string) $this->validated(
            'name',
        );
    }

    public function customerEmail(): string
    {
        return (string) $this->validated(
            'email',
        );
    }

    public function customerPassword(): string
    {
        return (string) $this->validated(
            'password',
        );
    }

    protected function prepareForValidation(): void
    {
        $name = $this->input(
            'name',
        );

        $email = $this->input(
            'email',
        );

        $this->merge([
            'name' => is_string($name)
                ? trim($name)
                : $name,

            'email' => is_string($email)
                ? mb_strtolower(
                    trim($email),
                )
                : $email,
        ]);
    }
}
