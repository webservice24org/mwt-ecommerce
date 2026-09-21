<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Actions;

use App\Domain\PageBuilder\Data\CreatePageData;
use App\Domain\PageBuilder\Services\PageIntegrityService;
use App\Models\Page;
use App\Support\Slugs\UniqueSlugGenerator;
use Illuminate\Support\Facades\DB;

final readonly class CreatePageAction
{
    public function __construct(
        private UniqueSlugGenerator $slugGenerator,
        private PageIntegrityService $integrity,
    ) {}

    public function execute(
        CreatePageData $data,
    ): Page {
        return DB::transaction(
            function () use ($data): Page {
                $this->integrity
                    ->assertTypeIsAvailable(
                        $data->type,
                    );

                $slug = $this->slugGenerator->generate(
                    table: 'pages',
                    column: 'slug',
                    value: $data->slug ?? $data->title,
                );

                return Page::query()->create([
                    'type' => $data->type,
                    'title' => $data->title,
                    'slug' => $slug,
                    'status' => $data->status,
                    'meta_title' => $data->metaTitle,
                    'meta_description' => $data->metaDescription,
                    'published_at' => $data->publishedAt,
                ]);
            },
        );
    }
}
