<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Catalog\Concerns;

use App\Domain\Seo\Data\EntitySeoData;

trait InteractsWithCategorySeo
{
    protected function prepareCategorySeoForValidation(): void
    {
        if (
            ! $this->exists(
                'seo',
            )
        ) {
            return;
        }

        $seo =
            $this->input(
                'seo',
            );

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
            'seo' => $seo,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function categorySeoRules(): array
    {
        return [
            'seo' => [
                'sometimes',
                'array',
            ],

            'seo.canonical_url' => [
                'nullable',
                'url:http,https',
                'max:2048',
            ],

            'seo.robots_index' => [
                'nullable',
                'boolean',
            ],

            'seo.robots_follow' => [
                'nullable',
                'boolean',
            ],

            'seo.og_title' => [
                'nullable',
                'string',
                'max:255',
            ],

            'seo.og_description' => [
                'nullable',
                'string',
                'max:500',
            ],

            'seo.og_image' => [
                'nullable',
                'url:http,https',
                'max:2048',
            ],

            'seo.twitter_title' => [
                'nullable',
                'string',
                'max:255',
            ],

            'seo.twitter_description' => [
                'nullable',
                'string',
                'max:500',
            ],

            'seo.twitter_image' => [
                'nullable',
                'url:http,https',
                'max:2048',
            ],
        ];
    }

    public function hasSeoPayload(): bool
    {
        return $this->exists(
            'seo',
        );
    }

    public function toSeoData(): EntitySeoData
    {
        $seo =
            $this->validated(
                'seo',
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
             * Category already owns:
             *
             * categories.meta_title
             * categories.meta_description
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
