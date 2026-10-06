<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\PageBuilder\Queries\PageBuilderBrandOptionsQuery;
use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PageBuilderBrandSearchController extends Controller
{
    public function __invoke(
        Request $request,
        Page $page,
        PageBuilderBrandOptionsQuery $query,
    ): JsonResponse {
        $this->authorize(
            'update',
            $page,
        );

        $search =
            $request
                ->string(
                    'search',
                )
                ->trim()
                ->toString();

        $ids =
            $this->parseIds(
                $request
                    ->string(
                        'ids',
                    )
                    ->toString(),
            );

        return response()->json([
            'brands' => $query->get(
                search: $search === ''
                        ? null
                        : $search,

                ids: $ids,
            ),
        ]);
    }

    /**
     * @return list<int>
     */
    private function parseIds(
        string $value,
    ): array {
        $value =
            trim(
                $value,
            );

        if ($value === '') {
            return [];
        }

        $ids = [];

        foreach (
            explode(
                ',',
                $value,
            ) as $candidate
        ) {
            $candidate =
                trim(
                    $candidate,
                );

            if (
                $candidate === '' ||
                ! ctype_digit(
                    $candidate,
                )
            ) {
                continue;
            }

            $id =
                (int) $candidate;

            if (
                $id < 1 ||
                in_array(
                    $id,
                    $ids,
                    true,
                )
            ) {
                continue;
            }

            $ids[] =
                $id;

            if (
                count(
                    $ids,
                ) >= 24
            ) {
                break;
            }
        }

        return $ids;
    }
}
