import AdminLayout from '@/Layouts/Admin/AdminLayout'
import type {
    MerchantFeedExportHistory,
    MerchantFeedExportHistoryItem,
    MerchantFeedExportStatus,
} from '@/types/merchant-feed-export'
import type {
    MerchantFinding,
    MerchantFindingSeverity,
    MerchantReadinessFilters,
    MerchantReadinessItem,
    MerchantReadinessOption,
    MerchantReadinessStatus,
    MerchantReadinessWorkspace,
} from '@/types/merchant-readiness'
import {
    Head,
    Link,
    router,
    useForm,
} from '@inertiajs/react'
import {
    AlertTriangle,
    ArrowLeft,
    ArrowRight,
    CheckCircle2,
    CircleAlert,
    Download,
    FileSpreadsheet,
    Filter,
    PackageCheck,
    PackageX,
    RefreshCcw,
    RotateCcw,
    Search,
    Settings2,
    ShoppingBag,
    TriangleAlert,
} from 'lucide-react'
import type {
    FormEvent,
    ReactNode,
} from 'react'
import {
    useMemo,
    useState,
} from 'react'

type Props = {
    workspace: MerchantReadinessWorkspace
    exports: MerchantFeedExportHistory
}

type FilterState = {
    search: string
    status: string
    feed: string
    availability: string
    identifier_state: string
    severity: string
}

