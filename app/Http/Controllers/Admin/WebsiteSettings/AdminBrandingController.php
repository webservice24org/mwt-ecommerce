<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\WebsiteSettings;

use App\Domain\WebsiteSettings\AdminBrandingSchema;
use App\Http\Controllers\Controller;
use App\Models\ThemeSetting;
use App\Support\Media\ImageStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class AdminBrandingController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $errors = [];
        $config = (new AdminBrandingSchema)->normalize($request->input('admin_branding'), $errors);

        foreach (AdminBrandingSchema::IMAGE_FIELDS as $field) {
            if (isset($config[$field]) && ! Storage::disk('public')->exists(substr($config[$field], strlen('/storage/')))) {
                $errors['admin_branding.'.$field][] = 'The image is missing. Upload it again or remove it.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        ThemeSetting::query()->updateOrCreate(
            ['section' => AdminBrandingSchema::SECTION],
            ['config' => $config],
        );

        return redirect()->route('admin.website-settings.theme-settings.edit')
            ->with('success', 'Admin branding saved successfully.');
    }

    public function upload(Request $request, string $kind, ImageStorageService $storage): JsonResponse
    {
        abort_unless(in_array($kind, AdminBrandingSchema::IMAGE_FIELDS, true), 404);

        $background = $kind === 'background_image';
        $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:png,jpg,jpeg,webp', $background ? 'max:5120' : 'max:2048', $background ? 'dimensions:min_width=1,min_height=1,max_width=4096,max_height=4096' : 'dimensions:min_width=1,min_height=1,max_width=2400,max_height=2400'],
        ]);

        $image = $request->file('image');
        if (! $image instanceof UploadedFile) {
            throw ValidationException::withMessages(['image' => 'Select an image.']);
        }

        $path = $storage->store($image, 'branding/admin/'.$kind);

        return response()->json(['image' => ['url' => '/storage/'.$path]]);
    }
}
