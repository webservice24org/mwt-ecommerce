import {
    Link,
} from '@inertiajs/react'
import {
    useState,
} from 'react'

import ReportNavigation from '@/Components/Admin/Reports/ReportNavigation'

type ReportKey =
    | 'sales'
    | 'products'

interface ReportHeaderProps {
    title: string
    description: string
    activeReport: ReportKey
    exportUrl?: string
    exportLabel?: string
}

interface ExportErrorResponse {
    message?: string
}

export default function ReportHeader({
    title,
    description,
    activeReport,
    exportUrl,
    exportLabel = 'Export CSV',
}: ReportHeaderProps) {
    const [
        isExporting,
        setIsExporting,
    ] = useState(
        false,
    )

    const [
        exportError,
        setExportError,
    ] = useState<
        string | null
    >(
        null,
    )

    const downloadExport =
        async () => {
            if (
                !exportUrl ||
                isExporting
            ) {
                return
            }

            setIsExporting(
                true,
            )

            setExportError(
                null,
            )

            try {
                const response =
                    await fetch(
                        exportUrl,
                        {
                            method:
                                'GET',

                            credentials:
                                'same-origin',

                            headers: {
                                Accept:
                                    'application/json',

                                'X-Requested-With':
                                    'XMLHttpRequest',
                            },
                        },
                    )

                if (
                    !response.ok
                ) {
                    throw new Error(
                        await exportErrorMessage(
                            response,
                        ),
                    )
                }

                const blob =
                    await response.blob()

                const filename =
                    exportFilename(
                        response,
                    )

                triggerDownload(
                    blob,
                    filename,
                )
            } catch (
                error
            ) {
                setExportError(
                    error instanceof
                    Error
                        ? error.message
                        : 'Unable to export this report. Please try again.',
                )
            } finally {
                setIsExporting(
                    false,
                )
            }
        }

    return (
        <section className="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm sm:p-5">
            <div className="flex flex-col gap-5">
                <nav
                    aria-label="Breadcrumb"
                    className="flex flex-wrap items-center gap-2 text-xs text-neutral-500"
                >
                    <Link
                        href={route(
                            'admin.dashboard',
                        )}
                        className="transition hover:text-neutral-900"
                    >
                        Dashboard
                    </Link>

                    <span
                        aria-hidden="true"
                    >
                        /
                    </span>

                    <span>
                        Reports
                    </span>

                    <span
                        aria-hidden="true"
                    >
                        /
                    </span>

                    <span
                        aria-current="page"
                        className="font-medium text-neutral-700"
                    >
                        {title}
                    </span>
                </nav>

                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="min-w-0">
                        <h1 className="text-lg font-semibold text-neutral-900">
                            {title}
                        </h1>

                        <p className="mt-1 text-sm leading-6 text-neutral-500">
                            {
                                description
                            }
                        </p>
                    </div>

                    {exportUrl && (
                        <button
                            type="button"
                            onClick={
                                downloadExport
                            }
                            disabled={
                                isExporting
                            }
                            aria-busy={
                                isExporting
                            }
                            className="inline-flex min-h-10 shrink-0 items-center justify-center rounded-lg border border-neutral-300 bg-white px-4 py-2.5 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-neutral-300 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {isExporting
                                ? 'Preparing export...'
                                : exportLabel}
                        </button>
                    )}
                </div>

                <ReportNavigation
                    active={
                        activeReport
                    }
                />
            </div>

            {exportError && (
                <div
                    role="alert"
                    aria-live="assertive"
                    className="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
                >
                    <p className="font-semibold">
                        Export unavailable
                    </p>

                    <p className="mt-1 leading-5">
                        {
                            exportError
                        }
                    </p>
                </div>
            )}
        </section>
    )
}

async function exportErrorMessage(
    response: Response,
): Promise<string> {
    try {
        const contentType =
            response.headers.get(
                'Content-Type',
            ) ?? ''

        if (
            contentType.includes(
                'application/json',
            )
        ) {
            const payload =
                (await response.json()) as
                    ExportErrorResponse

            if (
                payload.message &&
                payload.message.trim() !==
                    ''
            ) {
                return payload.message
            }
        }

        const body =
            await response.text()

        if (
            body.trim() !== ''
        ) {
            return body
        }
    } catch {
        //
    }

    if (
        response.status ===
        413
    ) {
        return 'This report is too large to export. Narrow the date range or filters and try again.'
    }

    if (
        response.status ===
            401 ||
        response.status ===
            403
    ) {
        return 'You are not authorized to export this report.'
    }

    return 'Unable to export this report. Please try again.'
}

function exportFilename(
    response: Response,
): string {
    const disposition =
        response.headers.get(
            'Content-Disposition',
        )

    if (
        disposition
    ) {
        const match =
            disposition.match(
                /filename="([^"]+)"/i,
            )

        if (
            match?.[1]
        ) {
            return match[1]
        }
    }

    return 'report.csv'
}

function triggerDownload(
    blob: Blob,
    filename: string,
): void {
    const downloadUrl =
        URL.createObjectURL(
            blob,
        )

    const anchor =
        document.createElement(
            'a',
        )

    anchor.href =
        downloadUrl

    anchor.download =
        filename

    anchor.style.display =
        'none'

    document.body.appendChild(
        anchor,
    )

    anchor.click()

    anchor.remove()

    window.setTimeout(
        () => {
            URL.revokeObjectURL(
                downloadUrl,
            )
        },
        0,
    )
}
