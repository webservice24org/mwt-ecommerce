<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\PageBuilder\Actions\CreatePageAction;
use App\Domain\PageBuilder\Actions\DeletePageAction;
use App\Domain\PageBuilder\Actions\UpdatePageAction;
use App\Domain\PageBuilder\Data\PageDataFactory;
use App\Domain\PageBuilder\Data\PageIndexItemData;
use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\PageBuilder\Enums\PageType;
use App\Domain\PageBuilder\Queries\PageEditQuery;
use App\Domain\PageBuilder\Queries\PageFormOptionsQuery;
use App\Domain\PageBuilder\Queries\PageIndexQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePageRequest;
use App\Http\Requests\Admin\UpdatePageRequest;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PageController extends Controller
{
    public function index(
        Request $request,
        PageIndexQuery $query,
        PageFormOptionsQuery $options,
    ): Response {
        $this->authorize(
            'viewAny',
            Page::class,
        );

        $search = $request->string('search')
            ->trim()
            ->toString();

        $status = PageStatus::tryFrom(
            $request->string('status')->toString(),
        );

        $type = PageType::tryFrom(
            $request->string('type')->toString(),
        );

        $pages = $query->paginate(
            search: $search,
            status: $status,
            type: $type,
        );

        $pages->through(
            static fn (Page $page): array => PageIndexItemData::fromModel(
                $page,
            )->toArray(),
        );

        $admin = $request->user('admin');

        return Inertia::render(
            'Admin/PageBuilder/Pages/Index',
            [
                'pages' => $pages,

                'filters' => [
                    'search' => $search,
                    'status' => $status?->value,
                    'type' => $type?->value,
                ],

                'options' => $options->get(),

                'abilities' => [
                    'create' => $admin?->can(
                        'create',
                        Page::class,
                    ) ?? false,

                    'delete' => $admin?->can(
                        'deleteAny',
                        Page::class,
                    ) ?? false,
                ],
            ],
        );
    }

    public function create(
        PageFormOptionsQuery $options,
    ): Response {
        $this->authorize(
            'create',
            Page::class,
        );

        return Inertia::render(
            'Admin/PageBuilder/Pages/Create',
            [
                'options' => $options->get(),
            ],
        );
    }

    public function store(
        StorePageRequest $request,
        CreatePageAction $action,
    ): RedirectResponse {
        $page = $action->execute(
            $request->toData(),
        );

        return to_route(
            'admin.pages.edit',
            $page,
        )->with(
            'success',
            'Page created successfully.',
        );
    }

    public function edit(
        Page $page,
        PageEditQuery $query,
        PageFormOptionsQuery $options,
        PageDataFactory $dataFactory,
    ): Response {
        $this->authorize(
            'update',
            $page,
        );

        $page = $query->findOrFail(
            $page->id,
        );

        return Inertia::render(
            'Admin/PageBuilder/Pages/Edit',
            [
                'page' => $dataFactory
                    ->fromModel($page)
                    ->toArray(),

                'options' => $options->get(),
            ],
        );
    }

    public function update(
        UpdatePageRequest $request,
        Page $page,
        UpdatePageAction $action,
    ): RedirectResponse {
        $action->execute(
            $page,
            $request->toData(),
        );

        return back()->with(
            'success',
            'Page updated successfully.',
        );
    }

    public function destroy(
        Page $page,
        DeletePageAction $action,
    ): RedirectResponse {
        $this->authorize(
            'delete',
            $page,
        );

        $action->execute($page);

        return to_route(
            'admin.pages.index',
        )->with(
            'success',
            'Page deleted successfully.',
        );
    }
}
