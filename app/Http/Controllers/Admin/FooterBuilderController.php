<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\FooterBuilder\Data\FooterTemplateData;
use App\Domain\FooterBuilder\Enums\FooterTemplate;
use App\Domain\FooterBuilder\Exceptions\InvalidFooterConfiguration;
use App\Domain\FooterBuilder\Registry\FooterTemplateRegistry;
use App\Domain\FooterBuilder\Schemas\FooterConfigSchema;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateFooterSettingRequest;
use App\Models\FooterSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class FooterBuilderController extends Controller
{
    public function edit(
        Request $request,
        FooterTemplateRegistry $registry,
        FooterConfigSchema $schema,
    ): Response {
        $this->authorize(
            'viewAny',
            FooterSetting::class,
        );

        $footer =
            FooterSetting::singleton();

        /*
         * Use getAttribute() here because static analysis
         * may otherwise see the underlying database column
         * type instead of the Eloquent enum cast.
         */
        $templateValue =
            $footer->getAttribute(
                'template',
            );

        $template =
            $templateValue instanceof FooterTemplate
            ? $templateValue
            : $registry->default();

        /*
         * The config column is JSON-cast to an array at
         * runtime. getAttribute() lets us safely narrow
         * that casted value without fighting the raw
         * database-column type inferred by PHPStan.
         */
        $configValue =
            $footer->getAttribute(
                'config',
            );

        $config =
            $schema->validate(
                $template,
                is_array(
                    $configValue,
                )
                ? $configValue
                : [],
            );

        $admin =
            $request->user(
                'admin',
            );

        return Inertia::render(
            'Admin/FooterBuilder/Edit',
            [
                'footer' => [
                    'id' => $footer->id,

                    'template' => $template->value,

                    'config' => $config,

                    'is_enabled' => $footer->is_enabled,
                ],

                'templates' => array_map(
                    static fn (
                        FooterTemplateData $templateData,
                    ): array => $templateData
                        ->toArray(),
                    $registry->all(),
                ),

                'abilities' => [
                'update' => $admin?->can(
                    'updateAny',
                    FooterSetting::class,
                ) ??
                    false,
                ],
            ],
        );
    }

    public function update(
        UpdateFooterSettingRequest $request,
        FooterConfigSchema $schema,
    ): RedirectResponse {
        $this->authorize(
            'updateAny',
            FooterSetting::class,
        );

        $footer =
            FooterSetting::singleton();

        $template =
            $request
                ->footerTemplate();

        try {
            $config =
                $schema->validate(
                    $template,
                    $request
                        ->footerConfig(),
                );
        } catch (
            InvalidFooterConfiguration $exception
        ) {
            throw ValidationException::withMessages(
                $exception->errors(),
            );
        }

        $footer->update([
            'template' => $template,

            'config' => $config,

            'is_enabled' => $request
                ->footerEnabled(),
        ]);

        return to_route(
            'admin.website-settings.footer-builder.edit',
        )->with(
            'success',
            'Footer settings updated successfully.',
        );
    }
}
