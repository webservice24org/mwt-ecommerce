<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\PageBuilder\Actions\UploadPageBuilderImageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PageBuilder\StorePageBuilderImageRequest;
use App\Models\Page;
use Illuminate\Http\JsonResponse;

final class PageBuilderImageController extends Controller
{
    public function __invoke(
        StorePageBuilderImageRequest $request,
        Page $page,
        UploadPageBuilderImageAction $action,
    ): JsonResponse {
        $image = $action->execute(
            $page,
            $request->file('image'),
        );

        return response()->json([
            'image' => $image->toArray(),
        ]);
    }
}
