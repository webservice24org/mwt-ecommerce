<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Seo;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateAiCrawlerPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'allow_oai_search_bot' => [
                'required',
                'boolean',
            ],

            'allow_gpt_bot' => [
                'required',
                'boolean',
            ],

            'allow_google_extended' => [
                'required',
                'boolean',
            ],

            'version' => [
                'required',
                'integer',
                'min:1',
            ],
        ];
    }
}
