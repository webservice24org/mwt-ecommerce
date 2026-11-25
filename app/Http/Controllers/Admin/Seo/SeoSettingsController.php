<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seo;

use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Seo\UpdateSeoSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class SeoSettingsController extends Controller
{
    public function edit(
        SeoSettingsService $settings,
    ): Response {
        return Inertia::render(
            'Admin/Seo/Settings',
            [
                'settings' => $settings
                    ->get()
                    ->toArray(),

                'options' => [
                    'twitter_cards' => [
                        [
                            'value' => 'summary',

                            'label' => 'Summary',
                        ],
                        [
                            'value' => 'summary_large_image',

                            'label' => 'Summary Large Image',
                        ],
                    ],

                    'title_tokens' => [
                        '{page_title}',
                        '{separator}',
                        '{site_name}',
                    ],
                ],
            ],
        );
    }

    public function update(
        UpdateSeoSettingsRequest $request,
        SeoSettingsService $settings,
    ): RedirectResponse {
        $validated =
            $request->validated();

        $settings->save(
            new SeoSettingsData(
                siteName: $this->nullableString(
                    $validated,
                    'site_name',
                ),

                titleSeparator: (string) $validated[
                    'title_separator'
                ],

                defaultTitleTemplate: (string) $validated[
                    'default_title_template'
                ],

                defaultMetaDescription: $this->nullableString(
                    $validated,
                    'default_meta_description',
                ),

                canonicalHost: $this->nullableString(
                    $validated,
                    'canonical_host',
                ),

                defaultRobotsIndex: (bool) $validated[
                    'default_robots_index'
                ],

                defaultRobotsFollow: (bool) $validated[
                    'default_robots_follow'
                ],

                defaultOgImage: $this->nullableString(
                    $validated,
                    'default_og_image',
                ),

                twitterCard: (string) $validated[
                    'twitter_card'
                ],

                organizationName: $this->nullableString(
                    $validated,
                    'organization_name',
                ),

                organizationLogo: $this->nullableString(
                    $validated,
                    'organization_logo',
                ),

                organizationSocialProfiles: $this->stringList(
                    $validated[
                        'organization_social_profiles'
                    ] ?? [],
                ),

                googleSiteVerification: $this->nullableString(
                    $validated,
                    'google_site_verification',
                ),

                bingSiteVerification: $this->nullableString(
                    $validated,
                    'bing_site_verification',
                ),

                enableStructuredData: (bool) $validated[
                    'enable_structured_data'
                ],

                enableOpenGraph: (bool) $validated[
                    'enable_open_graph'
                ],

                enableTwitterCards: (bool) $validated[
                    'enable_twitter_cards'
                ],

                enableSitemap: (bool) $validated[
                    'enable_sitemap'
                ],
            ),
        );

        return to_route(
            'admin.seo.settings.edit',
        )->with(
            'success',
            'SEO settings updated successfully.',
        );
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function nullableString(
        array $values,
        string $key,
    ): ?string {
        $value =
            $values[
                $key
            ] ??
            null;

        return is_string(
            $value,
        )
            ? $value
            : null;
    }

    /**
     * @return array<int, string>
     */
    private function stringList(
        mixed $values,
    ): array {
        if (
            ! is_array(
                $values,
            )
        ) {
            return [];
        }

        $result = [];

        foreach (
            $values as $value
        ) {
            if (
                is_string(
                    $value,
                )
            ) {
                $result[] =
                    $value;
            }
        }

        return $result;
    }
}
