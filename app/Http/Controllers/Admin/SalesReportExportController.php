<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Reporting\Enums\ReportExportFormat;
use App\Domain\Reporting\Export\ReportExportFilenameFactory;
use App\Domain\Reporting\Export\ReportExportLimitGuard;
use App\Domain\Reporting\Export\ReportExportWriterRegistry;
use App\Domain\Reporting\Queries\GetAdminSalesReportExportQuery;
use App\Domain\Reporting\Services\ReportDateRangeFactory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalesReportRequest;
use Illuminate\Http\Response;

final class SalesReportExportController extends Controller
{
    public function __invoke(
        SalesReportRequest $request,
        ReportDateRangeFactory $dateRanges,
        GetAdminSalesReportExportQuery $exportQuery,
        ReportExportWriterRegistry $writers,
        ReportExportFilenameFactory $filenames,
        ReportExportLimitGuard $limitGuard,
    ): Response {
        $filters =
            $request->filters(
                $dateRanges,
            );

        $rowCount =
            $exportQuery->count(
                $filters,
            );

        $limitGuard->assertAllowed(
            $rowCount,
        );

        $format =
            ReportExportFormat::Csv;

        $export =
            $exportQuery->get(
                $filters,
            );

        $contents =
            $writers
                ->forFormat(
                    $format,
                )
                ->write(
                    $export,
                );

        $filename =
            $filenames->sales(
                range: $filters->range,

                format: $format,
            );

        return response(
            content: $contents,

            status: Response::HTTP_OK,

            headers: [
                'Content-Type' => $format
                    ->mimeType(),

                'Content-Disposition' => sprintf(
                    'attachment; filename="%s"',
                    $filename,
                ),

                'X-Content-Type-Options' => 'nosniff',

                'Cache-Control' => 'private, no-store, max-age=0',
            ],
        );
    }
}
