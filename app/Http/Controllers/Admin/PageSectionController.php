<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\PageBuilder\Actions\CreatePageSectionAction;
use App\Domain\PageBuilder\Actions\DeletePageSectionAction;
use App\Domain\PageBuilder\Actions\DuplicatePageSectionAction;
use App\Domain\PageBuilder\Actions\ReorderPageSectionsAction;
use App\Domain\PageBuilder\Actions\UpdatePageSectionAction;
use App\Domain\PageBuilder\Queries\PageSectionQuery;
use App\Domain\PageBuilder\Sections\Exceptions\InvalidSectionConfiguration;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderPageSectionsRequest;
use App\Http\Requests\Admin\StorePageSectionRequest;
use App\Http\Requests\Admin\UpdatePageSectionRequest;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

final class PageSectionController extends Controller
{
    public function store(
        StorePageSectionRequest $request,
        Page $page,
        CreatePageSectionAction $action,
    ): RedirectResponse {
        try {
            $action->execute(
                $page,
                $request->toData(),
            );
        } catch (InvalidSectionConfiguration $exception) {
            throw ValidationException::withMessages(
                $exception->errors(),
            );
        }

        return back()->with(
            'success',
            'Section added successfully.',
        );
    }

    public function update(
        UpdatePageSectionRequest $request,
        Page $page,
        int $section,
        PageSectionQuery $query,
        UpdatePageSectionAction $action,
    ): RedirectResponse {
        $pageSection = $query->findForPageOrFail(
            $page,
            $section,
        );

        try {
            $action->execute(
                $pageSection,
                $request->toData(),
            );
        } catch (InvalidSectionConfiguration $exception) {
            throw ValidationException::withMessages(
                $exception->errors(),
            );
        }

        return back()->with(
            'success',
            'Section updated successfully.',
        );
    }

    public function duplicate(
        Page $page,
        int $section,
        PageSectionQuery $query,
        DuplicatePageSectionAction $action,
    ): RedirectResponse {
        $this->authorize(
            'update',
            $page,
        );

        $pageSection = $query->findForPageOrFail(
            $page,
            $section,
        );

        try {
            $action->execute(
                $page,
                $pageSection,
            );
        } catch (InvalidSectionConfiguration $exception) {
            throw ValidationException::withMessages(
                $exception->errors(),
            );
        }

        return back()->with(
            'success',
            'Section duplicated successfully.',
        );
    }

    public function destroy(
        Page $page,
        int $section,
        PageSectionQuery $query,
        DeletePageSectionAction $action,
    ): RedirectResponse {
        $this->authorize(
            'update',
            $page,
        );

        $pageSection = $query->findForPageOrFail(
            $page,
            $section,
        );

        $action->execute(
            $pageSection,
        );

        return back()->with(
            'success',
            'Section removed successfully.',
        );
    }

    public function reorder(
        ReorderPageSectionsRequest $request,
        Page $page,
        ReorderPageSectionsAction $action,
    ): RedirectResponse {
        $action->execute(
            $page,
            $request->toData(),
        );

        return back()->with(
            'success',
            'Section order updated successfully.',
        );
    }
}
