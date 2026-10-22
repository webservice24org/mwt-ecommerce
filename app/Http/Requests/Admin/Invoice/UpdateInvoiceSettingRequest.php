<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Invoice;

use App\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateInvoiceSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') instanceof Admin;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'company_name' => trim((string) $this->input('company_name')),

            'company_tagline' => trim((string) $this->input('company_tagline')),

            'support_email' => $this->filled('support_email')
                ? trim((string) $this->input('support_email'))
                : null,

            'footer_credit' => trim((string) $this->input('footer_credit')),
        ]);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'company_name' => [
                'required',
                'string',
                'max:255',
            ],

            'company_tagline' => [
                'required',
                'string',
                'max:255',
            ],

            'logo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],

            'remove_logo' => [
                'sometimes',
                'boolean',
            ],

            'header_start_color' => [
                'required',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],

            'header_end_color' => [
                'required',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],

            'footer_color' => [
                'required',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],

            'support_email' => [
                'nullable',
                'email:rfc',
                'max:254',
            ],

            'footer_credit' => [
                'required',
                'string',
                'max:500',
            ],
        ];
    }
}
