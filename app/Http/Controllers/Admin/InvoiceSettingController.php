<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Invoice\UpdateInvoiceSettingRequest;
use App\Models\InvoiceSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

final class InvoiceSettingController extends Controller
{
    public function edit(): Response
    {
        $setting =
            InvoiceSetting::current();

        return Inertia::render(
            'Admin/WebsiteSettings/Invoice/Edit',
            [
                'setting' => [
                    'company_name' => $setting->company_name,

                    'company_tagline' => $setting->company_tagline,

                    'logo_url' => $setting->logo_path !== null
                        ? asset(
                            'storage/'.ltrim(
                                $setting->logo_path,
                                '/',
                            ),
                        )
                        : null,

                    'header_start_color' => $setting->header_start_color,

                    'header_end_color' => $setting->header_end_color,

                    'footer_color' => $setting->footer_color,

                    'support_email' => $setting->support_email,

                    'footer_credit' => $setting->footer_credit,
                ],

                'update_url' => route(
                    'admin.website-settings.invoice.update',
                ),
            ],
        );
    }

    public function update(
        UpdateInvoiceSettingRequest $request,
    ): RedirectResponse {
        $setting =
            InvoiceSetting::current();

        $validated =
            $request->validated();

        $oldLogo =
            $setting->logo_path;

        $logoPath =
            $oldLogo;

        if (
            (bool) ($validated['remove_logo'] ?? false)
        ) {
            $logoPath = null;
        }

        if ($request->hasFile('logo')) {
            $logoPath = $request
                ->file('logo')
                ?->store(
                    'invoice-branding',
                    'public',
                );
        }

        $setting->update([
            'company_name' => $validated['company_name'],

            'company_tagline' => $validated['company_tagline'],

            'logo_path' => $logoPath,

            'header_start_color' => $validated['header_start_color'],

            'header_end_color' => $validated['header_end_color'],

            'footer_color' => $validated['footer_color'],

            'support_email' => $validated['support_email']
                ?? null,

            'footer_credit' => $validated['footer_credit'],
        ]);

        if (
            $oldLogo !== null
            && $oldLogo !== $logoPath
        ) {
            Storage::disk('public')
                ->delete(
                    $oldLogo,
                );
        }

        return back()->with(
            'success',
            'Invoice settings updated successfully.',
        );
    }
}
