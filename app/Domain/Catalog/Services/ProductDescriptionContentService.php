<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Services;

use MicroWeb\TiptapEditor\Services\EditorHtmlSanitizer;
use MicroWeb\TiptapEditor\Services\EditorMediaService;

final readonly class ProductDescriptionContentService
{
    public function __construct(
        private EditorHtmlSanitizer $sanitizer,
        private EditorMediaService $mediaService,
    ) {}

    public function prepare(
        ?string $html,
        int $productId,
    ): ?string {
        if (
            $html === null
            || trim($html) === ''
        ) {
            return null;
        }

        $cleanHtml = $this->sanitizer
            ->sanitize(
                $html,
            );

        if (trim($cleanHtml) === '') {
            return null;
        }

        $permanentRoot = trim(
            (string) config(
                'tiptap-editor.image_upload.permanent_directory',
                'editor/images',
            ),
            '/',
        );

        if ($permanentRoot === '') {
            $permanentRoot = 'editor/images';
        }

        return $this->mediaService
            ->finalize(
                html: $cleanHtml,
                directory: $permanentRoot
                .'/products/'
                .$productId
                .'/description',
            );
    }
}
