<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Mail;

use App\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateMailSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user(
            'admin',
        ) instanceof Admin;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'host' => $this->nullableTrimmedString(
                'host',
            ),

            'username' => $this->nullableTrimmedString(
                'username',
            ),

            'encryption' => $this->nullableTrimmedString(
                'encryption',
            ),

            'from_address' => $this->nullableTrimmedString(
                'from_address',
            ),

            'from_name' => $this->nullableTrimmedString(
                'from_name',
            ),

            'admin_order_notification_email' => $this->nullableTrimmedString(
                'admin_order_notification_email',
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'enabled' => [
                'required',
                'boolean',
            ],

            'mailer' => [
                'required',
                'string',
                Rule::in([
                    'smtp',
                ]),
            ],

            'host' => [
                'nullable',
                'string',
                'max:255',
            ],

            'port' => [
                'nullable',
                'integer',
                'min:1',
                'max:65535',
            ],

            'username' => [
                'nullable',
                'string',
                'max:255',
            ],

            /*
             * Blank means:
             * keep the already stored secret.
             */
            'password' => [
                'nullable',
                'string',
                'max:4096',
            ],

            'encryption' => [
                'nullable',
                'string',
                Rule::in([
                    'tls',
                    'ssl',
                ]),
            ],

            'from_address' => [
                'nullable',
                'email:rfc',
                'max:254',
            ],

            'from_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'admin_order_notification_email' => [
                'nullable',
                'email:rfc',
                'max:254',
            ],
        ];
    }

    private function nullableTrimmedString(
        string $key,
    ): ?string {
        $value =
            $this->input(
                $key,
            );

        if (! is_string($value)) {
            return null;
        }

        $value =
            trim(
                $value,
            );

        return $value === ''
            ? null
            : $value;
    }
}
