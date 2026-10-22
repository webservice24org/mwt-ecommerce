<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Mail\Actions\SendTestMailAction;
use App\Domain\Mail\Queries\GetMailSettingEditorDataQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Mail\SendTestMailRequest;
use App\Http\Requests\Admin\Mail\UpdateMailSettingRequest;
use App\Models\MailSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class MailSettingController extends Controller
{
    public function edit(
        GetMailSettingEditorDataQuery $query,
    ): Response {
        return Inertia::render(
            'Admin/WebsiteSettings/Mail/Edit',
            [
                'setting' => $query->execute(),

                'update_url' => route(
                    'admin.website-settings.mail.update',
                ),

                'test_url' => route(
                    'admin.website-settings.mail.test',
                ),
            ],
        );
    }

    public function update(
        UpdateMailSettingRequest $request,
    ): RedirectResponse {
        $setting =
            MailSetting::current();

        $validated =
            $request->validated();

        /*
         * Password is deliberately handled
         * separately.
         *
         * An empty field means "keep the
         * currently configured password".
         */
        $password =
            $validated['password']
            ?? null;

        unset(
            $validated['password'],
        );

        if (
            is_string($password)
            && trim($password) !== ''
        ) {
            $validated['password'] =
                $password;
        }

        $setting->fill(
            $validated,
        );

        $setting->save();

        return to_route(
            'admin.website-settings.mail.edit',
        )->with(
            'success',
            'Mail settings updated successfully.',
        );
    }

    public function test(
        SendTestMailRequest $request,
        SendTestMailAction $action,
    ): RedirectResponse {
        $action->execute(
            $request->email(),
        );

        return back()->with(
            'success',
            'Test email sent successfully.',
        );
    }
}
