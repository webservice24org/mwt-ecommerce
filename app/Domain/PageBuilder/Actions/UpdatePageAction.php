<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Actions;

use App\Domain\PageBuilder\Data\UpdatePageData;
use App\Domain\PageBuilder\Services\PageIntegrityService;
use App\Models\Page;
use App\Support\Slugs\UniqueSlugGenerator;
use Illuminate\Support\Facades\DB;

final readonly class UpdatePageAction
{
    public function __construct(
        private UniqueSlugGenerator $slugGenerator,
        private PageIntegrityService $integrity,
    ) {}

    public function execute(
        Page $page,
        UpdatePageData $data,
    ): Page {
        return DB::transaction(
            function () use (
                $page,
                $data,
            ): Page {
                $lockedPage = Page::query()
                    ->whereKey($page->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->integrity
                    ->assertTypeIsAvailable(
                        type: $data->type,
                        ignorePageId: $lockedPage->id,
                    );

                $slug = $this->slugGenerator->generate(
                    table: 'pages',
                    column: 'slug',
                    value: $data->slug ?? $data->title,
                    ignoreId: $lockedPage->id,
                );

                $lockedPage->update([
                    'type' => $data->type,
                    'title' => $data->title,
                    'slug' => $slug,
                    'status' => $data->status,
                    'meta_title' => $data->metaTitle,
                    'meta_description' => $data->metaDescription,
                    'published_at' => $data->publishedAt,
                ]);

                return $lockedPage->refresh();
            },
        );
    }
}
