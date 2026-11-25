<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\WebsiteSettings;

use App\Domain\WebsiteSettings\ProductCardStyleSchema;
use App\Http\Controllers\Controller;
use App\Models\ThemeSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class ThemeSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('Admin/WebsiteSettings/ThemeSettings/Edit', [
            'productCards' => ThemeSetting::productCardStyles(),
            'abilities' => [
                'update' => $request->user('admin')?->can('updateAny', ThemeSetting::class) ?? false,
            ],
        ]);
    }

    public function updateProductCards(Request $request): RedirectResponse
    {
        $errors = [];
        $config = (new ProductCardStyleSchema)->normalize($request->input('product_cards'), $errors);

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        ThemeSetting::query()->updateOrCreate(
            ['section' => ThemeSetting::PRODUCT_CARDS],
            ['config' => $config],
        );

        return redirect()->route('admin.website-settings.theme-settings.edit')
            ->with('success', 'Product card styles saved successfully.');
    }
}
