<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Concerns;

use App\Domain\Seo\Data\EntitySeoData;

trait InteractsWithPageSeo
{
    protected function preparePageSeoForValidation(): void
    {
        if (
            ! $this->exists(
                'seo_meta',
            )
        ) {
            return;
        }

        $seo =
            $this->input(
                'seo_meta',
            );

        /*
         * Preserve invalid non-array values so validation
         * can reject them through the "array" rule.
         */
        if (
            ! is_array(
                $seo,
            )
        ) {
            return;
        }

        foreach (
            [
                'canonical_url',
                'og_title',
                'og_description',
                'og_image',
                'twitter_title',
                'twitter_description',
                'twitter_image',
            ] as $key
        ) {
            $value =
                $seo[
                    $key
                ] ??
                null;

            if (
                ! is_string(
                    $value,
                )
            ) {
                continue;
            }

            $value =
                trim(
                    $value,
                );

            $seo[
                $key
            ] =
                $value !== ''
                ? $value
                : null;
        }

        $this->merge([
            'seo_meta' => $seo,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function pageSeoRules(): array
    {
        return [
            'seo_meta' => [
                'sometimes',
                'array',
            ],

            'seo_meta.canonical_url' => [
                'nullable',
                'url:http,https',
                'max:2048',
            ],

            'seo_meta.robots_index' => [
                'nullable',
                'boolean',
            ],

            'seo_meta.robots_follow' => [
                'nullable',
                'boolean',
            ],

            'seo_meta.og_title' => [
                'nullable',
                'string',
                'max:255',
            ],

            'seo_meta.og_description' => [
                'nullable',
                'string',
                'max:500',
            ],

            'seo_meta.og_image' => [
                'nullable',
                'url:http,https',
                'max:2048',
            ],

            'seo_meta.twitter_title' => [
                'nullable',
                'string',
                'max:255',
            ],

            'seo_meta.twitter_description' => [
                'nullable',
                'string',
                'max:500',
            ],

            'seo_meta.twitter_image' => [
                'nullable',
                'url:http,https',
                'max:2048',
            ],
        ];
    }

    public function hasSeoPayload(): bool
    {
        return $this->exists(
            'seo_meta',
        );
    }

    public function toSeoData(): EntitySeoData
    {
        $seo =
            $this->validated(
                'seo_meta',
                [],
            );

        if (
            ! is_array(
                $seo,
            )
        ) {
            $seo = [];
        }

        return new EntitySeoData(
            /*
             * Page title/description remain authoritative on:
             *
             * pages.meta_title
             * pages.meta_description
             */
            title: null,

            description: null,

            canonicalUrl: $this->nullableSeoString(
                $seo,
                'canonical_url',
            ),

            robotsIndex: $this->nullableSeoBoolean(
                $seo,
                'robots_index',
            ),

            robotsFollow: $this->nullableSeoBoolean(
                $seo,
                'robots_follow',
            ),

            ogTitle: $this->nullableSeoString(
                $seo,
                'og_title',
            ),

            ogDescription: $this->nullableSeoString(
                $seo,
                'og_description',
            ),

            ogImage: $this->nullableSeoString(
                $seo,
                'og_image',
            ),

            twitterTitle: $this->nullableSeoString(
                $seo,
                'twitter_title',
            ),

            twitterDescription: $this->nullableSeoString(
                $seo,
                'twitter_description',
            ),

            twitterImage: $this->nullableSeoString(
                $seo,
                'twitter_image',
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $seo
     */
    private function nullableSeoString(
        array $seo,
        string $key,
    ): ?string {
        $value =
            $seo[
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
     * @param  array<string, mixed>  $seo
     */
    private function nullableSeoBoolean(
        array $seo,
        string $key,
    ): ?bool {
        if (
            ! array_key_exists(
                $key,
                $seo,
            ) ||
            $seo[
                $key
            ] === null
        ) {
            return null;
        }

        return (bool) $seo[
            $key
        ];
    }
}
