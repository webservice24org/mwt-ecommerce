<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CustomerAvatar;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CustomerAvatarController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $this->customer($request);
        $avatar = CustomerAvatar::query()->findOrFail($user->id);
        $bytes = base64_decode($avatar->image_base64, true);
        abort_unless($bytes !== false && in_array($avatar->mime_type, ['image/png', 'image/jpeg', 'image/webp'], true), 404);

        return response($bytes, 200, [
            'Content-Type' => $avatar->mime_type,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, nofollow',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->customer($request);
        $request->validate([
            'avatar' => ['required', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024', 'dimensions:min_width=1,min_height=1,max_width=2000,max_height=2000'],
        ]);

        $file = $request->file('avatar');
        if (! $file instanceof UploadedFile) {
            throw ValidationException::withMessages(['avatar' => 'Choose a valid profile image.']);
        }

        $mime = $file->getMimeType();
        $bytes = file_get_contents($file->getPathname());
        if ($bytes === false || $bytes === '' || strlen($bytes) > 1024 * 1024 || ! in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true)) {
            throw ValidationException::withMessages(['avatar' => 'Choose a valid PNG, JPEG or WebP image up to 1 MB.']);
        }

        DB::transaction(function () use ($user, $mime, $bytes): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            abort_unless($lockedUser->isActive() && ! $lockedUser->isAnonymized(), 403);

            CustomerAvatar::query()->updateOrCreate(
                ['user_id' => $lockedUser->id],
                [
                    'mime_type' => $mime,
                    'image_base64' => base64_encode($bytes),
                    'version' => (string) Str::uuid(),
                ],
            );
        });

        return redirect()->route('profile.edit')->with('success', 'Profile photo updated.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $this->customer($request);

        DB::transaction(function () use ($user): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            abort_unless($lockedUser->isActive() && ! $lockedUser->isAnonymized(), 403);
            CustomerAvatar::query()->whereKey($lockedUser->id)->delete();
        });

        return redirect()->route('profile.edit')->with('success', 'Profile photo removed.');
    }

    private function customer(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isActive() && ! $user->isAnonymized(), 403);

        return $user;
    }
}
