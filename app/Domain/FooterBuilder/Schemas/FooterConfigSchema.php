<?php

declare(strict_types=1);

namespace App\Domain\FooterBuilder\Schemas;

use App\Domain\FooterBuilder\Enums\FooterTemplate;
use App\Domain\FooterBuilder\Exceptions\InvalidFooterConfiguration;
use App\Domain\FooterBuilder\FooterConfigDefaults;

final class FooterConfigSchema
{
    private const MAX_BRAND_NAME_LENGTH = 160;

    private const MAX_DESCRIPTION_LENGTH = 1000;

    private const MAX_LINK_GROUPS = 4;

    private const MAX_LINKS_PER_GROUP = 10;

    private const MAX_LINK_LABEL_LENGTH = 120;

    private const MAX_URL_LENGTH = 2048;

    private const MAX_SOCIAL_LINKS = 8;

    private const MAX_VALUE_PROPS = 6;

    private const MAX_PAYMENT_METHODS = 8;

    private const MAX_LOCALIZATION_OPTIONS = 12;

    private const MAX_POPULAR_LINKS = 12;

    private const MAX_CERTIFICATIONS = 8;

    private const MAX_SHORT_TEXT_LENGTH = 160;

    private const ALLOWED_SOCIAL_PLATFORMS = [
        'facebook',
        'instagram',
        'x',
        'tiktok',
        'youtube',
        'pinterest',
        'linkedin',
    ];

    private const ALLOWED_VALUE_PROP_ICONS = [
        'package',
        'truck',
        'shield-check',
        'refresh-ccw',
        'headphones',
        'credit-card',
        'lock',
        'badge-check',
        'award',
    ];

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function validate(
        FooterTemplate $template,
        array $config,
    ): array {
        /** @var array<string, list<string>> $errors */
        $errors = [];

        $allowedKeys = [
            'brand',
            'link_groups',
            'social_links',
            'copyright',
            'developer',
            'value_props',
            'newsletter',
            'payment_methods',
            'localization',
            'promotion',
            'popular_links',
            'certifications',
        ];

        $this->rejectUnknownKeys(
            value: $config,
            allowedKeys: $allowedKeys,
            prefix: '',
            errors: $errors,
        );

        $defaults =
            (
                new FooterConfigDefaults
            )->for(
                $template,
            );

        $brand =
            $this->brand(
                value: $config['brand'] ??
                $defaults['brand'],
                errors: $errors,
            );

        $linkGroups =
            $this->linkGroups(
                value: $config['link_groups'] ??
                $defaults['link_groups'],
                errors: $errors,
            );

        $socialLinks =
            $this->socialLinks(
                value: $config['social_links'] ??
                $defaults['social_links'],
                errors: $errors,
            );

        $copyright =
            $this->copyright(
                value: $config['copyright'] ??
                $defaults['copyright'],
                errors: $errors,
            );

        $developer =
            $this->developer(
                value: $config['developer'] ??
                $defaults['developer'],
                errors: $errors,
            );

        $valueProps =
            $this->valueProps(
                value: $config['value_props'] ??
                $defaults['value_props'],
                errors: $errors,
            );

        $newsletter =
            $this->newsletter(
                value: $config['newsletter'] ??
                $defaults['newsletter'],
                errors: $errors,
            );

        $paymentMethods =
            $this->stringList(
                value: $config['payment_methods'] ??
                $defaults['payment_methods'],
                key: 'payment_methods',
                maxItems: self::MAX_PAYMENT_METHODS,
                maxLength: 40,
                errors: $errors,
            );

        $localization =
            $this->localization(
                value: $config['localization'] ??
                $defaults['localization'],
                errors: $errors,
            );

        $promotion =
            $this->promotion(
                value: $config['promotion'] ??
                $defaults['promotion'],
                errors: $errors,
            );

        $popularLinks =
            $this->simpleLinks(
                value: $config['popular_links'] ??
                $defaults['popular_links'],
                key: 'popular_links',
                maxItems: self::MAX_POPULAR_LINKS,
                errors: $errors,
            );

        $certifications =
            $this->stringList(
                value: $config['certifications'] ??
                $defaults['certifications'],
                key: 'certifications',
                maxItems: self::MAX_CERTIFICATIONS,
                maxLength: self::MAX_SHORT_TEXT_LENGTH,
                errors: $errors,
            );

        if (
            $errors !==
            []
        ) {
            throw new InvalidFooterConfiguration(
                $errors,
            );
        }

        return [
            'brand' => $brand,

            'link_groups' => $linkGroups,

            'social_links' => $socialLinks,

            'copyright' => $copyright,

            'developer' => $developer,

            'value_props' => $valueProps,

            'newsletter' => $newsletter,

            'payment_methods' => $paymentMethods,

            'localization' => $localization,

            'promotion' => $promotion,

            'popular_links' => $popularLinks,

            'certifications' => $certifications,
        ];
    }

