<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seo;

use App\Domain\Seo\Actions\UploadSeoMediaAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Seo\StoreSeoMediaRequest;
use Illuminate\Http\JsonResponse;
use RuntimeException;

final class SeoMediaController extends Controller
{
    public function __invoke(
        StoreSeoMediaRequest $request,
        UploadSeoMediaAction $action,
    ): JsonResponse {
        $image =
            $request->file(
                'image',
            );

        if (
            $image === null
        ) {
            throw new RuntimeException(
                'Validated SEO image upload is missing.',
            );
        }

        $media =
            $action->execute(
                $image,
            );

        return response()->json([
            'image' => $media->toArray(),
        ]);
    }
}
