<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\WebsiteSettings;

use App\Domain\HeaderBuilder\Enums\HeaderTemplate;
use App\Domain\HeaderBuilder\Schemas\HeaderConfigSchema;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WebsiteSettings\UpdateHeaderBuilderRequest;
use App\Models\HeaderSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class HeaderBuilderController extends Controller
{
    public function edit(
        Request $request,
    ): Response {
        $header =
            HeaderSetting::singleton();

        $config = (
            new HeaderConfigSchema
        )->validate(
            template: $header->template,
            config: $header->config,
        );

        $admin =
            $request->user('admin');

        return Inertia::render(
            'Admin/HeaderBuilder/Edit',
            [
                'header' => [
                    'template' => $header->template->value,

                    'config' => $config,

                    'is_enabled' => $header->is_enabled,
                ],

                'templates' => array_map(
                    static fn (
                        HeaderTemplate $template,
                    ): array => [
                        'key' => $template->value,

                        'label' => $template->label(),
                    ],
                    HeaderTemplate::cases(),
                ),

                'abilities' => [
                'update' => $admin?->can(
                    'updateAny',
                    HeaderSetting::class,
                ) ?? false,
                ],
            ],
        );
    }

    public function update(
        UpdateHeaderBuilderRequest $request,
    ): RedirectResponse {
        $header =
            HeaderSetting::singleton();

        $template =
            $request->headerTemplate();

        $config =
            $request->normalizedConfig();

        $header->update([
            'template' => $template,

            'config' => $config,

            'is_enabled' => $request->enabled(),
        ]);

        return redirect()
            ->route(
                'admin.website-settings.header-builder.edit',
            )
            ->with(
                'success',
                'Header settings updated successfully.',
            );
    }
}
