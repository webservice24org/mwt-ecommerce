<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'email',
                'max:254',
            ],
            'password' => [
                'required',
                'string',
            ],
        ];
    }

    /**
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (
            ! Auth::guard(
                'web',
            )->attempt(
                [
                    'email' => $this->string(
                        'email',
                    )->toString(),
                    'password' => $this->string(
                        'password',
                    )->toString(),
                ],
                $this->boolean(
                    'remember',
                ),
            )
        ) {
            $this->failAuthentication();
        }

        $user = Auth::guard(
            'web',
        )->user();

        /*
         * Keep account-status failures indistinguishable from
         * invalid credentials. A suspended/inactive customer
         * must never obtain an authenticated storefront session.
         */
        if (
            ! $user instanceof User
            || ! $user->isActive()
        ) {
            Auth::guard(
                'web',
            )->logout();

            $this->failAuthentication();
        }

        RateLimiter::clear(
            $this->throttleKey(),
        );
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (
            ! RateLimiter::tooManyAttempts(
                $this->throttleKey(),
                5,
            )
        ) {
            return;
        }

        event(
            new Lockout(
                $this,
            ),
        );

        $seconds = RateLimiter::availableIn(
            $this->throttleKey(),
        );

        throw ValidationException::withMessages([
            'email' => trans(
                'auth.throttle',
                [
                    'seconds' => $seconds,
                    'minutes' => ceil(
                        $seconds / 60,
                    ),
                ],
            ),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower(
                $this->string(
                    'email',
                )->toString(),
            )
            .'|'
            .$this->ip(),
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

    /**
     * @throws ValidationException
     */
    private function failAuthentication(): never
    {
        RateLimiter::hit(
            $this->throttleKey(),
        );

        throw ValidationException::withMessages([
            'email' => trans(
                'auth.failed',
            ),
        ]);
    }
}
