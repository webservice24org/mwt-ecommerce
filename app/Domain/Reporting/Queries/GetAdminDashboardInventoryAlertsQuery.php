<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Reporting\Data\DashboardInventoryAlertData;
use Illuminate\Support\Facades\DB;
use stdClass;

final readonly class GetAdminDashboardInventoryAlertsQuery
{
    /**
     * @return list<DashboardInventoryAlertData>
     */
    public function get(
        int $threshold = 5,
        int $limit = 10,
    ): array {
        $threshold =
            max(
                0,
                min(
                    $threshold,
                    10_000,
                ),
            );

        $limit =
            max(
                1,
                min(
                    $limit,
                    50,
                ),
            );

        $rows =
            DB::table(
                'products',
            )
                ->where(
                    'track_inventory',
                    true,
                )
                ->where(
                    'stock_quantity',
                    '<=',
                    $threshold,
                )
                ->orderBy(
                    'stock_quantity',
                )
                ->orderBy(
                    'name',
                )
                ->orderBy(
                    'id',
                )
                ->limit(
                    $limit,
                )
                ->get([
                    'id',
                    'name',
                    'slug',
                    'sku',
                    'status',
                    'stock_quantity',
                ]);

        return $rows
            ->map(
                static function (stdClass $row) use ($threshold): DashboardInventoryAlertData {
                    $sku =
                        is_string(
                            $row->sku,
                        )
                        && trim(
                            $row->sku,
                        ) !== ''
                        ? $row->sku
                        : null;

                    $stockQuantity =
                        (int) $row
                            ->stock_quantity;

                    return new DashboardInventoryAlertData(
                        productId: (int) $row->id,

                        name: (string) $row->name,

                        slug: (string) $row->slug,

                        sku: $sku,

                        status: (string) $row->status,

                        stockQuantity: $stockQuantity,

                        lowStockThreshold: $threshold,

                        outOfStock: $stockQuantity <= 0,
                    );
                },
            )
            ->values()
            ->all();
    }
}
