<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Catalog\StoreProductDescriptionMediaRequest;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class ProductDescriptionMediaController extends Controller
{
    public function __invoke(
        StoreProductDescriptionMediaRequest $request,
    ): JsonResponse {
        $file = $request->file(
            'image',
        );

        abort_unless(
            $file instanceof UploadedFile,
            422,
        );

        $diskName = (string) config(
            'tiptap-editor.image_upload.disk',
            'public',
        );

        $directory = trim(
            (string) config(
                'tiptap-editor.image_upload.temporary_directory',
                'editor/tmp',
            ),
            '/',
        );

        $extension = strtolower(
            $file->extension(),
        );

        $filename = (string) Str::uuid();

        if ($extension !== '') {
            $filename .= '.'.$extension;
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk(
            $diskName,
        );

        $path = $disk->putFileAs(
            $directory,
            $file,
            $filename,
        );

        abort_unless(
            is_string($path),
            500,
            'Unable to store product description image.',
        );

        $url = $diskName === 'public'
            ? '/storage/'.ltrim(
                $path,
                '/',
            )
            : $disk->url(
                $path,
            );

        return response()->json([
            'url' => $url,
            'path' => $path,
            'temporary' => true,
        ]);
    }
}
