<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Operations;

use App\Domain\Reporting\Data\OperationalExportDefinitionData;
use App\Domain\Reporting\Enums\OperationalExportType;

final class OperationalExportRegistryFactory
{
    public function make(): OperationalExportRegistry
    {
        $registry =
            new OperationalExportRegistry;

        $maxRows =
            $this->maxRows();

        $registry->register(
            new OperationalExportDefinitionData(
                type: OperationalExportType::OperationalOrders,

                routeName: 'admin.reports.operations.orders.csv',

                headers: [
                    'Order Number',
                    'Customer',
                    'Order Status',
                    'Payment Status',
                    'Payment Method',
                    'Fulfilment Status',
                    'Grand Total',
                    'Amount Due',
                    'Currency',
                    'Age Hours',
                    'Age Days',
                    'Attention State',
                    'Attention Severity',
                    'Attention Priority',
                    'Requires Attention',
                    'Placed At',
                ],

                maxRows: $maxRows,
            ),
        );

        $registry->register(
            new OperationalExportDefinitionData(
                type: OperationalExportType::CodOperations,

                routeName: 'admin.reports.operations.cod.csv',

                headers: [
                    'Order Number',
                    'Customer',
                    'Order Status',
                    'Payment Status',
                    'Payment Method',
                    'Grand Total',
                    'Amount Due',
                    'Currency',
                    'Age Hours',
                    'Age Days',
                    'Collection Risk',
                    'Risk Severity',
                    'Risk Priority',
                    'Requires Follow Up',
                    'Payment Due At',
                    'Paid At',
                    'Placed At',
                ],

                maxRows: $maxRows,
            ),
        );

        $registry->register(
            new OperationalExportDefinitionData(
                type: OperationalExportType::InventoryOperations,

                routeName: 'admin.reports.operations.inventory.csv',

                headers: [
                    'Product ID',
                    'Product Name',
                    'SKU',
                    'Slug',
                    'Product Status',
                    'Inventory Tracked',
                    'Stock Quantity',
                    'Low Stock Threshold',
                    'Stock State',
                    'Alert State',
                    'Alert Label',
                    'Alert Severity',
                    'Alert Priority',
                    'Requires Attention',
                    'Updated At',
                ],

                maxRows: $maxRows,
            ),
        );

        $registry->register(
            new OperationalExportDefinitionData(
                type: OperationalExportType::ActionQueue,

                routeName: 'admin.reports.operations.action-queue.csv',

                headers: [
                    'Source',
                    'Entity Type',
                    'Entity ID',
                    'Reference',
                    'Title',
                    'Priority',
                    'Severity',
                    'Reason',
                    'Requires Action',
                    'Age Hours',
                    'Age Days',
                    'Action Label',
                    'Action URL',
                ],

                maxRows: $maxRows,
            ),
        );

        return $registry;
    }

    private function maxRows(): int
    {
        return max(
            1,
            (int) config(
                'reporting.operational_exports.max_rows',
                10_000,
            ),
        );
    }
}
