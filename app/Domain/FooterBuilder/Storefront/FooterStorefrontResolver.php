<?php

declare(strict_types=1);

namespace App\Domain\FooterBuilder\Storefront;

use App\Domain\FooterBuilder\Enums\FooterTemplate;
use App\Domain\FooterBuilder\Exceptions\InvalidFooterConfiguration;
use App\Domain\FooterBuilder\Schemas\FooterConfigSchema;
use App\Models\FooterSetting;

final readonly class FooterStorefrontResolver
{
    public function __construct(
        private FooterConfigSchema $schema,
    ) {}

    /**
     * @return array{
     *     template: string,
     *     config: array<string, mixed>
     * }|null
     */
    public function resolve(): ?array
    {
        $footer =
            FooterSetting::query()
                ->where(
                    'singleton_key',
                    FooterSetting::SINGLETON_KEY,
                )
                ->first();

        /*
         * Public storefront requests must remain
         * read-only.
         *
         * Do not call FooterSetting::singleton()
         * here because merely visiting the
         * storefront must not create database
         * state.
         */
        if (
            ! $footer instanceof FooterSetting
        ) {
            return null;
        }

        if (
            $footer->is_enabled !== true
        ) {
            return null;
        }

        /*
         * Read the raw persisted value instead of
         * relying on Eloquent's enum cast here.
         *
         * This gives PHPStan an explicit and
         * deterministic string -> enum boundary,
         * while the FooterSetting model can keep
         * its normal FooterTemplate cast.
         */
        $templateValue =
            $footer->getRawOriginal(
                'template',
            );

        if (
            ! is_string(
                $templateValue,
            )
        ) {
            return null;
        }

        $template =
            FooterTemplate::tryFrom(
                $templateValue,
            );

        if (
            ! $template instanceof FooterTemplate
        ) {
            return null;
        }

        $storedConfig =
            $footer->getAttribute(
                'config',
            );

        $config = [];

        if (
            is_array(
                $storedConfig,
            )
        ) {
            foreach (
                $storedConfig as $key => $value
            ) {
                /*
                 * Footer configuration is defined as a
                 * JSON object with string top-level keys.
                 *
                 * Keep the public resolver boundary
                 * explicitly typed for PHPStan.
                 */
                if (
                    ! is_string(
                        $key,
                    )
                ) {
                    continue;
                }

                $config[
                    $key
                ] =
                    $value;
            }
        }

        try {
            $normalized =
                $this->schema
                    ->validate(
                        template: $template,

                        config: $config,
                    );
        } catch (
            InvalidFooterConfiguration
        ) {
            /*
             * Invalid legacy or corrupted stored
             * configuration must never break a
             * public storefront request.
             *
             * The Admin Footer Builder remains the
             * place where the configuration can be
             * repaired.
             */
            return null;
        }

        return [
            'template' => $template->value,

            'config' => $normalized,
        ];
    }
}
