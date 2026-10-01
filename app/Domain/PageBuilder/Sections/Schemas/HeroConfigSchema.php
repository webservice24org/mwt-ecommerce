<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Sections\Schemas;

use App\Domain\PageBuilder\Sections\Contracts\SectionConfigSchema;
use App\Domain\PageBuilder\Sections\Exceptions\InvalidSectionConfiguration;

final class HeroConfigSchema implements SectionConfigSchema
{
    private const ALLOWED_KEYS = [
        'autoplay',
        'autoplay_delay',
        'effect',
        'show_arrows',
        'show_dots',
        'slides',
    ];

    private const ALLOWED_SLIDE_KEYS = [
        'background_color',
        'background_image',
        'image',
        'alt',
        'url',
        'top_title',
        'title',
        'description',
        'alignment',
        'primary_button',
        'secondary_button',
    ];

    private const ALLOWED_BUTTON_KEYS = [
        'label',
        'url',
    ];

    private const EFFECTS = [
        'slide_left',
        'slide_right',
        'slide_up',
        'slide_down',
        'fade',
        'fade_scale',
        'zoom',
    ];

    private const ALIGNMENTS = [
        'left',
        'center',
        'right',
    ];

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function validate(array $config): array
    {
        $this->rejectUnknownKeys(
            $config,
            self::ALLOWED_KEYS,
            'Hero configuration',
            'config',
        );

        $autoplay = $this->boolean(
            $config,
            'autoplay',
            true,
        );

        $autoplayDelay = $this->autoplayDelay(
            $config['autoplay_delay'] ?? 5000,
        );

        $effect = $this->effect(
            $config['effect'] ?? 'slide_left',
        );

        $showArrows = $this->boolean(
            $config,
            'show_arrows',
            true,
        );

        $showDots = $this->boolean(
            $config,
            'show_dots',
            true,
        );

        $slides = $this->slides(
            $config['slides'] ?? [],
        );

        if ($slides === []) {
            $this->fail(
                'slides',
                'Hero configuration must contain at least one slide.',
            );
        }

        return [
            'autoplay' => $autoplay,
            'autoplay_delay' => $autoplayDelay,
            'effect' => $effect,
            'show_arrows' => $showArrows,
            'show_dots' => $showDots,
            'slides' => $slides,
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function boolean(
        array $config,
        string $key,
        bool $default,
    ): bool {
        if (! \array_key_exists($key, $config)) {
            return $default;
        }

        if (! \is_bool($config[$key])) {
            $this->fail(
                $key,
                \sprintf(
                    'Hero %s must be a boolean.',
                    $key,
                ),
            );
        }

        return $config[$key];
    }

    private function autoplayDelay(mixed $value): int
    {
        if (! \is_int($value)) {
            $this->fail(
                'autoplay_delay',
                'Hero autoplay_delay must be an integer.',
            );
        }

        if ($value < 1000 || $value > 30000) {
            $this->fail(
                'autoplay_delay',
                'Hero autoplay_delay must be between 1000 and 30000 milliseconds.',
            );
        }

        return $value;
    }

    private function effect(mixed $value): string
    {
        if (
            ! \is_string($value)
            || ! \in_array($value, self::EFFECTS, true)
        ) {
            $this->fail(
                'effect',
                'Hero effect is not supported.',
            );
        }

        return $value;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function slides(mixed $value): array
    {
        if (! \is_array($value)) {
            $this->fail(
                'slides',
                'Hero slides must be an array.',
            );
        }

        $slides = [];

        foreach ($value as $index => $slide) {
            if (! \is_array($slide)) {
                $this->fail(
                    \sprintf('slides.%s', $index),
                    'Every Hero slide must be an array.',
                );
            }

            /** @var array<string, mixed> $slide */
            $slides[] = $this->slide(
                $slide,
                (int) $index,
            );
        }

        return $slides;
    }

    /**
     * @param  array<string, mixed>  $slide
     * @return array<string, mixed>
     */
    private function slide(
        array $slide,
        int $index,
    ): array {
        $this->rejectUnknownKeys(
            $slide,
            self::ALLOWED_SLIDE_KEYS,
            'Hero slide',
            \sprintf('slides.%d', $index),
        );

        $normalized = [];

        $this->optionalString(
            $slide,
            $normalized,
            'background_color',
            $index,
        );

        $this->optionalString(
            $slide,
            $normalized,
            'background_image',
            $index,
        );

        $this->optionalString(
            $slide,
            $normalized,
            'image',
            $index,
        );

        $this->optionalString(
            $slide,
            $normalized,
            'alt',
            $index,
        );

        $this->optionalString(
            $slide,
            $normalized,
            'url',
            $index,
        );

        $this->optionalString(
            $slide,
            $normalized,
            'top_title',
            $index,
        );

        $this->optionalString(
            $slide,
            $normalized,
            'title',
            $index,
        );

        $this->optionalString(
            $slide,
            $normalized,
            'description',
            $index,
        );

        $normalized['alignment'] = $this->alignment(
            $slide['alignment'] ?? 'left',
            $index,
        );

        $normalized['primary_button'] = $this->button(
            $slide['primary_button'] ?? null,
            'primary_button',
            $index,
        );

        $normalized['secondary_button'] = $this->button(
            $slide['secondary_button'] ?? null,
            'secondary_button',
            $index,
        );

        return $normalized;
    }

    private function alignment(
        mixed $value,
        int $index,
    ): string {
        if (
            ! \is_string($value)
            || ! \in_array($value, self::ALIGNMENTS, true)
        ) {
            $this->fail(
                \sprintf(
                    'slides.%d.alignment',
                    $index,
                ),
                'Hero slide alignment is not supported.',
            );
        }

        return $value;
    }

    /**
     * @return array{label: string, url: string}|null
     */
    private function button(
        mixed $value,
        string $name,
        int $index,
    ): ?array {
        if ($value === null) {
            return null;
        }

        $errorKey = \sprintf(
            'slides.%d.%s',
            $index,
            $name,
        );

        if (! \is_array($value)) {
            $this->fail(
                $errorKey,
                \sprintf(
                    'Hero %s must be an array or null.',
                    $name,
                ),
            );
        }

        /** @var array<string, mixed> $value */
        $this->rejectUnknownKeys(
            $value,
            self::ALLOWED_BUTTON_KEYS,
            \sprintf(
                'Hero %s',
                $name,
            ),
            $errorKey,
        );

        $label = $value['label'] ?? null;
        $url = $value['url'] ?? null;

        if (
            ! \is_string($label)
            || \trim($label) === ''
            || ! \is_string($url)
            || \trim($url) === ''
        ) {
            $this->fail(
                $errorKey,
                \sprintf(
                    'Hero %s requires both a label and URL.',
                    $name,
                ),
            );
        }

        return [
            'label' => \trim($label),
            'url' => \trim($url),
        ];
    }

    /**
     * @param  array<string, mixed>  $source
     * @param  array<string, mixed>  $target
     */
    private function optionalString(
        array $source,
        array &$target,
        string $key,
        int $index,
    ): void {
        if (! \array_key_exists($key, $source)) {
            return;
        }

        $value = $source[$key];

        if ($value === null) {
            $target[$key] = null;

            return;
        }

        if (! \is_string($value)) {
            $this->fail(
                \sprintf(
                    'slides.%d.%s',
                    $index,
                    $key,
                ),
                \sprintf(
                    'Hero slide %s must be a string or null.',
                    $key,
                ),
            );
        }

        $target[$key] = \trim($value);
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  list<string>  $allowed
     */
    private function rejectUnknownKeys(
        array $values,
        array $allowed,
        string $context,
        string $errorKey,
    ): void {
        $unknown = \array_diff(
            \array_keys($values),
            $allowed,
        );

        if ($unknown === []) {
            return;
        }

        $this->fail(
            $errorKey,
            \sprintf(
                '%s contains unsupported fields: %s.',
                $context,
                \implode(', ', $unknown),
            ),
        );
    }

    private function fail(
        string $key,
        string $message,
    ): never {
        throw new InvalidSectionConfiguration([
            $key => [
                $message,
            ],
        ]);
    }
}
