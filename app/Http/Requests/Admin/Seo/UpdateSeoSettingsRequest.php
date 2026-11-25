<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Seo;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class UpdateSeoSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth(
            'admin',
        )->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'site_name' => [
                'nullable',
                'string',
                'max:190',
            ],

            'title_separator' => [
                'required',
                'string',
                'max:10',
            ],

            'default_title_template' => [
                'required',
                'string',
                'max:255',
            ],

            'default_meta_description' => [
                'nullable',
                'string',
                'max:500',
            ],

            'canonical_host' => [
                'nullable',
                'url',
                'max:255',
            ],

            'default_robots_index' => [
                'required',
                'boolean',
            ],

            'default_robots_follow' => [
                'required',
                'boolean',
            ],

            'default_og_image' => [
                'nullable',
                'url',
                'max:2048',
            ],

            'twitter_card' => [
                'required',
                'string',
                'in:summary,summary_large_image',
            ],

            'organization_name' => [
                'nullable',
                'string',
                'max:190',
            ],

            'organization_logo' => [
                'nullable',
                'url',
                'max:2048',
            ],

            'organization_social_profiles' => [
                'nullable',
                'array',
                'max:20',
            ],

            'organization_social_profiles.*' => [
                'required',
                'url',
                'max:2048',
            ],

            'google_site_verification' => [
                'nullable',
                'string',
                'max:255',
            ],

            'bing_site_verification' => [
                'nullable',
                'string',
                'max:255',
            ],

            'enable_structured_data' => [
                'required',
                'boolean',
            ],

            'enable_open_graph' => [
                'required',
                'boolean',
            ],

            'enable_twitter_cards' => [
                'required',
                'boolean',
            ],

            'enable_sitemap' => [
                'required',
                'boolean',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'site_name' => $this->nullableTrimmed(
                'site_name',
            ),

            'title_separator' => trim(
                (string) $this->input(
                    'title_separator',
                    '',
                ),
            ),

            'default_title_template' => trim(
                (string) $this->input(
                    'default_title_template',
                    '',
                ),
            ),

            'default_meta_description' => $this->nullableTrimmed(
                'default_meta_description',
            ),

            'canonical_host' => $this->nullableTrimmed(
                'canonical_host',
            ),

            'default_og_image' => $this->nullableTrimmed(
                'default_og_image',
            ),

            'twitter_card' => trim(
                (string) $this->input(
                    'twitter_card',
                    '',
                ),
            ),

            'organization_name' => $this->nullableTrimmed(
                'organization_name',
            ),

            'organization_logo' => $this->nullableTrimmed(
                'organization_logo',
            ),

            'organization_social_profiles' => $this->normalizedSocialProfiles(),

            'google_site_verification' => $this->nullableTrimmed(
                'google_site_verification',
            ),

            'bing_site_verification' => $this->nullableTrimmed(
                'bing_site_verification',
            ),
        ]);
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->validateCanonicalHost(
                    $validator,
                );

                $this->validateTitleTemplate(
                    $validator,
                );
            },
        ];
    }

    private function validateCanonicalHost(
        Validator $validator,
    ): void {
        $value =
            $this->input(
                'canonical_host',
            );

        if (
            ! is_string(
                $value,
            ) ||
            $value === ''
        ) {
            return;
        }

        $parts =
            parse_url(
                $value,
            );

        if (
            ! is_array(
                $parts,
            )
        ) {
            return;
        }

        $scheme =
            strtolower(
                (string) (
                    $parts['scheme'] ??
                    ''
                ),
            );

        if (
            ! in_array(
                $scheme,
                [
                    'http',
                    'https',
                ],
                true,
            )
        ) {
            $validator
                ->errors()
                ->add(
                    'canonical_host',
                    'The canonical host must use http or https.',
                );

            return;
        }

        if (
            isset(
                $parts['path'],
            ) &&
            $parts['path'] !== '' &&
            $parts['path'] !== '/'
        ) {
            $validator
                ->errors()
                ->add(
                    'canonical_host',
                    'The canonical host must not contain a path.',
                );
        }

        if (
            isset(
                $parts['query'],
            ) ||
            isset(
                $parts['fragment'],
            )
        ) {
            $validator
                ->errors()
                ->add(
                    'canonical_host',
                    'The canonical host must not contain a query string or fragment.',
                );
        }

        if (
            isset(
                $parts['user'],
            ) ||
            isset(
                $parts['pass'],
            )
        ) {
            $validator
                ->errors()
                ->add(
                    'canonical_host',
                    'The canonical host must not contain embedded credentials.',
                );
        }
    }

    private function validateTitleTemplate(
        Validator $validator,
    ): void {
        $template =
            $this->input(
                'default_title_template',
            );

        if (
            ! is_string(
                $template,
            ) ||
            $template === ''
        ) {
            return;
        }

        if (
            ! str_contains(
                $template,
                '{page_title}',
            )
        ) {
            $validator
                ->errors()
                ->add(
                    'default_title_template',
                    'The title template must contain {page_title}.',
                );
        }

        preg_match_all(
            '/\{[a-z_]+\}/',
            $template,
            $matches,
        );

        $tokens =
            array_values(
                array_unique(
                    $matches[0],
                ),
            );
        $allowed = [
            '{page_title}',
            '{separator}',
            '{site_name}',
        ];

        foreach (
            $tokens as $token
        ) {
            if (
                ! in_array(
                    $token,
                    $allowed,
                    true,
                )
            ) {
                $validator
                    ->errors()
                    ->add(
                        'default_title_template',
                        sprintf(
                            'Unsupported title template token [%s].',
                            $token,
                        ),
                    );
            }
        }
    }

    private function nullableTrimmed(
        string $key,
    ): ?string {
        $value =
            $this->input(
                $key,
            );

        if (
            ! is_string(
                $value,
            )
        ) {
            return null;
        }

        $value =
            trim(
                $value,
            );

        return $value !== ''
            ? $value
            : null;
    }

    /**
     * @return array<int, string>
     */
    private function normalizedSocialProfiles(): array
    {
        $profiles =
            $this->input(
                'organization_social_profiles',
                [],
            );

        if (
            ! is_array(
                $profiles,
            )
        ) {
            return [];
        }

        $normalized = [];

        foreach (
            $profiles as $profile
        ) {
            if (
                ! is_string(
                    $profile,
                )
            ) {
                continue;
            }

            $profile =
                trim(
                    $profile,
                );

            if (
                $profile === ''
            ) {
                continue;
            }

            if (
                ! in_array(
                    $profile,
                    $normalized,
                    true,
                )
            ) {
                $normalized[] =
                    $profile;
            }
        }

        return $normalized;
    }
}