export default function Readiness({
    workspace,
    exports,
}: Props) {
    const [
        filters,
        setFilters,
    ] = useState<FilterState>(() =>
        fromServerFilters(
            workspace.filters,
        ),
    )

    const exportForm =
        useForm({})

    const [
        retryingExport,
        setRetryingExport,
    ] = useState<string | null>(
        null,
    )

    const topFindings =
        useMemo(
            () =>
                Object.entries(
                    workspace.summary
                        .finding_counts,
                )
                    .sort(
                        (
                            left,
                            right,
                        ) =>
                            right[1] -
                            left[1],
                    )
                    .slice(
                        0,
                        6,
                    ),
            [
                workspace.summary
                    .finding_counts,
            ],
        )

    const submit = (
        event: FormEvent<HTMLFormElement>,
    ) => {
        event.preventDefault()

        navigate(
            filters,
            1,
        )
    }

    const clearFilters = () => {
        const empty: FilterState = {
            search: '',
            status: '',
            feed: '',
            availability: '',
            identifier_state: '',
            severity: '',
        }

        setFilters(
            empty,
        )

        navigate(
            empty,
            1,
        )
    }

    const navigate = (
        nextFilters: FilterState,
        page: number,
    ) => {
        router.get(
            route(
                'admin.seo.merchant.readiness.index',
            ),
            compactQuery(
                nextFilters,
                page,
            ),
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }

    const requestExport = () => {
        exportForm.post(
            route(
                'admin.seo.merchant.exports.store',
            ),
            {
                preserveScroll: true,
            },
        )
    }

    const retryExport = (
        publicId: string,
    ) => {
        setRetryingExport(
            publicId,
        )

        router.post(
            route(
                'admin.seo.merchant.exports.retry',
                publicId,
            ),
            {},
            {
                preserveScroll: true,
                onFinish: () =>
                    setRetryingExport(
                        null,
                    ),
            },
        )
    }

    const navigateExports = (
        page: number,
    ) => {
        const query =
            compactQuery(
                fromServerFilters(
                    workspace.filters,
                ),
                workspace.pagination.current_page,
            )

        if (page > 1) {
            query.export_page =
                page
        }

        router.get(
            route(
                'admin.seo.merchant.readiness.index',
            ),
            query,
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }

    return (
        <AdminLayout title="">
            <Head title="Merchant readiness" />

            <div className="mx-auto max-w-[1600px] space-y-6">
                <header className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">
                            Merchant / Shopping SEO
                        </p>

                        <h1 className="mt-2 text-2xl font-bold tracking-tight text-neutral-950 dark:text-neutral-100 sm:text-3xl">
                            Merchant readiness workspace
                        </h1>

                        <p className="mt-2 max-w-3xl text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                            Review the server-authoritative M.1 readiness findings and M.3 feed eligibility before exporting product data. Nothing on this page submits data to Google.
                        </p>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <button
                            type="button"
                            onClick={requestExport}
                            disabled={exportForm.processing}
                            className="inline-flex min-h-10 items-center gap-2 rounded-lg bg-neutral-950 px-4 text-sm font-semibold text-white transition hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200"
                        >
                            <FileSpreadsheet
                                className="h-4 w-4"
                                aria-hidden="true"
                            />
                            {exportForm.processing
                                ? 'Queueing export…'
                                : 'Export TSV'}
                        </button>

                        <Link
                            href={route(
                                'admin.seo.merchant.policies.index',
                            )}
                            className="inline-flex min-h-10 items-center gap-2 rounded-lg border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-200 dark:hover:bg-neutral-900"
                        >
                            <Settings2
                                className="h-4 w-4"
                                aria-hidden="true"
                            />
                            Merchant policy
                        </Link>

                        <Link
                            href={route(
                                'admin.products.index',
                            )}
                            className="inline-flex min-h-10 items-center gap-2 rounded-lg bg-neutral-950 px-4 text-sm font-semibold text-white transition hover:bg-neutral-800 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200"
                        >
                            <ShoppingBag
                                className="h-4 w-4"
                                aria-hidden="true"
                            />
                            Products
                        </Link>
                    </div>
                </header>

                {workspace.summary.truncated && (
                    <div className="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/20 dark:text-amber-200">
                        <div className="flex items-start gap-3">
                            <TriangleAlert
                                className="mt-0.5 h-5 w-5 shrink-0"
                                aria-hidden="true"
                            />
                            <div>
                                <p className="font-semibold">
                                    Readiness catalog reached its safety bound
                                </p>
                                <p className="mt-1 leading-6">
                                    M.3 stopped at {formatNumber(workspace.summary.max_items)} item candidates. Summary and filters cover only this bounded catalog; export operations must preserve the same bound until a later phase deliberately changes it.
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-7">
                    <MetricCard
                        label="Products"
                        value={workspace.summary.products_considered}
                        helper={`${formatNumber(workspace.summary.item_candidates)} item candidates`}
                        icon={<ShoppingBag className="h-5 w-5" aria-hidden="true" />}
                    />

                    <MetricCard
                        label="Ready"
                        value={workspace.summary.ready}
                        helper="No M.1 blocker or warning"
                        icon={<CheckCircle2 className="h-5 w-5" aria-hidden="true" />}
                    />

                    <MetricCard
                        label="Needs work"
                        value={workspace.summary.needs_work}
                        helper={`${formatNumber(workspace.summary.severity_counts.warning)} warnings`}
                        icon={<AlertTriangle className="h-5 w-5" aria-hidden="true" />}
                    />

                    <MetricCard
                        label="Blocked"
                        value={workspace.summary.blocked}
                        helper={`${formatNumber(workspace.summary.severity_counts.blocker)} blockers`}
                        icon={<CircleAlert className="h-5 w-5" aria-hidden="true" />}
                    />

                    <MetricCard
                        label="Feed eligible"
                        value={workspace.summary.feed_eligible}
                        helper="Included by M.3"
                        icon={<PackageCheck className="h-5 w-5" aria-hidden="true" />}
                    />

                    <MetricCard
                        label="Excluded"
                        value={workspace.summary.excluded}
                        helper="Not present in feed output"
                        icon={<PackageX className="h-5 w-5" aria-hidden="true" />}
                    />

                    <MetricCard
                        label="Out of stock"
                        value={workspace.summary.out_of_stock}
                        helper="Still shown for readiness review"
                        icon={<RefreshCcw className="h-5 w-5" aria-hidden="true" />}
                    />
                </section>

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
                    <main className="min-w-0 space-y-6">
                        <section className="rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                            <div className="border-b border-neutral-200 p-4 dark:border-neutral-800 sm:p-5">
                                <div className="flex items-start gap-3">
                                    <div className="mt-0.5 rounded-lg bg-neutral-100 p-2 text-neutral-600 dark:bg-neutral-900 dark:text-neutral-300">
                                        <Filter
                                            className="h-4 w-4"
                                            aria-hidden="true"
                                        />
                                    </div>

                                    <div>
                                        <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">
                                            Filter merchant items
                                        </h2>
                                        <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                            Filters change the table only. Summary cards remain the full bounded M.4A catalog.
                                        </p>
                                    </div>
                                </div>

                                <form
                                    onSubmit={submit}
                                    className="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3"
                                >
                                    <label className="md:col-span-2 xl:col-span-3">
                                        <span className="sr-only">
                                            Search merchant items
                                        </span>
                                        <div className="relative">
                                            <Search
                                                className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400"
                                                aria-hidden="true"
                                            />
                                            <input
                                                value={filters.search}
                                                onChange={(event) =>
                                                    setFilters((current) => ({
                                                        ...current,
                                                        search: event.target.value,
                                                    }))
                                                }
                                                placeholder="Search product, variant, feed ID or numeric ID"
                                                className="block min-h-10 w-full rounded-lg border border-neutral-300 bg-white py-2 pl-9 pr-3 text-sm text-neutral-900 outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:border-neutral-500 dark:focus:ring-neutral-800"
                                            />
                                        </div>
                                    </label>

                                    <SelectFilter
                                        label="Readiness"
                                        value={filters.status}
                                        options={workspace.options.statuses}
                                        onChange={(value) =>
                                            setFilters((current) => ({
                                                ...current,
                                                status: value,
                                            }))
                                        }
                                    />

                                    <SelectFilter
                                        label="Feed"
                                        value={filters.feed}
                                        options={workspace.options.feed_states}
                                        onChange={(value) =>
                                            setFilters((current) => ({
                                                ...current,
                                                feed: value,
                                            }))
                                        }
                                    />

                                    <SelectFilter
                                        label="Finding severity"
                                        value={filters.severity}
                                        options={workspace.options.severities}
                                        onChange={(value) =>
                                            setFilters((current) => ({
                                                ...current,
                                                severity: value,
                                            }))
                                        }
                                    />

                                    <SelectFilter
                                        label="Availability"
                                        value={filters.availability}
                                        options={workspace.options.availabilities}
                                        onChange={(value) =>
                                            setFilters((current) => ({
                                                ...current,
                                                availability: value,
                                            }))
                                        }
                                    />

                                    <SelectFilter
                                        label="Identifier state"
                                        value={filters.identifier_state}
                                        options={workspace.options.identifier_states}
                                        onChange={(value) =>
                                            setFilters((current) => ({
                                                ...current,
                                                identifier_state: value,
                                            }))
                                        }
                                    />

                                    <div className="flex items-end gap-2">
                                        <button
                                            type="submit"
                                            className="inline-flex min-h-10 flex-1 items-center justify-center rounded-lg bg-neutral-950 px-4 text-sm font-semibold text-white transition hover:bg-neutral-800 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200"
                                        >
                                            Apply filters
                                        </button>

                                        <button
                                            type="button"
                                            onClick={clearFilters}
                                            className="inline-flex min-h-10 items-center justify-center rounded-lg border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-200 dark:hover:bg-neutral-900"
                                        >
                                            Reset
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div className="flex flex-col gap-2 border-b border-neutral-200 px-4 py-3 text-sm text-neutral-500 dark:border-neutral-800 dark:text-neutral-400 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                                <p>
                                    {workspace.pagination.total === 0
                                        ? 'No matching merchant items'
                                        : `Showing ${formatNumber(workspace.pagination.from ?? 0)}–${formatNumber(workspace.pagination.to ?? 0)} of ${formatNumber(workspace.pagination.total)}`}
                                </p>

                                <p>
                                    Policy {workspace.policy_version}
                                </p>
                            </div>

                            {workspace.items.length === 0 ? (
                                <EmptyState />
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="min-w-[1050px] w-full divide-y divide-neutral-200 text-sm dark:divide-neutral-800">
                                        <thead className="bg-neutral-50 dark:bg-neutral-900/70">
                                            <tr className="text-left text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                                                <th className="px-5 py-3">
                                                    Product / item
                                                </th>
                                                <th className="px-5 py-3">
                                                    Readiness
                                                </th>
                                                <th className="px-5 py-3">
                                                    Feed
                                                </th>
                                                <th className="px-5 py-3">
                                                    Signals
                                                </th>
                                                <th className="px-5 py-3">
                                                    Findings
                                                </th>
                                                <th className="px-5 py-3 text-right">
                                                    Action
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody className="divide-y divide-neutral-200 bg-white align-top dark:divide-neutral-800 dark:bg-neutral-950">
                                            {workspace.items.map((item) => (
                                                <ReadinessRow
                                                    key={`${item.product_id}:${item.variant_id ?? 'product'}`}
                                                    item={item}
                                                />
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}

                            <Pagination
                                workspace={workspace}
                                filters={filters}
                                onNavigate={navigate}
                            />
                        </section>
                    </main>

                    <aside className="space-y-6">
                        <PolicyReadinessCard
                            workspace={workspace}
                        />

                        <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                            <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">
                                Most frequent findings
                            </h2>
                            <p className="mt-1 text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                                Counts are from the full bounded M.4A catalog, not only the current page.
                            </p>

                            {topFindings.length === 0 ? (
                                <div className="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-900/60 dark:bg-emerald-950/20 dark:text-emerald-300">
                                    No readiness findings in the current catalog.
                                </div>
                            ) : (
                                <div className="mt-4 space-y-3">
                                    {topFindings.map(([id, count]) => (
                                        <div
                                            key={id}
                                            className="flex items-start justify-between gap-3 border-b border-neutral-100 pb-3 last:border-0 last:pb-0 dark:border-neutral-900"
                                        >
                                            <span className="min-w-0 break-words text-sm text-neutral-700 dark:text-neutral-300">
                                                {humanize(id)}
                                            </span>
                                            <span className="shrink-0 rounded-full bg-neutral-100 px-2.5 py-1 text-xs font-semibold text-neutral-700 dark:bg-neutral-900 dark:text-neutral-300">
                                                {formatNumber(count)}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </section>
                    </aside>
                </div>

                <ExportOperations
                    exports={exports}
                    retryingExport={retryingExport}
                    onRetry={retryExport}
                    onNavigate={navigateExports}
                />
            </div>
        </AdminLayout>
    )
}

function ExportOperations({
    exports,
    retryingExport,
    onRetry,
    onNavigate,
}: {
    exports: MerchantFeedExportHistory
    retryingExport: string | null
    onRetry: (publicId: string) => void
    onNavigate: (page: number) => void
}) {
    return (
        <section className="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <div className="flex flex-col gap-3 border-b border-neutral-200 px-4 py-4 dark:border-neutral-800 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <div>
                    <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">
                        Merchant feed export history
                    </h2>
                    <p className="mt-1 text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                        Private queued TSV exports. Downloads are authorized, integrity-checked and never exposed as public storage URLs.
                    </p>
                </div>

                <span className="shrink-0 rounded-full bg-neutral-100 px-3 py-1 text-xs font-semibold text-neutral-700 dark:bg-neutral-900 dark:text-neutral-300">
                    {formatNumber(exports.pagination.total)} exports
                </span>
            </div>

            {exports.items.length === 0 ? (
                <div className="px-6 py-12 text-center">
                    <FileSpreadsheet
                        className="mx-auto h-8 w-8 text-neutral-400"
                        aria-hidden="true"
                    />
                    <h3 className="mt-3 font-semibold text-neutral-950 dark:text-neutral-100">
                        No merchant feed exports yet
                    </h3>
                    <p className="mx-auto mt-1 max-w-md text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                        Use Export TSV above to snapshot the current bounded merchant catalog and queue a private feed file.
                    </p>
                </div>
            ) : (
                <div className="overflow-x-auto">
                    <table className="min-w-[1180px] w-full divide-y divide-neutral-200 text-sm dark:divide-neutral-800">
                        <thead className="bg-neutral-50 dark:bg-neutral-900/70">
                            <tr className="text-left text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                                <th className="px-5 py-3">Requested</th>
                                <th className="px-5 py-3">Status</th>
                                <th className="px-5 py-3">Snapshot</th>
                                <th className="px-5 py-3">Counts</th>
                                <th className="px-5 py-3">Result</th>
                                <th className="px-5 py-3 text-right">Action</th>
                            </tr>
                        </thead>

                        <tbody className="divide-y divide-neutral-200 bg-white align-top dark:divide-neutral-800 dark:bg-neutral-950">
                            {exports.items.map((item) => (
                                <ExportHistoryRow
                                    key={item.public_id}
                                    item={item}
                                    retrying={retryingExport === item.public_id}
                                    onRetry={onRetry}
                                />
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            <ExportPagination
                exports={exports}
                onNavigate={onNavigate}
            />
        </section>
    )
}

function ExportHistoryRow({
    item,
    retrying,
    onRetry,
}: {
    item: MerchantFeedExportHistoryItem
    retrying: boolean
    onRetry: (publicId: string) => void
}) {
    return (
        <tr>
            <td className="px-5 py-4">
                <p className="font-semibold text-neutral-950 dark:text-neutral-100">
                    {formatDateTime(item.created_at)}
                </p>
                <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                    {item.requested_by.name}
                </p>
                <p className="mt-1 max-w-[230px] truncate text-xs text-neutral-400 dark:text-neutral-500">
                    {item.public_id}
                </p>
            </td>

            <td className="px-5 py-4">
                <ExportStatusBadge status={item.status} />
                <p className="mt-2 text-xs text-neutral-500 dark:text-neutral-400">
                    Attempt {formatNumber(item.attempt_count)}
                </p>
            </td>

            <td className="px-5 py-4 text-xs leading-5 text-neutral-600 dark:text-neutral-300">
                <p>{item.schema_version}</p>
                <p className="mt-1">Readiness {item.readiness_policy_version}</p>
                <p>Merchant policy v{item.merchant_policy_version}</p>
                {item.truncated && (
                    <p className="mt-1 font-semibold text-amber-700 dark:text-amber-300">
                        Bounded at {formatNumber(item.max_items)} items
                    </p>
                )}
            </td>

            <td className="px-5 py-4">
                <dl className="space-y-1.5 text-xs">
                    <SignalRow label="Exportable" value={formatNumber(item.exportable_count)} />
                    <SignalRow label="Excluded" value={formatNumber(item.excluded_count)} />
                    <SignalRow label="Blockers" value={formatNumber(item.blocker_count)} />
                    <SignalRow label="Warnings" value={formatNumber(item.warning_count)} />
                </dl>
            </td>

            <td className="max-w-[330px] px-5 py-4">
                {item.status === 'completed' && item.file.name ? (
                    <div>
                        <p className="font-medium text-neutral-800 dark:text-neutral-200">
                            {item.file.name}
                        </p>
                        <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                            {item.file.size === null ? 'Unknown size' : formatBytes(item.file.size)}
                        </p>
                        {item.completed_at && (
                            <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                Completed {formatDateTime(item.completed_at)}
                            </p>
                        )}
                    </div>
                ) : item.status === 'failed' ? (
                    <div>
                        <p className="font-semibold text-red-700 dark:text-red-300">
                            {item.failure.code ? humanize(item.failure.code) : 'Export failed'}
                        </p>
                        {item.failure.message && (
                            <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                                {item.failure.message}
                            </p>
                        )}
                        {item.failure.code === 'snapshot_changed' && (
                            <p className="mt-2 text-xs font-medium text-amber-700 dark:text-amber-300">
                                Request a new export to capture current catalog data.
                            </p>
                        )}
                    </div>
                ) : (
                    <p className="text-xs text-neutral-500 dark:text-neutral-400">
                        {item.status === 'processing'
                            ? 'Worker is generating and verifying the private TSV file.'
                            : 'Waiting for the merchant export queue worker.'}
                    </p>
                )}
            </td>

            <td className="px-5 py-4 text-right">
                <div className="flex justify-end gap-2">
                    {item.can_download && (
                        <a
                            href={route(
                                'admin.seo.merchant.exports.download',
                                item.public_id,
                            )}
                            className="inline-flex min-h-9 items-center gap-2 rounded-lg bg-neutral-950 px-3 text-xs font-semibold text-white transition hover:bg-neutral-800 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200"
                        >
                            <Download
                                className="h-4 w-4"
                                aria-hidden="true"
                            />
                            Download
                        </a>
                    )}

                    {item.can_retry && (
                        <button
                            type="button"
                            disabled={retrying}
                            onClick={() => onRetry(item.public_id)}
                            className="inline-flex min-h-9 items-center gap-2 rounded-lg border border-neutral-300 px-3 text-xs font-semibold text-neutral-700 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-900"
                        >
                            <RotateCcw
                                className="h-4 w-4"
                                aria-hidden="true"
                            />
                            {retrying ? 'Queueing…' : 'Retry'}
                        </button>
                    )}
                </div>
            </td>
        </tr>
    )
}

function ExportPagination({
    exports,
    onNavigate,
}: {
    exports: MerchantFeedExportHistory
    onNavigate: (page: number) => void
}) {
    if (
        exports.pagination.total === 0
        || exports.pagination.last_page <= 1
    ) {
        return null
    }

    return (
        <div className="flex flex-col gap-3 border-t border-neutral-200 px-4 py-4 dark:border-neutral-800 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                Showing {formatNumber(exports.pagination.from ?? 0)}–{formatNumber(exports.pagination.to ?? 0)} of {formatNumber(exports.pagination.total)} exports
            </p>

            <div className="flex gap-2">
                <button
                    type="button"
                    disabled={exports.pagination.previous_page === null}
                    onClick={() => {
                        if (exports.pagination.previous_page !== null) {
                            onNavigate(exports.pagination.previous_page)
                        }
                    }}
                    className="inline-flex min-h-9 items-center gap-2 rounded-lg border border-neutral-300 px-3 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-900"
                >
                    <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                    Previous
                </button>

                <button
                    type="button"
                    disabled={exports.pagination.next_page === null}
                    onClick={() => {
                        if (exports.pagination.next_page !== null) {
                            onNavigate(exports.pagination.next_page)
                        }
                    }}
                    className="inline-flex min-h-9 items-center gap-2 rounded-lg border border-neutral-300 px-3 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-900"
                >
                    Next
                    <ArrowRight className="h-4 w-4" aria-hidden="true" />
                </button>
            </div>
        </div>
    )
}

function ExportStatusBadge({
    status,
}: {
    status: MerchantFeedExportStatus
}) {
    const styles: Record<MerchantFeedExportStatus, string> = {
        queued:
            'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/60 dark:bg-blue-950/20 dark:text-blue-300',
        processing:
            'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-900/60 dark:bg-violet-950/20 dark:text-violet-300',
        completed:
            'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/20 dark:text-emerald-300',
        failed:
            'border-red-200 bg-red-50 text-red-700 dark:border-red-900/60 dark:bg-red-950/20 dark:text-red-300',
    }

    return (
        <span className={`inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold ${styles[status]}`}>
            {humanize(status)}
        </span>
    )
}

function ReadinessRow({
    item,
}: {
    item: MerchantReadinessItem
}) {
    return (
        <tr>
            <td className="px-5 py-4">
                <p className="font-semibold text-neutral-950 dark:text-neutral-100">
                    {item.product_name}
                </p>

                {item.variant_label && (
                    <p className="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                        {item.variant_label}
                    </p>
                )}

                <div className="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-neutral-500 dark:text-neutral-400">
                    <span>
                        Product #{item.product_id}
                    </span>
                    {item.variant_id !== null && (
                        <span>
                            Variant #{item.variant_id}
                        </span>
                    )}
                    {item.feed_id && (
                        <span className="font-mono">
                            {item.feed_id}
                        </span>
                    )}
                </div>
            </td>

            <td className="px-5 py-4">
                <StatusBadge status={item.status} />
            </td>

            <td className="px-5 py-4">
                <BooleanBadge
                    value={item.feed_eligible}
                    trueLabel="Eligible"
                    falseLabel="Excluded"
                />
            </td>

            <td className="px-5 py-4">
                <dl className="space-y-2 text-xs">
                    <SignalRow
                        label="Availability"
                        value={item.availability ? humanize(item.availability) : 'Unknown'}
                    />
                    <SignalRow
                        label="Identifiers"
                        value={item.identifier_state ? humanize(item.identifier_state) : 'Unknown'}
                    />
                </dl>
            </td>

            <td className="max-w-[420px] px-5 py-4">
                {item.findings.length === 0 ? (
                    <span className="text-sm text-emerald-700 dark:text-emerald-300">
                        No findings
                    </span>
                ) : (
                    <details>
                        <summary className="cursor-pointer select-none text-sm font-semibold text-neutral-800 marker:text-neutral-400 dark:text-neutral-200">
                            {item.findings.length} {item.findings.length === 1 ? 'finding' : 'findings'}
                        </summary>

                        <div className="mt-3 space-y-3">
                            {item.findings.map((finding) => (
                                <FindingCard
                                    key={finding.id}
                                    finding={finding}
                                />
                            ))}
                        </div>
                    </details>
                )}
            </td>

            <td className="px-5 py-4 text-right">
                <Link
                    href={route(
                        'admin.products.edit',
                        item.product_id,
                    )}
                    className="inline-flex min-h-9 items-center justify-center rounded-lg border border-neutral-300 px-3 text-xs font-semibold text-neutral-700 transition hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-900"
                >
                    Edit product
                </Link>
            </td>
        </tr>
    )
}

function FindingCard({
    finding,
}: {
    finding: MerchantFinding
}) {
    return (
        <div className="rounded-lg border border-neutral-200 p-3 dark:border-neutral-800">
            <div className="flex flex-wrap items-center gap-2">
                <SeverityBadge
                    severity={finding.severity}
                />
                <span className="text-xs font-medium text-neutral-500 dark:text-neutral-400">
                    {humanize(finding.category)}
                </span>
            </div>

            <p className="mt-2 text-sm leading-5 text-neutral-800 dark:text-neutral-200">
                {finding.message}
            </p>

            <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                {finding.action}
            </p>
        </div>
    )
}

function PolicyReadinessCard({
    workspace,
}: {
    workspace: MerchantReadinessWorkspace
}) {
    const policy = workspace.policy

    return (
        <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">
                        Merchant policy readiness
                    </h2>
                    <p className="mt-1 text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                        Publication state from the existing M.2 policy resolver.
                    </p>
                </div>

                <span className="rounded-full bg-neutral-100 px-2.5 py-1 text-xs font-semibold text-neutral-600 dark:bg-neutral-900 dark:text-neutral-300">
                    v{policy.policy_version}
                </span>
            </div>

            <div className="mt-4 space-y-3">
                <PolicyRow
                    label="Structured data"
                    value={policy.structured_data_enabled}
                    enabledLabel="Enabled"
                    disabledLabel="Disabled"
                />
                <PolicyRow
                    label="Shipping markup"
                    value={policy.shipping_published}
                    enabledLabel="Published"
                    disabledLabel={policy.shipping_markup_enabled ? 'Omitted' : 'Disabled'}
                />
                <PolicyRow
                    label="Return policy"
                    value={policy.return_policy_published}
                    enabledLabel="Published"
                    disabledLabel={policy.return_policy_enabled ? 'Omitted' : 'Disabled'}
                />
            </div>

            {policy.shipping_omission_reasons.length > 0 && (
                <div className="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-900/60 dark:bg-amber-950/20">
                    <p className="text-xs font-semibold uppercase tracking-wide text-amber-900 dark:text-amber-200">
                        Shipping omission reasons
                    </p>
                    <ul className="mt-2 list-disc space-y-1 pl-4 text-xs leading-5 text-amber-800 dark:text-amber-300">
                        {policy.shipping_omission_reasons.map((reason) => (
                            <li key={reason}>
                                {reason}
                            </li>
                        ))}
                    </ul>
                </div>
            )}

            <Link
                href={route(
                    'admin.seo.merchant.policies.index',
                )}
                className="mt-4 inline-flex min-h-9 w-full items-center justify-center rounded-lg border border-neutral-300 px-3 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-900"
            >
                Review merchant policy
            </Link>
        </section>
    )
}

function MetricCard({
    label,
    value,
    helper,
    icon,
}: {
    label: string
    value: number
    helper: string
    icon: ReactNode
}) {
    return (
        <section className="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                        {label}
                    </p>
                    <p className="mt-2 text-2xl font-bold tracking-tight text-neutral-950 dark:text-neutral-100">
                        {formatNumber(value)}
                    </p>
                </div>

                <div className="rounded-lg bg-neutral-100 p-2 text-neutral-600 dark:bg-neutral-900 dark:text-neutral-300">
                    {icon}
                </div>
            </div>
            <p className="mt-2 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                {helper}
            </p>
        </section>
    )
}

function SelectFilter({
    label,
    value,
    options,
    onChange,
}: {
    label: string
    value: string
    options: MerchantReadinessOption[]
    onChange: (value: string) => void
}) {
    return (
        <label>
            <span className="mb-1.5 block text-xs font-semibold text-neutral-600 dark:text-neutral-300">
                {label}
            </span>
            <select
                value={value}
                onChange={(event) =>
                    onChange(
                        event.target.value,
                    )
                }
                className="block min-h-10 w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:border-neutral-500 dark:focus:ring-neutral-800"
            >
                <option value="">
                    All
                </option>
                {options.map((option) => (
                    <option
                        key={option.value}
                        value={option.value}
                    >
                        {option.label}
                    </option>
                ))}
            </select>
        </label>
    )
}

function Pagination({
    workspace,
    filters,
    onNavigate,
}: {
    workspace: MerchantReadinessWorkspace
    filters: FilterState
    onNavigate: (
        filters: FilterState,
        page: number,
    ) => void
}) {
    if (
        workspace.pagination.total === 0
        || workspace.pagination.last_page <= 1
    ) {
        return null
    }

    return (
        <div className="flex flex-col gap-3 border-t border-neutral-200 px-4 py-4 dark:border-neutral-800 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                Page {workspace.pagination.current_page} of {workspace.pagination.last_page}
            </p>

            <div className="flex gap-2">
                <button
                    type="button"
                    disabled={workspace.pagination.previous_page === null}
                    onClick={() => {
                        if (workspace.pagination.previous_page !== null) {
                            onNavigate(
                                filters,
                                workspace.pagination.previous_page,
                            )
                        }
                    }}
                    className="inline-flex min-h-9 items-center gap-2 rounded-lg border border-neutral-300 px-3 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-900"
                >
                    <ArrowLeft
                        className="h-4 w-4"
                        aria-hidden="true"
                    />
                    Previous
                </button>

                <button
                    type="button"
                    disabled={workspace.pagination.next_page === null}
                    onClick={() => {
                        if (workspace.pagination.next_page !== null) {
                            onNavigate(
                                filters,
                                workspace.pagination.next_page,
                            )
                        }
                    }}
                    className="inline-flex min-h-9 items-center gap-2 rounded-lg border border-neutral-300 px-3 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-900"
                >
                    Next
                    <ArrowRight
                        className="h-4 w-4"
                        aria-hidden="true"
                    />
                </button>
            </div>
        </div>
    )
}

function EmptyState() {
    return (
        <div className="px-6 py-16 text-center">
            <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 text-neutral-500 dark:bg-neutral-900 dark:text-neutral-400">
                <Search
                    className="h-5 w-5"
                    aria-hidden="true"
                />
            </div>
            <h3 className="mt-4 font-semibold text-neutral-950 dark:text-neutral-100">
                No merchant items match these filters
            </h3>
            <p className="mx-auto mt-1 max-w-md text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                Clear one or more filters to return to the bounded M.4A readiness catalog.
            </p>
        </div>
    )
}

function StatusBadge({
    status,
}: {
    status: MerchantReadinessStatus
}) {
    const styles: Record<MerchantReadinessStatus, string> = {
        ready:
            'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/20 dark:text-emerald-300',
        needs_work:
            'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/20 dark:text-amber-300',
        blocked:
            'border-red-200 bg-red-50 text-red-700 dark:border-red-900/60 dark:bg-red-950/20 dark:text-red-300',
    }

    return (
        <span className={`inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold ${styles[status]}`}>
            {status === 'ready'
                ? 'Ready'
                : status === 'needs_work'
                    ? 'Needs work'
                    : 'Blocked'}
        </span>
    )
}

function SeverityBadge({
    severity,
}: {
    severity: MerchantFindingSeverity
}) {
    const styles: Record<MerchantFindingSeverity, string> = {
        blocker:
            'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-300',
        warning:
            'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300',
        info:
            'bg-blue-100 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300',
    }

    return (
        <span className={`rounded-full px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide ${styles[severity]}`}>
            {severity}
        </span>
    )
}

function BooleanBadge({
    value,
    trueLabel,
    falseLabel,
}: {
    value: boolean
    trueLabel: string
    falseLabel: string
}) {
    return (
        <span className={[
            'inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold',
            value
                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/20 dark:text-emerald-300'
                : 'border-neutral-200 bg-neutral-100 text-neutral-600 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-300',
        ].join(' ')}>
            {value ? trueLabel : falseLabel}
        </span>
    )
}

function SignalRow({
    label,
    value,
}: {
    label: string
    value: string
}) {
    return (
        <div className="flex items-start justify-between gap-3">
            <dt className="text-neutral-500 dark:text-neutral-400">
                {label}
            </dt>
            <dd className="max-w-[150px] text-right font-medium text-neutral-800 dark:text-neutral-200">
                {value}
            </dd>
        </div>
    )
}

function PolicyRow({
    label,
    value,
    enabledLabel,
    disabledLabel,
}: {
    label: string
    value: boolean
    enabledLabel: string
    disabledLabel: string
}) {
    return (
        <div className="flex items-center justify-between gap-3 rounded-lg border border-neutral-200 px-3 py-2.5 dark:border-neutral-800">
            <span className="text-sm text-neutral-700 dark:text-neutral-300">
                {label}
            </span>
            <BooleanBadge
                value={value}
                trueLabel={enabledLabel}
                falseLabel={disabledLabel}
            />
        </div>
    )
}

function fromServerFilters(
    filters: MerchantReadinessFilters,
): FilterState {
    return {
        search: filters.search,
        status: filters.status ?? '',
        feed: filters.feed ?? '',
        availability: filters.availability ?? '',
        identifier_state: filters.identifier_state ?? '',
        severity: filters.severity ?? '',
    }
}

function compactQuery(
    filters: FilterState,
    page: number,
): Record<string, string | number> {
    const query: Record<string, string | number> = {}

    for (const [key, value] of Object.entries(filters)) {
        const trimmed = value.trim()

        if (trimmed !== '') {
            query[key] = trimmed
        }
    }

    if (page > 1) {
        query.page = page
    }

    return query
}

function humanize(value: string): string {
    return value
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (character) =>
            character.toUpperCase(),
        )
}

function formatDateTime(value: string): string {
    return new Intl.DateTimeFormat(
        undefined,
        {
            dateStyle: 'medium',
            timeStyle: 'short',
        },
    ).format(
        new Date(value),
    )
}

function formatBytes(value: number): string {
    if (value < 1024) {
        return `${formatNumber(value)} B`
    }

    const units = [
        'KB',
        'MB',
        'GB',
    ]

    let amount =
        value / 1024

    let unit =
        units[0]

    for (
        let index = 1;
        index < units.length && amount >= 1024;
        index += 1
    ) {
        amount /= 1024
        unit = units[index]
    }

    return `${amount.toFixed(amount >= 10 ? 0 : 1)} ${unit}`
}

function formatNumber(value: number): string {
    return new Intl.NumberFormat().format(value)
}
