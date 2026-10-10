<?php

declare(strict_types=1);

namespace App\Domain\HeaderBuilder\Schemas;

use App\Domain\HeaderBuilder\Enums\HeaderTemplate;
use App\Domain\HeaderBuilder\Exceptions\InvalidHeaderConfiguration;
use App\Domain\HeaderBuilder\HeaderConfigDefaults;

final class HeaderConfigSchema
{
    private const MAX_SHORT_TEXT_LENGTH = 160;

    private const MAX_DESCRIPTION_LENGTH = 1000;

    private const MAX_URL_LENGTH = 2048;

    private const MAX_ANNOUNCEMENT_LINKS = 4;

    private const MAX_TRENDING_SEARCHES = 12;

    private const MAX_ACCOUNT_LINKS = 8;

    private const MAX_NAVIGATION_LINKS = 12;

    private const MAX_MEGA_MENU_GROUPS = 6;

    private const MAX_MEGA_MENU_LINKS = 12;

    private const MAX_MOBILE_LINKS = 12;

    /**
     * @var list<string>
     */
    private const ALLOWED_NAVIGATION_STYLES = [
        'default',
        'highlight',
    ];

    /**
     * @var list<string>
     */
    private const ALLOWED_MOBILE_STYLES = [
        'default',
        'primary',
        'highlight',
    ];

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function validate(
        HeaderTemplate $template,
        array $config,
    ): array {
        /** @var array<string, list<string>> $errors */
        $errors = [];

        $allowedKeys = [
            'announcement',
            'brand',
            'search',
            'actions',
            'navigation',
            'mega_menu',
            'mobile',
        ];

        $this->rejectUnknownKeys(
            value: $config,
            allowedKeys: $allowedKeys,
            prefix: '',
            errors: $errors,
        );

        $defaults = (
            new HeaderConfigDefaults
        )->for(
            $template,
        );

        $announcement = $this->announcement(
            value: $config['announcement']
            ?? $defaults['announcement'],
            defaults: $defaults['announcement'],
            errors: $errors,
        );

        $brand = $this->brand(
            value: $config['brand']
            ?? $defaults['brand'],
            defaults: $defaults['brand'],
            errors: $errors,
        );

        $search = $this->search(
            value: $config['search']
            ?? $defaults['search'],
            defaults: $defaults['search'],
            errors: $errors,
        );

        $actions = $this->actions(
            value: $config['actions']
            ?? $defaults['actions'],
            defaults: $defaults['actions'],
            errors: $errors,
        );

        $navigation = $this->navigation(
            value: $config['navigation']
            ?? $defaults['navigation'],
            defaults: $defaults['navigation'],
            errors: $errors,
        );

        $megaMenu = $this->megaMenu(
            value: $config['mega_menu']
            ?? $defaults['mega_menu'],
            defaults: $defaults['mega_menu'],
            errors: $errors,
        );

        $mobile = $this->mobile(
            value: $config['mobile']
            ?? $defaults['mobile'],
            defaults: $defaults['mobile'],
            errors: $errors,
        );

        if ($errors !== []) {
            throw new InvalidHeaderConfiguration(
                $errors,
            );
        }

        return [
            'announcement' => $announcement,

            'brand' => $brand,

            'search' => $search,

            'actions' => $actions,

            'navigation' => $navigation,

            'mega_menu' => $megaMenu,

            'mobile' => $mobile,
        ];
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @param  array<string, list<string>>  $errors
     * @return array<string, mixed>
     */
    private function announcement(
        mixed $value,
        array $defaults,
        array &$errors,
    ): array {
        $path = 'announcement';

        $array = $this->expectArray(
            value: $value,
            path: $path,
            errors: $errors,
        );

        $this->rejectUnknownKeys(
            value: $array,
            allowedKeys: [
                'enabled',
                'badge',
                'message',
                'promo_code',
                'promo_suffix',
                'links',
                'currency_label',
            ],
            prefix: $path,
            errors: $errors,
        );

        return [
            'enabled' => $this->boolean(
                value: $array['enabled']
                ?? $defaults['enabled'],
                path: "{$path}.enabled",
                errors: $errors,
            ),

            'badge' => $this->shortText(
                value: $array['badge']
                ?? $defaults['badge'],
                path: "{$path}.badge",
                errors: $errors,
                nullable: true,
            ),

            'message' => $this->text(
                value: $array['message']
                ?? $defaults['message'],
                path: "{$path}.message",
                errors: $errors,
                max: self::MAX_DESCRIPTION_LENGTH,
            ),

            'promo_code' => $this->shortText(
                value: $array['promo_code']
                ?? $defaults['promo_code'],
                path: "{$path}.promo_code",
                errors: $errors,
                nullable: true,
            ),

            'promo_suffix' => $this->shortText(
                value: $array['promo_suffix']
                ?? $defaults['promo_suffix'],
                path: "{$path}.promo_suffix",
                errors: $errors,
                nullable: true,
            ),

            'links' => $this->links(
                value: $array['links']
                ?? $defaults['links'],
                path: "{$path}.links",
                maxItems: self::MAX_ANNOUNCEMENT_LINKS,
                errors: $errors,
            ),

            'currency_label' => $this->shortText(
                value: $array['currency_label']
                ?? $defaults['currency_label'],
                path: "{$path}.currency_label",
                errors: $errors,
                nullable: true,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @param  array<string, list<string>>  $errors
     * @return array<string, mixed>
     */
    private function brand(
        mixed $value,
        array $defaults,
        array &$errors,
    ): array {
        $path = 'brand';

        $array = $this->expectArray(
            value: $value,
            path: $path,
            errors: $errors,
        );

        $this->rejectUnknownKeys(
            value: $array,
            allowedKeys: [
                'name',
                'accent',
                'home_url',
                'logo_url',
                'fallback_mark',
            ],
            prefix: $path,
            errors: $errors,
        );

        return [
            'name' => $this->shortText(
                value: $array['name']
                ?? $defaults['name'],
                path: "{$path}.name",
                errors: $errors,
            ),

            'accent' => $this->shortText(
                value: $array['accent']
                ?? $defaults['accent'],
                path: "{$path}.accent",
                errors: $errors,
                nullable: true,
            ),

            'home_url' => $this->url(
                value: $array['home_url']
                ?? $defaults['home_url'],
                path: "{$path}.home_url",
                errors: $errors,
            ),

            'logo_url' => $this->url(
                value: $array['logo_url']
                ?? $defaults['logo_url'],
                path: "{$path}.logo_url",
                errors: $errors,
                nullable: true,
            ),

            'fallback_mark' => $this->shortText(
                value: $array['fallback_mark']
                ?? $defaults['fallback_mark'],
                path: "{$path}.fallback_mark",
                errors: $errors,
                nullable: true,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @param  array<string, list<string>>  $errors
     * @return array<string, mixed>
     */
    private function search(
        mixed $value,
        array $defaults,
        array &$errors,
    ): array {
        $path = 'search';

        $array = $this->expectArray(
            value: $value,
            path: $path,
            errors: $errors,
        );

        $this->rejectUnknownKeys(
            value: $array,
            allowedKeys: [
                'enabled',
                'placeholder',
                'suggestions_enabled',
                'suggestion_heading',
                'trending_searches',
            ],
            prefix: $path,
            errors: $errors,
        );

        return [
            'enabled' => $this->boolean(
                value: $array['enabled']
                ?? $defaults['enabled'],
                path: "{$path}.enabled",
                errors: $errors,
            ),

            'placeholder' => $this->shortText(
                value: $array['placeholder']
                ?? $defaults['placeholder'],
                path: "{$path}.placeholder",
                errors: $errors,
            ),

            'suggestions_enabled' => $this->boolean(
                value: $array['suggestions_enabled']
                ?? $defaults['suggestions_enabled'],
                path: "{$path}.suggestions_enabled",
                errors: $errors,
            ),

            'suggestion_heading' => $this->shortText(
                value: $array['suggestion_heading']
                ?? $defaults['suggestion_heading'],
                path: "{$path}.suggestion_heading",
                errors: $errors,
            ),

            'trending_searches' => $this->stringList(
                value: $array['trending_searches']
                ?? $defaults['trending_searches'],
                path: "{$path}.trending_searches",
                maxItems: self::MAX_TRENDING_SEARCHES,
                errors: $errors,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @param  array<string, list<string>>  $errors
     * @return array<string, mixed>
     */
    private function actions(
        mixed $value,
        array $defaults,
        array &$errors,
    ): array {
        $path = 'actions';

        $array = $this->expectArray(
            value: $value,
            path: $path,
            errors: $errors,
        );

        $this->rejectUnknownKeys(
            value: $array,
            allowedKeys: [
                'wishlist',
                'cart',
                'account',
            ],
            prefix: $path,
            errors: $errors,
        );

        return [
            'wishlist' => $this->simpleAction(
                value: $array['wishlist']
                ?? $defaults['wishlist'],
                defaults: $defaults['wishlist'],
                path: "{$path}.wishlist",
                errors: $errors,
            ),

            'cart' => $this->simpleAction(
                value: $array['cart']
                ?? $defaults['cart'],
                defaults: $defaults['cart'],
                path: "{$path}.cart",
                errors: $errors,
            ),

            'account' => $this->accountAction(
                value: $array['account']
                ?? $defaults['account'],
                defaults: $defaults['account'],
                path: "{$path}.account",
                errors: $errors,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @param  array<string, list<string>>  $errors
     * @return array<string, mixed>
     */
    private function simpleAction(
        mixed $value,
        array $defaults,
        string $path,
        array &$errors,
    ): array {
        $array = $this->expectArray(
            value: $value,
            path: $path,
            errors: $errors,
        );

        $this->rejectUnknownKeys(
            value: $array,
            allowedKeys: [
                'enabled',
                'url',
            ],
            prefix: $path,
            errors: $errors,
        );

        return [
            'enabled' => $this->boolean(
                value: $array['enabled']
                ?? $defaults['enabled'],
                path: "{$path}.enabled",
                errors: $errors,
            ),

            'url' => $this->url(
                value: $array['url']
                ?? $defaults['url'],
                path: "{$path}.url",
                errors: $errors,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @param  array<string, list<string>>  $errors
     * @return array<string, mixed>
     */
    private function accountAction(
        mixed $value,
        array $defaults,
        string $path,
        array &$errors,
    ): array {
        $array = $this->expectArray(
            value: $value,
            path: $path,
            errors: $errors,
        );

        $this->rejectUnknownKeys(
            value: $array,
            allowedKeys: [
                'enabled',
                'guest_login_url',
                'guest_register_url',
                'menu_links',
            ],
            prefix: $path,
            errors: $errors,
        );

        return [
            'enabled' => $this->boolean(
                value: $array['enabled']
                ?? $defaults['enabled'],
                path: "{$path}.enabled",
                errors: $errors,
            ),

            'guest_login_url' => $this->url(
                value: $array['guest_login_url']
                ?? $defaults['guest_login_url'],
                path: "{$path}.guest_login_url",
                errors: $errors,
            ),

            'guest_register_url' => $this->url(
                value: $array['guest_register_url']
                ?? $defaults['guest_register_url'],
                path: "{$path}.guest_register_url",
                errors: $errors,
            ),

            'menu_links' => $this->links(
                value: $array['menu_links']
                ?? $defaults['menu_links'],
                path: "{$path}.menu_links",
                maxItems: self::MAX_ACCOUNT_LINKS,
                errors: $errors,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @param  array<string, list<string>>  $errors
     * @return array<string, mixed>
     */
    private function navigation(
        mixed $value,
        array $defaults,
        array &$errors,
    ): array {
        $path = 'navigation';

        $array = $this->expectArray(
            value: $value,
            path: $path,
            errors: $errors,
        );

        $this->rejectUnknownKeys(
            value: $array,
            allowedKeys: [
                'enabled',
                'mega_menu_label',
                'links',
            ],
            prefix: $path,
            errors: $errors,
        );

        return [
            'enabled' => $this->boolean(
                value: $array['enabled']
                ?? $defaults['enabled'],
                path: "{$path}.enabled",
                errors: $errors,
            ),

            'mega_menu_label' => $this->shortText(
                value: $array['mega_menu_label']
                ?? $defaults['mega_menu_label'],
                path: "{$path}.mega_menu_label",
                errors: $errors,
            ),

            'links' => $this->styledLinks(
                value: $array['links']
                ?? $defaults['links'],
                path: "{$path}.links",
                maxItems: self::MAX_NAVIGATION_LINKS,
                allowedStyles: self::ALLOWED_NAVIGATION_STYLES,
                errors: $errors,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @param  array<string, list<string>>  $errors
     * @return array<string, mixed>
     */
    private function megaMenu(
        mixed $value,
        array $defaults,
        array &$errors,
    ): array {
        $path = 'mega_menu';

        $array = $this->expectArray(
            value: $value,
            path: $path,
            errors: $errors,
        );

        $this->rejectUnknownKeys(
            value: $array,
            allowedKeys: [
                'enabled',
                'groups',
                'promotion',
            ],
            prefix: $path,
            errors: $errors,
        );

        return [
            'enabled' => $this->boolean(
                value: $array['enabled']
                ?? $defaults['enabled'],
                path: "{$path}.enabled",
                errors: $errors,
            ),

            'groups' => $this->megaMenuGroups(
                value: $array['groups']
                ?? $defaults['groups'],
                path: "{$path}.groups",
                errors: $errors,
            ),

            'promotion' => $this->megaMenuPromotion(
                value: $array['promotion']
                ?? $defaults['promotion'],
                defaults: $defaults['promotion'],
                path: "{$path}.promotion",
                errors: $errors,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @param  array<string, list<string>>  $errors
     * @return array<string, mixed>
     */
    private function megaMenuPromotion(
        mixed $value,
        array $defaults,
        string $path,
        array &$errors,
    ): array {
        $array = $this->expectArray(
            value: $value,
            path: $path,
            errors: $errors,
        );

        $this->rejectUnknownKeys(
            value: $array,
            allowedKeys: [
                'enabled',
                'eyebrow',
                'title',
                'button_label',
                'url',
            ],
            prefix: $path,
            errors: $errors,
        );

        return [
            'enabled' => $this->boolean(
                value: $array['enabled']
                ?? $defaults['enabled'],
                path: "{$path}.enabled",
                errors: $errors,
            ),

            'eyebrow' => $this->shortText(
                value: $array['eyebrow']
                ?? $defaults['eyebrow'],
                path: "{$path}.eyebrow",
                errors: $errors,
                nullable: true,
            ),

            'title' => $this->shortText(
                value: $array['title']
                ?? $defaults['title'],
                path: "{$path}.title",
                errors: $errors,
            ),

            'button_label' => $this->shortText(
                value: $array['button_label']
                ?? $defaults['button_label'],
                path: "{$path}.button_label",
                errors: $errors,
            ),

            'url' => $this->url(
                value: $array['url']
                ?? $defaults['url'],
                path: "{$path}.url",
                errors: $errors,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @param  array<string, list<string>>  $errors
     * @return array<string, mixed>
     */
    private function mobile(
        mixed $value,
        array $defaults,
        array &$errors,
    ): array {
        $path = 'mobile';

        $array = $this->expectArray(
            value: $value,
            path: $path,
            errors: $errors,
        );

        $this->rejectUnknownKeys(
            value: $array,
            allowedKeys: [
                'search_enabled',
                'search_placeholder',
                'menu_links',
            ],
            prefix: $path,
            errors: $errors,
        );

        return [
            'search_enabled' => $this->boolean(
                value: $array['search_enabled']
                ?? $defaults['search_enabled'],
                path: "{$path}.search_enabled",
                errors: $errors,
            ),

            'search_placeholder' => $this->shortText(
                value: $array['search_placeholder']
                ?? $defaults['search_placeholder'],
                path: "{$path}.search_placeholder",
                errors: $errors,
            ),

            'menu_links' => $this->styledLinks(
                value: $array['menu_links']
                ?? $defaults['menu_links'],
                path: "{$path}.menu_links",
                maxItems: self::MAX_MOBILE_LINKS,
                allowedStyles: self::ALLOWED_MOBILE_STYLES,
                errors: $errors,
            ),
        ];
    }

    /**
     * @param  array<string, list<string>>  $errors
     * @return list<array<string, string>>
     */
    private function links(
        mixed $value,
        string $path,
        int $maxItems,
        array &$errors,
    ): array {
        if (! is_array($value)) {
            $this->addError(
                $errors,
                $path,
                'Must be an array.',
            );

            return [];
        }

        if (count($value) > $maxItems) {
            $this->addError(
                $errors,
                $path,
                "May not contain more than {$maxItems} items.",
            );
        }

        $normalized = [];

        foreach (
            array_slice(
                $value,
                0,
                $maxItems,
            ) as $index => $item
        ) {
            $itemPath = "{$path}.{$index}";

            $array = $this->expectArray(
                value: $item,
                path: $itemPath,
                errors: $errors,
            );

            $this->rejectUnknownKeys(
                value: $array,
                allowedKeys: [
                    'label',
                    'url',
                ],
                prefix: $itemPath,
                errors: $errors,
            );

            $normalized[] = [
                'label' => $this->shortText(
                    value: $array['label'] ?? '',
                    path: "{$itemPath}.label",
                    errors: $errors,
                ),

                'url' => $this->url(
                    value: $array['url'] ?? '',
                    path: "{$itemPath}.url",
                    errors: $errors,
                ),
            ];
        }

        return $normalized;
    }

    /**
     * @param  list<string>  $allowedStyles
     * @param  array<string, list<string>>  $errors
     * @return list<array<string, string>>
     */
    private function styledLinks(
        mixed $value,
        string $path,
        int $maxItems,
        array $allowedStyles,
        array &$errors,
    ): array {
        if (! is_array($value)) {
            $this->addError(
                $errors,
                $path,
                'Must be an array.',
            );

            return [];
        }

        if (count($value) > $maxItems) {
            $this->addError(
                $errors,
                $path,
                "May not contain more than {$maxItems} items.",
            );
        }

        $normalized = [];

        foreach (
            array_slice(
                $value,
                0,
                $maxItems,
            ) as $index => $item
        ) {
            $itemPath = "{$path}.{$index}";

            $array = $this->expectArray(
                value: $item,
                path: $itemPath,
                errors: $errors,
            );

            $this->rejectUnknownKeys(
                value: $array,
                allowedKeys: [
                    'label',
                    'url',
                    'style',
                ],
                prefix: $itemPath,
                errors: $errors,
            );

            $style = $array['style'] ?? 'default';

            if (
                ! is_string($style)
                || ! in_array(
                    $style,
                    $allowedStyles,
                    true,
                )
            ) {
                $this->addError(
                    $errors,
                    "{$itemPath}.style",
                    'Contains an unsupported style.',
                );

                $style = 'default';
            }

            $normalized[] = [
                'label' => $this->shortText(
                    value: $array['label'] ?? '',
                    path: "{$itemPath}.label",
                    errors: $errors,
                ),

                'url' => $this->url(
                    value: $array['url'] ?? '',
                    path: "{$itemPath}.url",
                    errors: $errors,
                ),

                'style' => $style,
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<string, list<string>>  $errors
     * @return list<array<string, mixed>>
     */
    private function megaMenuGroups(
        mixed $value,
        string $path,
        array &$errors,
    ): array {
        if (! is_array($value)) {
            $this->addError(
                $errors,
                $path,
                'Must be an array.',
            );

            return [];
        }

        if (
            count($value)
            > self::MAX_MEGA_MENU_GROUPS
        ) {
            $this->addError(
                $errors,
                $path,
                'May not contain more than '
                .self::MAX_MEGA_MENU_GROUPS
                .' groups.',
            );
        }

        $normalized = [];

        foreach (
            array_slice(
                $value,
                0,
                self::MAX_MEGA_MENU_GROUPS,
            ) as $index => $item
        ) {
            $itemPath = "{$path}.{$index}";

            $array = $this->expectArray(
                value: $item,
                path: $itemPath,
                errors: $errors,
            );

            $this->rejectUnknownKeys(
                value: $array,
                allowedKeys: [
                    'heading',
                    'links',
                ],
                prefix: $itemPath,
                errors: $errors,
            );

            $normalized[] = [
                'heading' => $this->shortText(
                    value: $array['heading'] ?? '',
                    path: "{$itemPath}.heading",
                    errors: $errors,
                ),

                'links' => $this->links(
                    value: $array['links'] ?? [],
                    path: "{$itemPath}.links",
                    maxItems: self::MAX_MEGA_MENU_LINKS,
                    errors: $errors,
                ),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<string, list<string>>  $errors
     * @return list<string>
     */
    private function stringList(
        mixed $value,
        string $path,
        int $maxItems,
        array &$errors,
    ): array {
        if (! is_array($value)) {
            $this->addError(
                $errors,
                $path,
                'Must be an array.',
            );

            return [];
        }

        if (count($value) > $maxItems) {
            $this->addError(
                $errors,
                $path,
                "May not contain more than {$maxItems} items.",
            );
        }

        $normalized = [];

        foreach (
            array_slice(
                $value,
                0,
                $maxItems,
            ) as $index => $item
        ) {
            $normalized[] = $this->shortText(
                value: $item,
                path: "{$path}.{$index}",
                errors: $errors,
            );
        }

        return $normalized;
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private function boolean(
        mixed $value,
        string $path,
        array &$errors,
    ): bool {
        if (! is_bool($value)) {
            $this->addError(
                $errors,
                $path,
                'Must be a boolean.',
            );

            return false;
        }

        return $value;
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private function shortText(
        mixed $value,
        string $path,
        array &$errors,
        bool $nullable = false,
    ): ?string {
        return $this->text(
            value: $value,
            path: $path,
            errors: $errors,
            max: self::MAX_SHORT_TEXT_LENGTH,
            nullable: $nullable,
        );
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private function text(
        mixed $value,
        string $path,
        array &$errors,
        int $max,
        bool $nullable = false,
    ): ?string {
        if (
            $nullable
            && $value === null
        ) {
            return null;
        }

        if (! is_string($value)) {
            $this->addError(
                $errors,
                $path,
                'Must be a string.',
            );

            return $nullable
                ? null
                : '';
        }

        if (
            mb_strlen($value)
            > $max
        ) {
            $this->addError(
                $errors,
                $path,
                "May not be longer than {$max} characters.",
            );
        }

        return trim($value);
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private function url(
        mixed $value,
        string $path,
        array &$errors,
        bool $nullable = false,
    ): ?string {
        if (
            $nullable
            && $value === null
        ) {
            return null;
        }

        if (! is_string($value)) {
            $this->addError(
                $errors,
                $path,
                'Must be a string.',
            );

            return $nullable
                ? null
                : '';
        }

        $value = trim($value);

        if (
            strlen($value)
            > self::MAX_URL_LENGTH
        ) {
            $this->addError(
                $errors,
                $path,
                'URL is too long.',
            );
        }

        return $value;
    }

    /**
     * @param  array<string, list<string>>  $errors
     * @return array<string, mixed>
     */
    private function expectArray(
        mixed $value,
        string $path,
        array &$errors,
    ): array {
        if (! is_array($value)) {
            $this->addError(
                $errors,
                $path,
                'Must be an object.',
            );

            return [];
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $value
     * @param  list<string>  $allowedKeys
     * @param  array<string, list<string>>  $errors
     */
    private function rejectUnknownKeys(
        array $value,
        array $allowedKeys,
        string $prefix,
        array &$errors,
    ): void {
        foreach (
            array_keys($value) as $key
        ) {
            if (
                in_array(
                    $key,
                    $allowedKeys,
                    true,
                )
            ) {
                continue;
            }

            $path = $prefix !== ''
                ? "{$prefix}.{$key}"
                : $key;

            $this->addError(
                $errors,
                $path,
                'This configuration key is not allowed.',
            );
        }
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private function addError(
        array &$errors,
        string $path,
        string $message,
    ): void {
        $errors[$path] ??= [];

        $errors[$path][] = $message;
    }
}
