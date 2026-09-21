<?php

declare(strict_types=1);

namespace Tests\Feature\PageBuilder;

use App\Domain\PageBuilder\Data\PageDataFactory;
use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\PageBuilder\Enums\PageType;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

final class PageBuilderDataFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_builds_page_data_from_eager_loaded_model(): void
    {
        $page = Page::factory()->create([
            'type' => PageType::Home,
            'status' => PageStatus::Draft,
            'meta_title' => 'Homepage',
            'meta_description' => 'Homepage description.',
        ]);

        PageSection::factory()->create([
            'page_id' => $page->id,
            'position' => 20,
        ]);

        PageSection::factory()->create([
            'page_id' => $page->id,
            'position' => 10,
        ]);

        $page = Page::query()
            ->with('sections')
            ->findOrFail($page->id);

        $data = app(PageDataFactory::class)
            ->fromModel($page);

        $payload = $data->toArray();

        $this->assertSame($page->id, $payload['id']);
        $this->assertSame('home', $payload['type']);
        $this->assertCount(2, $payload['sections']);

        $this->assertSame(
            [10, 20],
            array_column(
                $payload['sections'],
                'position',
            ),
        );
    }

    public function test_factory_refuses_to_lazy_load_sections(): void
    {
        $page = Page::factory()->create();

        $this->expectException(
            LogicException::class,
        );

        app(PageDataFactory::class)
            ->fromModel($page);
    }
}