    /**
     * @param  array<string, list<string>>  $errors
     * @return array<string, mixed>
     */
    private function brand(
        mixed $value,
        array &$errors,
    ): array {
        if (
            ! is_array(
                $value,
            )
        ) {
            $errors['brand'][] =
                'The brand configuration must be an object.';

            return [
                'name' => '',
                'description' => null,
            ];
        }

        $this->rejectUnknownKeys(
            value: $value,
            allowedKeys: [
                'name',
                'description',
            ],
            prefix: 'brand',
            errors: $errors,
        );

        return [
            'name' => $this->requiredString(
                value: $value['name'] ??
                null,
                key: 'brand.name',
                maxLength: self::MAX_BRAND_NAME_LENGTH,
                errors: $errors,
            ),

            'description' => $this->nullableString(
                value: $value['description'] ??
                null,
                key: 'brand.description',
                maxLength: self::MAX_DESCRIPTION_LENGTH,
                errors: $errors,
            ),
        ];
    }

    /**
     * @param  array<string, list<string>>  $errors
     * @return list<array<string, mixed>>
     */
    private function linkGroups(
        mixed $value,
        array &$errors,
    ): array {
        if (
            ! is_array($value) ||
            ! array_is_list(
                $value,
            )
        ) {
            $errors['link_groups'][] =
                'The link groups field must be a list.';

            return [];
        }

        if (
            count(
                $value,
            ) >
            self::MAX_LINK_GROUPS
        ) {
            $errors['link_groups'][] =
                'No more than 4 footer link groups may be added.';
        }

        $normalized = [];

        foreach (
            array_slice(
                $value,
                0,
                self::MAX_LINK_GROUPS,
            ) as $index => $group
        ) {
            if (
                ! is_array(
                    $group,
                )
            ) {
                $errors[
                    "link_groups.{$index}"
                ][] =
                    'Each link group must be an object.';

                continue;
            }

            $prefix =
                "link_groups.{$index}";

            $this->rejectUnknownKeys(
                value: $group,
                allowedKeys: [
                    'heading',
                    'links',
                ],
                prefix: $prefix,
                errors: $errors,
            );

            $normalized[] = [
                'heading' => $this->requiredString(
                    value: $group['heading'] ??
                    null,
                    key: "{$prefix}.heading",
                    maxLength: 100,
                    errors: $errors,
                ),

                'links' => $this->simpleLinks(
                    value: $group['links'] ??
                    null,
                    key: "{$prefix}.links",
                    maxItems: self::MAX_LINKS_PER_GROUP,
                    errors: $errors,
                ),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<string, list<string>>  $errors
     * @return list<array<string, string>>
     */
    private function socialLinks(
        mixed $value,
        array &$errors,
    ): array {
        if (
            ! is_array($value) ||
            ! array_is_list(
                $value,
            )
        ) {
            $errors['social_links'][] =
                'The social links field must be a list.';

            return [];
        }

        if (
            count(
                $value,
            ) >
            self::MAX_SOCIAL_LINKS
        ) {
            $errors['social_links'][] =
                'No more than 8 social links may be added.';
        }

        $normalized = [];

        foreach (
            array_slice(
                $value,
                0,
                self::MAX_SOCIAL_LINKS,
            ) as $index => $item
        ) {
            if (
                ! is_array(
                    $item,
                )
            ) {
                $errors[
                    "social_links.{$index}"
                ][] =
                    'Each social link must be an object.';

                continue;
            }

            $prefix =
                "social_links.{$index}";

            $this->rejectUnknownKeys(
                value: $item,
                allowedKeys: [
                    'platform',
                    'url',
                ],
                prefix: $prefix,
                errors: $errors,
            );

            $platform =
                $item['platform'] ??
                null;

            if (
                ! is_string(
                    $platform,
                ) ||
                ! in_array(
                    $platform,
                    self::ALLOWED_SOCIAL_PLATFORMS,
                    true,
                )
            ) {
                $errors[
                    "{$prefix}.platform"
                ][] =
                    'The social platform is not supported.';

                $platform =
                    'facebook';
            }

            $normalized[] = [
                'platform' => $platform,

                'url' => $this->requiredUrl(
                    value: $item['url'] ??
                    null,
                    key: "{$prefix}.url",
                    errors: $errors,
                ),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<string, list<string>>  $errors
     * @return array<string, string>
     */
    private function copyright(
        mixed $value,
        array &$errors,
    ): array {
        if (
            ! is_array(
                $value,
            )
        ) {
            $errors['copyright'][] =
                'The copyright configuration must be an object.';

            return [
                'name' => '',
                'suffix' => '',
            ];
        }

        $this->rejectUnknownKeys(
            value: $value,
            allowedKeys: [
                'name',
                'suffix',
            ],
            prefix: 'copyright',
            errors: $errors,
        );

        return [
            'name' => $this->requiredString(
                value: $value['name'] ??
                null,
                key: 'copyright.name',
                maxLength: 160,
                errors: $errors,
            ),

            'suffix' => $this->requiredString(
                value: $value['suffix'] ??
                null,
                key: 'copyright.suffix',
                maxLength: 160,
                errors: $errors,
            ),
        ];
    }

    /**
     * @param  array<string, list<string>>  $errors
     * @return array<string, string|null>
     */
    private function developer(
        mixed $value,
        array &$errors,
    ): array {
        if (
            ! is_array(
                $value,
            )
        ) {
            $errors['developer'][] =
                'The developer credit configuration must be an object.';

            return [
                'prefix' => '',
                'name' => '',
                'url' => null,
            ];
        }

        $this->rejectUnknownKeys(
            value: $value,
            allowedKeys: [
                'prefix',
                'name',
                'url',
            ],
            prefix: 'developer',
            errors: $errors,
        );

        return [
            'prefix' => $this->requiredString(
                value: $value['prefix'] ??
                null,
                key: 'developer.prefix',
                maxLength: 100,
                errors: $errors,
            ),

            'name' => $this->requiredString(
                value: $value['name'] ??
                null,
                key: 'developer.name',
                maxLength: 160,
                errors: $errors,
            ),

            'url' => $this->nullableUrl(
                value: $value['url'] ??
                null,
                key: 'developer.url',
                errors: $errors,
            ),
        ];
    }

    /**
     * @param  array<string, list<string>>  $errors
     * @return list<array<string, string>>
     */
    private function valueProps(
        mixed $value,
        array &$errors,
    ): array {
        if (
            ! is_array($value) ||
            ! array_is_list(
                $value,
            )
        ) {
            $errors['value_props'][] =
                'The value propositions field must be a list.';

            return [];
        }

        if (
            count(
                $value,
            ) >
            self::MAX_VALUE_PROPS
        ) {
            $errors['value_props'][] =
                'No more than 6 value propositions may be added.';
        }

        $normalized = [];

        foreach (
            array_slice(
                $value,
                0,
                self::MAX_VALUE_PROPS,
            ) as $index => $item
        ) {
            if (
                ! is_array(
                    $item,
                )
            ) {
                $errors[
                    "value_props.{$index}"
                ][] =
                    'Each value proposition must be an object.';

                continue;
            }

            $prefix =
                "value_props.{$index}";

            $this->rejectUnknownKeys(
                value: $item,
                allowedKeys: [
                    'icon',
                    'title',
                    'description',
                ],
                prefix: $prefix,
                errors: $errors,
            );

            $icon =
                $item['icon'] ??
                null;

            if (
                ! is_string(
                    $icon,
                ) ||
                ! in_array(
                    $icon,
                    self::ALLOWED_VALUE_PROP_ICONS,
                    true,
                )
            ) {
                $errors[
                    "{$prefix}.icon"
                ][] =
                    'The value proposition icon is not supported.';

                $icon =
                    'package';
            }

            $normalized[] = [
                'icon' => $icon,

                'title' => $this->requiredString(
                    value: $item['title'] ??
                    null,
                    key: "{$prefix}.title",
                    maxLength: 120,
                    errors: $errors,
                ),

                'description' => $this->requiredString(
                    value: $item['description'] ??
                    null,
                    key: "{$prefix}.description",
                    maxLength: 240,
                    errors: $errors,
                ),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<string, list<string>>  $errors
     * @return array<string, mixed>
     */
    private function newsletter(
        mixed $value,
        array &$errors,
    ): array {
        if (
            ! is_array(
                $value,
            )
        ) {
            $errors['newsletter'][] =
                'The newsletter configuration must be an object.';

            return [
                'enabled' => false,
                'description' => null,
                'placeholder' => null,
                'button_label' => null,
            ];
        }

        $this->rejectUnknownKeys(
            value: $value,
            allowedKeys: [
                'enabled',
                'description',
                'placeholder',
                'button_label',
            ],
            prefix: 'newsletter',
            errors: $errors,
        );

        $enabled =
            $this->boolean(
                value: $value['enabled'] ??
                null,
                key: 'newsletter.enabled',
                errors: $errors,
            );

        $description =
            $this->nullableString(
                value: $value['description'] ??
                null,
                key: 'newsletter.description',
                maxLength: 800,
                errors: $errors,
            );

        $placeholder =
            $this->nullableString(
                value: $value['placeholder'] ??
                null,
                key: 'newsletter.placeholder',
                maxLength: 160,
                errors: $errors,
            );

        $buttonLabel =
            $this->nullableString(
                value: $value['button_label'] ??
                null,
                key: 'newsletter.button_label',
                maxLength: 80,
                errors: $errors,
            );

        if (
            $enabled &&
            $placeholder ===
            null
        ) {
            $errors[
                'newsletter.placeholder'
            ][] =
                'A newsletter placeholder is required when newsletter signup is enabled.';
        }

        if (
            $enabled &&
            $buttonLabel ===
            null
        ) {
            $errors[
                'newsletter.button_label'
            ][] =
                'A newsletter button label is required when newsletter signup is enabled.';
        }

        return [
            'enabled' => $enabled,

            'description' => $description,

            'placeholder' => $placeholder,

            'button_label' => $buttonLabel,
        ];
    }

    /**
     * @param  array<string, list<string>>  $errors
     * @return array<string, mixed>
     */
    private function localization(
        mixed $value,
        array &$errors,
    ): array {
        if (
            ! is_array(
                $value,
            )
        ) {
            $errors['localization'][] =
                'The localization configuration must be an object.';

            return [
                'enabled' => false,
                'languages' => [],
                'currencies' => [],
            ];
        }

        $this->rejectUnknownKeys(
            value: $value,
            allowedKeys: [
                'enabled',
                'languages',
                'currencies',
            ],
            prefix: 'localization',
            errors: $errors,
        );

        $enabled =
            $this->boolean(
                value: $value['enabled'] ??
                null,
                key: 'localization.enabled',
                errors: $errors,
            );

        $languages =
            $this->localizationOptions(
                value: $value['languages'] ??
                null,
                key: 'localization.languages',
                errors: $errors,
            );

        $currencies =
            $this->localizationOptions(
                value: $value['currencies'] ??
                null,
                key: 'localization.currencies',
                errors: $errors,
            );

        if (
            $enabled &&
            $languages ===
            []
        ) {
            $errors[
                'localization.languages'
            ][] =
                'At least one language is required when localization is enabled.';
        }

        if (
            $enabled &&
            $currencies ===
            []
        ) {
            $errors[
                'localization.currencies'
            ][] =
                'At least one currency is required when localization is enabled.';
        }

        return [
            'enabled' => $enabled,

            'languages' => $languages,

            'currencies' => $currencies,
        ];
    }

    /**
     * @param  array<string, list<string>>  $errors
     * @return list<array<string, string>>
     */
    private function localizationOptions(
        mixed $value,
        string $key,
        array &$errors,
    ): array {
        if (
            ! is_array($value) ||
            ! array_is_list(
                $value,
            )
        ) {
            $errors[$key][] =
                'This field must be a list.';

            return [];
        }

        if (
            count(
                $value,
            ) >
            self::MAX_LOCALIZATION_OPTIONS
        ) {
            $errors[$key][] =
                'No more than 12 options may be added.';
        }

        $normalized = [];

        foreach (
            array_slice(
                $value,
                0,
                self::MAX_LOCALIZATION_OPTIONS,
            ) as $index => $item
        ) {
            if (
                ! is_array(
                    $item,
                )
            ) {
                $errors[
                    "{$key}.{$index}"
                ][] =
                    'Each option must be an object.';

                continue;
            }

            $prefix =
                "{$key}.{$index}";

            $this->rejectUnknownKeys(
                value: $item,
                allowedKeys: [
                    'code',
                    'label',
                ],
                prefix: $prefix,
                errors: $errors,
            );

            $code =
                $this->requiredString(
                    value: $item['code'] ??
                    null,
                    key: "{$prefix}.code",
                    maxLength: 16,
                    errors: $errors,
                );

            if (
                $code !==
                '' &&
                preg_match(
                    '/^[A-Za-z0-9_-]+$/',
                    $code,
                ) !==
                1
            ) {
                $errors[
                    "{$prefix}.code"
                ][] =
                    'The option code may contain only letters, numbers, hyphens, and underscores.';
            }

            $normalized[] = [
                'code' => $code,

                'label' => $this->requiredString(
                    value: $item['label'] ??
                    null,
                    key: "{$prefix}.label",
                    maxLength: 120,
                    errors: $errors,
                ),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<string, list<string>>  $errors
     * @return array<string, mixed>
     */
    private function promotion(
        mixed $value,
        array &$errors,
    ): array {
        if (
            ! is_array(
                $value,
            )
        ) {
            $errors['promotion'][] =
                'The promotion configuration must be an object.';

            return [
                'enabled' => false,
                'badge' => null,
                'message' => null,
                'code' => null,
                'button_label' => null,
                'button_url' => null,
            ];
        }

        $this->rejectUnknownKeys(
            value: $value,
            allowedKeys: [
                'enabled',
                'badge',
                'message',
                'code',
                'button_label',
                'button_url',
            ],
            prefix: 'promotion',
            errors: $errors,
        );

        $enabled =
            $this->boolean(
                value: $value['enabled'] ??
                null,
                key: 'promotion.enabled',
                errors: $errors,
            );

        $badge =
            $this->nullableString(
                value: $value['badge'] ??
                null,
                key: 'promotion.badge',
                maxLength: 60,
                errors: $errors,
            );

        $message =
            $this->nullableString(
                value: $value['message'] ??
                null,
                key: 'promotion.message',
                maxLength: 300,
                errors: $errors,
            );

        $code =
            $this->nullableString(
                value: $value['code'] ??
                null,
                key: 'promotion.code',
                maxLength: 60,
                errors: $errors,
            );

        $buttonLabel =
            $this->nullableString(
                value: $value['button_label'] ??
                null,
                key: 'promotion.button_label',
                maxLength: 80,
                errors: $errors,
            );

        $buttonUrl =
            $this->nullableUrl(
                value: $value['button_url'] ??
                null,
                key: 'promotion.button_url',
                errors: $errors,
            );

        if (
            $enabled &&
            $message ===
            null
        ) {
            $errors[
                'promotion.message'
            ][] =
                'A promotion message is required when the promotion is enabled.';
        }

        if (
            $enabled &&
            $buttonLabel ===
            null
        ) {
            $errors[
                'promotion.button_label'
            ][] =
                'A promotion button label is required when the promotion is enabled.';
        }

        if (
            $enabled &&
            $buttonUrl ===
            null
        ) {
            $errors[
                'promotion.button_url'
            ][] =
                'A promotion button URL is required when the promotion is enabled.';
        }

        return [
            'enabled' => $enabled,

            'badge' => $badge,

            'message' => $message,

            'code' => $code,

            'button_label' => $buttonLabel,

            'button_url' => $buttonUrl,
        ];
    }

    /**
     * @param  array<string, list<string>>  $errors
     * @return list<array<string, string>>
     */
    private function simpleLinks(
        mixed $value,
        string $key,
        int $maxItems,
        array &$errors,
    ): array {
        if (
            ! is_array($value) ||
            ! array_is_list(
                $value,
            )
        ) {
            $errors[$key][] =
                'This field must be a list.';

            return [];
        }

        if (
            count(
                $value,
            ) >
            $maxItems
        ) {
            $errors[$key][] =
                "No more than {$maxItems} links may be added.";
        }

        $normalized = [];

        foreach (
            array_slice(
                $value,
                0,
                $maxItems,
            ) as $index => $item
        ) {
            if (
                ! is_array(
                    $item,
                )
            ) {
                $errors[
                    "{$key}.{$index}"
                ][] =
                    'Each link must be an object.';

                continue;
            }

            $prefix =
                "{$key}.{$index}";

            $this->rejectUnknownKeys(
                value: $item,
                allowedKeys: [
                    'label',
                    'url',
                ],
                prefix: $prefix,
                errors: $errors,
            );

            $normalized[] = [
                'label' => $this->requiredString(
                    value: $item['label'] ??
                    null,
                    key: "{$prefix}.label",
                    maxLength: self::MAX_LINK_LABEL_LENGTH,
                    errors: $errors,
                ),

                'url' => $this->requiredUrl(
                    value: $item['url'] ??
                    null,
                    key: "{$prefix}.url",
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
        string $key,
        int $maxItems,
        int $maxLength,
        array &$errors,
    ): array {
        if (
            ! is_array($value) ||
            ! array_is_list(
                $value,
            )
        ) {
            $errors[$key][] =
                'This field must be a list.';

            return [];
        }

        if (
            count(
                $value,
            ) >
            $maxItems
        ) {
            $errors[$key][] =
                "No more than {$maxItems} items may be added.";
        }

        $normalized = [];

        foreach (
            array_slice(
                $value,
                0,
                $maxItems,
            ) as $index => $item
        ) {
            if (
                ! is_string(
                    $item,
                )
            ) {
                $errors[
                    "{$key}.{$index}"
                ][] =
                    'This item must be a string.';

                continue;
            }

            $item =
                trim(
                    $item,
                );

            if (
                $item ===
                ''
            ) {
                $errors[
                    "{$key}.{$index}"
                ][] =
                    'This item may not be empty.';

                continue;
            }

            if (
                mb_strlen(
                    $item,
                ) >
                $maxLength
            ) {
                $errors[
                    "{$key}.{$index}"
                ][] =
                    "This item may not be greater than {$maxLength} characters.";
            }

            $normalized[] =
                $item;
        }

        return $normalized;
    }

    /**
     * @param  array<mixed>  $value
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
            array_keys(
                $value,
            ) as $key
        ) {
            if (
                ! is_string(
                    $key,
                ) ||
                in_array(
                    $key,
                    $allowedKeys,
                    true,
                )
            ) {
                continue;
            }

            $errorKey =
                $prefix ===
                ''
                ? $key
                : "{$prefix}.{$key}";

            $errors[
                $errorKey
            ][] =
                'This configuration field is not supported.';
        }
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private function requiredString(
        mixed $value,
        string $key,
        int $maxLength,
        array &$errors,
    ): string {
        if (
            ! is_string(
                $value,
            )
        ) {
            $errors[$key][] =
                'This field is required and must be a string.';

            return '';
        }

        $value =
            trim(
                $value,
            );

        if (
            $value ===
            ''
        ) {
            $errors[$key][] =
                'This field is required.';
        } elseif (
            mb_strlen(
                $value,
            ) >
            $maxLength
        ) {
            $errors[$key][] =
                "This field may not be greater than {$maxLength} characters.";
        }

        return $value;
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private function nullableString(
        mixed $value,
        string $key,
        int $maxLength,
        array &$errors,
    ): ?string {
        if (
            $value ===
            null
        ) {
            return null;
        }

        if (
            ! is_string(
                $value,
            )
        ) {
            $errors[$key][] =
                'This field must be a string or null.';

            return null;
        }

        $value =
            trim(
                $value,
            );

        if (
            $value ===
            ''
        ) {
            return null;
        }

        if (
            mb_strlen(
                $value,
            ) >
            $maxLength
        ) {
            $errors[$key][] =
                "This field may not be greater than {$maxLength} characters.";
        }

        return $value;
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private function boolean(
        mixed $value,
        string $key,
        array &$errors,
    ): bool {
        if (
            ! is_bool(
                $value,
            )
        ) {
            $errors[$key][] =
                'This field must be true or false.';

            return false;
        }

        return $value;
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private function requiredUrl(
        mixed $value,
        string $key,
        array &$errors,
    ): string {
        $url =
            $this->nullableUrl(
                value: $value,
                key: $key,
                errors: $errors,
            );

        if (
            $url ===
            null
        ) {
            $errors[$key][] =
                'This URL is required.';

            return '';
        }

        return $url;
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private function nullableUrl(
        mixed $value,
        string $key,
        array &$errors,
    ): ?string {
        if (
            $value ===
            null
        ) {
            return null;
        }

        if (
            ! is_string(
                $value,
            )
        ) {
            $errors[$key][] =
                'This URL must be a string or null.';

            return null;
        }

        $value =
            trim(
                $value,
            );

        if (
            $value ===
            ''
        ) {
            return null;
        }

        if (
            mb_strlen(
                $value,
            ) >
            self::MAX_URL_LENGTH
        ) {
            $errors[$key][] =
                'This URL is too long.';

            return null;
        }

        if (
            str_starts_with(
                $value,
                '#',
            )
        ) {
            return $value;
        }

        if (
            str_starts_with(
                $value,
                '/',
            ) &&
            ! str_starts_with(
                $value,
                '//',
            )
        ) {
            return $value;
        }

        if (
            filter_var(
                $value,
                FILTER_VALIDATE_URL,
            ) ===
            false
        ) {
            $errors[$key][] =
                'Enter a valid URL.';

            return null;
        }

        $scheme =
            parse_url(
                $value,
                PHP_URL_SCHEME,
            );

        if (
            ! is_string(
                $scheme,
            ) ||
            ! in_array(
                strtolower(
                    $scheme,
                ),
                [
                    'http',
                    'https',
                ],
                true,
            )
        ) {
            $errors[$key][] =
                'Only HTTP and HTTPS URLs are supported.';

            return null;
        }

        return $value;
    }
}
