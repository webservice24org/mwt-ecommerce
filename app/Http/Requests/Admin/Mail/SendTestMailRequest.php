<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Mail;

use App\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;

final class SendTestMailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user(
            'admin',
        ) instanceof Admin;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email:rfc',
                'max:254',
            ],
        ];
    }

    public function email(): string
    {
        return (string) $this->validated(
            'email',
        );
    }
}
