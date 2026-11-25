<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\WebsiteSettings;

use App\Http\Controllers\Controller;
use App\Support\Media\ImageStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

final class BuilderLogoUploadController extends Controller
{
    public function header(Request $request, ImageStorageService $storage): JsonResponse
    {
        return $this->upload($request, $storage, 'branding/header');
    }

    public function footer(Request $request, ImageStorageService $storage): JsonResponse
    {
        return $this->upload($request, $storage, 'branding/footer');
    }

    private function upload(Request $request, ImageStorageService $storage, string $directory): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:min_width=1,min_height=1,max_width=2400,max_height=2400'],
        ]);

        $image = $request->file('image');
        if (! $image instanceof UploadedFile) {
            throw ValidationException::withMessages(['image' => 'Select a logo image.']);
        }

        $path = $storage->store($image, $directory);

        return response()->json([
            'image' => [
                'path' => $path,
                'url' => '/storage/'.$path,
            ],
        ]);
    }
}
