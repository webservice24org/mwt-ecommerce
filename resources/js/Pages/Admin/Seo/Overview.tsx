import AdminLayout from '@/Layouts/Admin/AdminLayout'
import { Head, Link, router } from '@inertiajs/react'
import {
    Activity,
    AlertTriangle,
    BarChart3,
    Bot,
    ChartNoAxesColumn,
    CheckCircle2,
    Filter,
    LayoutGrid,
    LineChart,
    Search,
    ShieldCheck,
    Sparkles,
    TrendingUp,
} from 'lucide-react'
import { useMemo, useState } from 'react'

type Summary = {
    entity_scope: string
    tracked_entities: number
    average_score: number
    coverage_percent: number
    critical_findings: number
    warning_findings: number
    info_findings: number
    completed_enrichments: number
    failed_enrichments: number
    latest_analysis_at: string | null
}

type CoverageRow = {
    label: string
    total: number
    analyzed: number
    percent: number
}

type ScoreBand = {
    label: string
    min: number
    max: number
    count: number
}

type GradeRow = {
    grade: string
    label: string
    count: number
}

type EntityAverageRow = {
    entity_type: string
    label: string
    average_score: number
    count: number
}

type CategoryRow = {
    category: string
    label: string
    count: number
}

type EnrichmentStatusRow = {
    status: string
    label: string
    count: number
}

type AnalysisRow = {
    analysis_id: number
    entity_type: string
    entity_type_label: string
    entity_id: number
    score: number
    grade: string
    finding_count: number
    analyzed_at: string | null
    has_enrichment: boolean
    enrichment_status: string | null
}

type IssueHotspot = {
    finding_id: string
    title: string
    category: string
    severity: string
    count: number
}

type FilterOption = {
    value: string
    label: string
}

type Props = {
    dashboard: {
        summary: Summary
        coverage: {
            products: CoverageRow
            categories: CoverageRow
            brands: CoverageRow
            pages: CoverageRow
        }
        charts: {
            score_bands: ScoreBand[]
            grade_distribution: GradeRow[]
            entity_type_averages: EntityAverageRow[]
            finding_categories: CategoryRow[]
            enrichment_statuses: EnrichmentStatusRow[]
        }
        classic: {
            top_entities: AnalysisRow[]
            recent_entities: AnalysisRow[]
            issue_hotspots: IssueHotspot[]
        }
        filters: {
            entity_type: string | null
            available_entity_types: FilterOption[]
        }
    }
}

type ViewMode =
    | 'classic'
    | 'graphical'

export default function Overview({
    dashboard,
}: Props) {
    const [view, setView] =
        useState<ViewMode>(
            'classic',
        )

    const summaryCards =
        useMemo(
            () => [
                {
                    title: 'Average SEO score',
                    value: `${dashboard.summary.average_score}/100`,
                    note: `${dashboard.summary.tracked_entities} latest entity analyses`,
                    icon: TrendingUp,
                },
                {
                    title: 'Coverage',
                    value: `${dashboard.summary.coverage_percent}%`,
                    note: 'Entity analysis coverage across tracked SEO entities',
                    icon: Activity,
                },
                {
                    title: 'Critical findings',
                    value: String(
                        dashboard.summary.critical_findings,
                    ),
                    note: `${dashboard.summary.warning_findings} warnings • ${dashboard.summary.info_findings} info`,
                    icon: AlertTriangle,
                },
                {
                    title: 'AI enrichment',
                    value: String(
                        dashboard.summary.completed_enrichments,
                    ),
                    note: `${dashboard.summary.failed_enrichments} failed enrichments`,
                    icon: Bot,
                },
            ],
            [dashboard.summary],
        )

    const maxScoreBand =
        Math.max(
            1,
            ...dashboard.charts.score_bands.map(
                (
                    item,
                ) => item.count,
            ),
        )

    const maxCategoryCount =
        Math.max(
            1,
            ...dashboard.charts.finding_categories.map(
                (
                    item,
                ) => item.count,
            ),
        )

    const maxEntityAverage =
        Math.max(
            1,
            ...dashboard.charts.entity_type_averages.map(
                (
                    item,
                ) =>
                    item.average_score,
            ),
        )

    const maxGradeCount =
        Math.max(
            1,
            ...dashboard.charts.grade_distribution.map(
                (
                    item,
                ) => item.count,
            ),
        )

    const maxEnrichmentCount =
        Math.max(
            1,
            ...dashboard.charts.enrichment_statuses.map(
                (
                    item,
                ) => item.count,
            ),
        )

    const applyEntityTypeFilter = (
        value: string,
    ) => {
        router.get(
            route(
                'admin.seo.overview',
            ),
            {
                entity_type:
                    value === ''
                        ? undefined
                        : value,
            },
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            },
        )
    }

    return (
        <AdminLayout title=''>
            <Head title="SEO Overview" />

            <div className="space-y-6">
                <PageHeader
                    view={view}
                    setView={setView}
                />

                <WorkspaceNotice />

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
                    <div className="min-w-0 space-y-6">
                        <FilterCard
                            options={
                                dashboard.filters
                                    .available_entity_types
                            }
                            value={
                                dashboard.filters
                                    .entity_type ??
                                ''
                            }
                            onChange={
                                applyEntityTypeFilter
                            }
                        />

                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            {summaryCards.map(
                                (
                                    card,
                                ) => (
                                    <SummaryCard
                                        key={
                                            card.title
                                        }
                                        {...card}
                                    />
                                ),
                            )}
                        </div>

                        {view ===
                        'classic' ? (
                            <>
                                <SectionCard
                                    icon={LayoutGrid}
                                    title="Coverage by entity type"
                                    description="Classic operational view of how much of the catalog and content library is already covered by AI SEO analysis."
                                >
                                    <CoverageGrid
                                        coverage={
                                            dashboard.coverage
                                        }
                                    />
                                </SectionCard>

                                <SectionCard
                                    icon={Search}
                                    title="Issue hotspots"
                                    description="The most repeated deterministic SEO findings across the latest entity analyses."
                                >
                                    <IssueHotspotsTable
                                        rows={
                                            dashboard.classic
                                                .issue_hotspots
                                        }
                                    />
                                </SectionCard>

                                <div className="grid gap-6 xl:grid-cols-2">
                                    <SectionCard
                                        icon={TrendingUp}
                                        title="Top-scoring entities"
                                        description="Best current SEO performers from the latest analysis for each entity."
                                    >
                                        <AnalysisTable
                                            rows={
                                                dashboard.classic
                                                    .top_entities
                                            }
                                        />
                                    </SectionCard>

                                    <SectionCard
                                        icon={Activity}
                                        title="Recently analyzed entities"
                                        description="Newest analysis history across products, categories, brands and pages."
                                    >
                                        <AnalysisTable
                                            rows={
                                                dashboard.classic
                                                    .recent_entities
                                            }
                                        />
                                    </SectionCard>
                                </div>
                            </>
                        ) : (
                            <>
                                <div className="grid gap-6 xl:grid-cols-2">
                                    <SectionCard
                                        icon={BarChart3}
                                        title="Score distribution"
                                        description="How the latest entity analyses distribute across score bands."
                                    >
                                        <SimpleBars
                                            rows={dashboard.charts.score_bands.map(
                                                (
                                                    item,
                                                ) => ({
                                                    label:
                                                        item.label,
                                                    value:
                                                        item.count,
                                                    max:
                                                        maxScoreBand,
                                                    note: `${item.count} entities`,
                                                }),
                                            )}
                                        />
                                    </SectionCard>

                                    <SectionCard
                                        icon={ChartNoAxesColumn}
                                        title="Grade distribution"
                                        description="Breakdown of current grades across the latest analysis per entity."
                                    >
                                        <SimpleBars
                                            rows={dashboard.charts.grade_distribution.map(
                                                (
                                                    item,
                                                ) => ({
                                                    label:
                                                        item.label,
                                                    value:
                                                        item.count,
                                                    max:
                                                        maxGradeCount,
                                                    note: `${item.count} entities`,
                                                }),
                                            )}
                                        />
                                    </SectionCard>
                                </div>

                                <div className="grid gap-6 xl:grid-cols-2">
                                    <SectionCard
                                        icon={LineChart}
                                        title="Average score by entity type"
                                        description="Useful for seeing whether products, categories, brands or pages need the most attention."
                                    >
                                        <SimpleBars
                                            rows={dashboard.charts.entity_type_averages.map(
                                                (
                                                    item,
                                                ) => ({
                                                    label:
                                                        item.label,
                                                    value:
                                                        item.average_score,
                                                    max:
                                                        maxEntityAverage,
                                                    note: `${item.average_score}/100 • ${item.count} entities`,
                                                }),
                                            )}
                                        />
                                    </SectionCard>

                                    <SectionCard
                                        icon={Sparkles}
                                        title="AI enrichment status"
                                        description="Where AI SEO explanations and action guidance already exist across the latest analyses."
                                    >
                                        <SimpleBars
                                            rows={dashboard.charts.enrichment_statuses.map(
                                                (
                                                    item,
                                                ) => ({
                                                    label:
                                                        item.label,
                                                    value:
                                                        item.count,
                                                    max:
                                                        maxEnrichmentCount,
                                                    note: `${item.count} entities`,
                                                }),
                                            )}
                                        />
                                    </SectionCard>
                                </div>

                                <SectionCard
                                    icon={AlertTriangle}
                                    title="Finding categories"
                                    description="Graphical view of the most frequent deterministic issue categories."
                                >
                                    <SimpleBars
                                        rows={dashboard.charts.finding_categories.map(
                                            (
                                                item,
                                            ) => ({
                                                label:
                                                    item.label,
                                                value:
                                                    item.count,
                                                max:
                                                    maxCategoryCount,
                                                note: `${item.count} findings`,
                                            }),
                                        )}
                                    />
                                </SectionCard>
                            </>
                        )}
                    </div>

                    <aside className="space-y-6 xl:sticky xl:top-6 xl:self-start">
                        <SectionCard
                            icon={ShieldCheck}
                            title="Workspace status"
                            description="This dashboard is read-only. Scores are deterministic, history is append-only, and AI enrichments cannot silently rewrite live SEO."
                        >
                            <WorkspaceStatus
                                summary={
                                    dashboard.summary
                                }
                                coverage={
                                    dashboard.coverage
                                }
                            />
                        </SectionCard>

                        <SectionCard
                            icon={Bot}
                            title="AI SEO workspace"
                            description="This overview is the first admin workspace layer on top of the 10.4I analysis engine."
                        >
                            <ul className="space-y-3 text-sm text-neutral-600 dark:text-neutral-300">
                                <li>
                                    • Classic
                                    view for
                                    operations,
                                    triage and
                                    quick review
                                </li>

                                <li>
                                    • Graphical
                                    view for
                                    trends,
                                    coverage
                                    and issue
                                    distribution
                                </li>

                                <li>
                                    • Next:
                                    AI
                                    suggestions,
                                    approval
                                    flows,
                                    dashboard
                                    filters and
                                    technical
                                    health
                                </li>
                            </ul>
                        </SectionCard>
                    </aside>
                </div>
            </div>
        </AdminLayout>
    )
}

function PageHeader({
    view,
    setView,
}: {
    view: ViewMode
    setView: (
        view: ViewMode,
    ) => void
}) {
    return (
        <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <div className="flex items-center gap-2 text-sm font-medium text-indigo-600 dark:text-indigo-400">
                    <Search className="h-4 w-4" />
                    Search optimization
                </div>

                <h1 className="mt-2 text-2xl font-bold tracking-tight text-neutral-950 dark:text-neutral-100">
                    SEO Overview
                </h1>

                <p className="mt-2 max-w-3xl text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                    A dedicated SEO dashboard for
                    classic operational review and
                    graphical trend analysis across
                    products, categories, brands
                    and pages.
                </p>
            </div>

            <div className="inline-flex rounded-xl border border-neutral-200 bg-white p-1 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                <ViewToggleButton
                    active={
                        view ===
                        'classic'
                    }
                    onClick={() =>
                        setView(
                            'classic',
                        )
                    }
                    icon={LayoutGrid}
                    label="Classic"
                />

                <ViewToggleButton
                    active={
                        view ===
                        'graphical'
                    }
                    onClick={() =>
                        setView(
                            'graphical',
                        )
                    }
                    icon={BarChart3}
                    label="Graphical"
                />
            </div>
        </div>
    )
}

function ViewToggleButton({
    active,
    onClick,
    icon: Icon,
    label,
}: {
    active: boolean
    onClick: () => void
    icon: typeof LayoutGrid
    label: string
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={[
                'inline-flex h-10 items-center justify-center gap-2 rounded-lg px-4 text-sm font-semibold transition',
                active
                    ? 'bg-neutral-950 text-white dark:bg-white dark:text-neutral-950'
                    : 'text-neutral-600 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-900',
            ].join(' ')}
        >
            <Icon className="h-4 w-4" />
            {label}
        </button>
    )
}

function WorkspaceNotice() {
    return (
        <div className="rounded-xl border border-indigo-200 bg-indigo-50/70 p-4 dark:border-indigo-900/60 dark:bg-indigo-950/20">
            <div className="flex items-start gap-3">
                <Sparkles className="mt-0.5 h-5 w-5 shrink-0 text-indigo-600 dark:text-indigo-400" />

                <div>
                    <p className="text-sm font-semibold text-indigo-950 dark:text-indigo-100">
                        SEO workspace foundation
                    </p>

                    <p className="mt-1 text-sm leading-6 text-indigo-800/80 dark:text-indigo-300/80">
                        The dashboard uses the
                        authoritative 10.4I analysis
                        engine. Graphs are derived
                        from deterministic scores and
                        findings; AI is limited to
                        enrichment and cannot
                        silently mutate live SEO.
                    </p>
                </div>
            </div>
        </div>
    )
}

function FilterCard({
    options,
    value,
    onChange,
}: {
    options: FilterOption[]
    value: string
    onChange: (
        value: string,
    ) => void
}) {
    return (
        <section className="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <div className="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                <div className="flex items-start gap-3">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-neutral-100 text-neutral-700 dark:bg-neutral-900 dark:text-neutral-300">
                        <Filter className="h-4 w-4" />
                    </div>

                    <div>
                        <h2 className="text-base font-semibold text-neutral-950 dark:text-neutral-100">
                            Dashboard filter
                        </h2>

                        <p className="mt-1 text-sm leading-5 text-neutral-500 dark:text-neutral-400">
                            Narrow the overview to a
                            single SEO entity family
                            or inspect the entire
                            workspace.
                        </p>
                    </div>
                </div>

                <select
                    value={value}
                    onChange={(
                        event,
                    ) =>
                        onChange(
                            event
                                .target
                                .value,
                        )
                    }
                    className="h-11 rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 shadow-sm transition focus:border-neutral-500 focus:outline-none focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:border-neutral-500 dark:focus:ring-neutral-800"
                >
                    {options.map(
                        (
                            option,
                        ) => (
                            <option
                                key={
                                    option.value
                                }
                                value={
                                    option.value
                                }
                            >
                                {
                                    option.label
                                }
                            </option>
                        ),
                    )}
                </select>
            </div>
        </section>
    )
}

function SummaryCard({
    title,
    value,
    note,
    icon: Icon,
}: {
    title: string
    value: string
    note: string
    icon: typeof TrendingUp
}) {
    return (
        <div className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                        {title}
                    </p>

                    <p className="mt-2 text-2xl font-bold tracking-tight text-neutral-950 dark:text-neutral-100">
                        {value}
                    </p>

                    <p className="mt-2 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                        {note}
                    </p>
                </div>

                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-neutral-100 text-neutral-700 dark:bg-neutral-900 dark:text-neutral-300">
                    <Icon className="h-5 w-5" />
                </div>
            </div>
        </div>
    )
}

function SectionCard({
    icon: Icon,
    title,
    description,
    children,
}: {
    icon: typeof Search
    title: string
    description: string
    children: React.ReactNode
}) {
    return (
        <section className="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <div className="border-b border-neutral-200 px-5 py-4 dark:border-neutral-800 sm:px-6">
                <div className="flex items-start gap-3">
                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-neutral-100 text-neutral-700 dark:bg-neutral-900 dark:text-neutral-300">
                        <Icon className="h-4 w-4" />
                    </div>

                    <div>
                        <h2 className="text-base font-semibold text-neutral-950 dark:text-neutral-100">
                            {title}
                        </h2>

                        <p className="mt-1 text-sm leading-5 text-neutral-500 dark:text-neutral-400">
                            {description}
                        </p>
                    </div>
                </div>
            </div>

            <div className="p-5 sm:p-6">
                {children}
            </div>
        </section>
    )
}

function CoverageGrid({
    coverage,
}: {
    coverage: Props['dashboard']['coverage']
}) {
    const items = [
        coverage.products,
        coverage.categories,
        coverage.brands,
        coverage.pages,
    ]

    return (
        <div className="grid gap-4 md:grid-cols-2">
            {items.map(
                (
                    item,
                ) => (
                    <div
                        key={
                            item.label
                        }
                        className="rounded-xl border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-900/60"
                    >
                        <div className="flex items-center justify-between gap-4">
                            <div>
                                <p className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                    {
                                        item.label
                                    }
                                </p>

                                <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                    {
                                        item.analyzed
                                    }{' '}
                                    analyzed of{' '}
                                    {
                                        item.total
                                    }{' '}
                                    total
                                </p>
                            </div>

                            <div className="text-right">
                                <p className="text-lg font-bold text-neutral-950 dark:text-neutral-100">
                                    {
                                        item.percent
                                    }
                                    %
                                </p>
                            </div>
                        </div>

                        <div className="mt-3 h-2 rounded-full bg-neutral-200 dark:bg-neutral-800">
                            <div
                                className="h-2 rounded-full bg-neutral-950 transition-all dark:bg-white"
                                style={{
                                    width: `${Math.max(
                                        0,
                                        Math.min(
                                            100,
                                            item.percent,
                                        ),
                                    )}%`,
                                }}
                            />
                        </div>
                    </div>
                ),
            )}
        </div>
    )
}

function SimpleBars({
    rows,
}: {
    rows: {
        label: string
        value: number
        max: number
        note: string
    }[]
}) {
    if (
        rows.length === 0
    ) {
        return (
            <EmptyState text="No chart data is available yet." />
        )
    }

    return (
        <div className="space-y-4">
            {rows.map(
                (
                    row,
                ) => {
                    const width =
                        row.max <= 0
                            ? 0
                            : Math.max(
                                  4,
                                  Math.round(
                                      (row.value /
                                          row.max) *
                                          100,
                                  ),
                              )

                    return (
                        <div
                            key={
                                row.label
                            }
                            className="space-y-2"
                        >
                            <div className="flex items-center justify-between gap-4 text-sm">
                                <div>
                                    <p className="font-medium text-neutral-900 dark:text-neutral-100">
                                        {
                                            row.label
                                        }
                                    </p>

                                    <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                        {
                                            row.note
                                        }
                                    </p>
                                </div>

                                <span className="font-semibold text-neutral-900 dark:text-neutral-100">
                                    {
                                        row.value
                                    }
                                </span>
                            </div>

                            <div className="h-2 rounded-full bg-neutral-200 dark:bg-neutral-800">
                                <div
                                    className="h-2 rounded-full bg-neutral-950 transition-all dark:bg-white"
                                    style={{
                                        width: `${width}%`,
                                    }}
                                />
                            </div>
                        </div>
                    )
                },
            )}
        </div>
    )
}

function IssueHotspotsTable({
    rows,
}: {
    rows: IssueHotspot[]
}) {
    if (
        rows.length === 0
    ) {
        return (
            <EmptyState text="No findings have been recorded yet." />
        )
    }

    return (
        <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-neutral-200 text-sm dark:divide-neutral-800">
                <thead>
                    <tr className="text-left text-xs uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                        <th className="pb-3 pr-4 font-medium">
                            Finding
                        </th>
                        <th className="pb-3 pr-4 font-medium">
                            Category
                        </th>
                        <th className="pb-3 pr-4 font-medium">
                            Severity
                        </th>
                        <th className="pb-3 font-medium">
                            Frequency
                        </th>
                    </tr>
                </thead>

                <tbody className="divide-y divide-neutral-100 dark:divide-neutral-900">
                    {rows.map(
                        (
                            row,
                        ) => (
                            <tr
                                key={
                                    row.finding_id
                                }
                            >
                                <td className="py-3 pr-4">
                                    <div>
                                        <p className="font-medium text-neutral-900 dark:text-neutral-100">
                                            {
                                                row.title
                                            }
                                        </p>

                                        <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                            {
                                                row.finding_id
                                            }
                                        </p>
                                    </div>
                                </td>

                                <td className="py-3 pr-4 text-neutral-600 dark:text-neutral-300">
                                    {row.category}
                                </td>

                                <td className="py-3 pr-4">
                                    <SeverityBadge
                                        severity={
                                            row.severity
                                        }
                                    />
                                </td>

                                <td className="py-3 font-semibold text-neutral-900 dark:text-neutral-100">
                                    {row.count}
                                </td>
                            </tr>
                        ),
                    )}
                </tbody>
            </table>
        </div>
    )
}

function AnalysisTable({
    rows,
}: {
    rows: AnalysisRow[]
}) {
    if (
        rows.length === 0
    ) {
        return (
            <EmptyState text="No analysis records are available yet." />
        )
    }

    return (
        <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-neutral-200 text-sm dark:divide-neutral-800">
                <thead>
                    <tr className="text-left text-xs uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                        <th className="pb-3 pr-4 font-medium">
                            Entity
                        </th>
                        <th className="pb-3 pr-4 font-medium">
                            Score
                        </th>
                        <th className="pb-3 pr-4 font-medium">
                            Grade
                        </th>
                        <th className="pb-3 pr-4 font-medium">
                            Findings
                        </th>
                        <th className="pb-3 pr-4 font-medium">
                            AI
                        </th>
                        <th className="pb-3 font-medium">
                            Analyzed
                        </th>
                    </tr>
                </thead>

                <tbody className="divide-y divide-neutral-100 dark:divide-neutral-900">
                    {rows.map(
                        (
                            row,
                        ) => (
                            <tr
                                key={`${row.analysis_id}-${row.entity_type}-${row.entity_id}`}
                            >
                                <td className="py-3 pr-4">
                                    <div>
                                        <Link
                                            href={route(
                                                'admin.seo.entities.show',
                                                {
                                                    entityType:
                                                        row.entity_type,
                                                    entityId:
                                                        row.entity_id,
                                                },
                                            )}
                                            className="font-medium text-neutral-900 hover:text-indigo-600 dark:text-neutral-100 dark:hover:text-indigo-400"
                                        >
                                            {
                                                row.entity_type_label
                                            }{' '}
                                            #
                                            {
                                                row.entity_id
                                            }
                                        </Link>

                                        <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                            Analysis #
                                            {
                                                row.analysis_id
                                            }
                                        </p>
                                    </div>
                                </td>

                                <td className="py-3 pr-4 font-semibold text-neutral-900 dark:text-neutral-100">
                                    {row.score}
                                </td>

                                <td className="py-3 pr-4">
                                    <GradeBadge
                                        grade={
                                            row.grade
                                        }
                                    />
                                </td>

                                <td className="py-3 pr-4 text-neutral-600 dark:text-neutral-300">
                                    {
                                        row.finding_count
                                    }
                                </td>

                                <td className="py-3 pr-4">
                                    {row.has_enrichment ? (
                                        <StatusBadge
                                            label={
                                                row.enrichment_status ??
                                                'yes'
                                            }
                                        />
                                    ) : (
                                        <StatusBadge label="none" />
                                    )}
                                </td>

                                <td className="py-3 text-neutral-600 dark:text-neutral-300">
                                    {formatDateTime(
                                        row.analyzed_at,
                                    )}
                                </td>
                            </tr>
                        ),
                    )}
                </tbody>
            </table>
        </div>
    )
}

function WorkspaceStatus({
    summary,
    coverage,
}: {
    summary: Summary
    coverage: Props['dashboard']['coverage']
}) {
    const items = [
        coverage.products,
        coverage.categories,
        coverage.brands,
        coverage.pages,
    ]

    return (
        <div className="space-y-5">
            <div className="rounded-xl border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-900/60">
                <div className="flex items-center justify-between gap-4">
                    <p className="text-sm font-medium text-neutral-600 dark:text-neutral-300">
                        Latest scope
                    </p>

                    <span className="rounded-full border border-neutral-200 bg-white px-2.5 py-1 text-xs font-semibold text-neutral-700 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-200">
                        {summary.entity_scope}
                    </span>
                </div>

                <p className="mt-3 text-lg font-bold text-neutral-950 dark:text-neutral-100">
                    {summary.coverage_percent}% workspace coverage
                </p>

                <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                    Last analysis:{' '}
                    {formatDateTime(
                        summary.latest_analysis_at,
                    )}
                </p>
            </div>

            <div className="space-y-3">
                {items.map(
                    (
                        item,
                    ) => (
                        <div
                            key={
                                item.label
                            }
                        >
                            <div className="mb-2 flex items-center justify-between gap-3 text-sm">
                                <span className="font-medium text-neutral-900 dark:text-neutral-100">
                                    {
                                        item.label
                                    }
                                </span>

                                <span className="text-neutral-500 dark:text-neutral-400">
                                    {
                                        item.analyzed
                                    }
                                    /
                                    {
                                        item.total
                                    }
                                </span>
                            </div>

                            <div className="h-2 rounded-full bg-neutral-200 dark:bg-neutral-800">
                                <div
                                    className="h-2 rounded-full bg-neutral-950 dark:bg-white"
                                    style={{
                                        width: `${Math.max(
                                            0,
                                            Math.min(
                                                100,
                                                item.percent,
                                            ),
                                        )}%`,
                                    }}
                                />
                            </div>
                        </div>
                    ),
                )}
            </div>

            <div className="space-y-2 text-sm text-neutral-600 dark:text-neutral-300">
                <p>
                    <span className="font-semibold text-neutral-900 dark:text-neutral-100">
                        {summary.completed_enrichments}
                    </span>{' '}
                    completed enrichments
                </p>

                <p>
                    <span className="font-semibold text-neutral-900 dark:text-neutral-100">
                        {summary.failed_enrichments}
                    </span>{' '}
                    failed enrichments
                </p>

                <p>
                    <span className="font-semibold text-neutral-900 dark:text-neutral-100">
                        {summary.critical_findings}
                    </span>{' '}
                    critical findings need attention
                </p>
            </div>
        </div>
    )
}

function EmptyState({
    text,
}: {
    text: string
}) {
    return (
        <div className="rounded-xl border border-dashed border-neutral-300 bg-neutral-50 px-4 py-6 text-sm text-neutral-500 dark:border-neutral-700 dark:bg-neutral-900/60 dark:text-neutral-400">
            {text}
        </div>
    )
}

function GradeBadge({
    grade,
}: {
    grade: string
}) {
    return (
        <span className="inline-flex items-center rounded-full border border-neutral-200 bg-neutral-50 px-2.5 py-1 text-xs font-medium capitalize text-neutral-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200">
            {grade.replaceAll(
                '_',
                ' ',
            )}
        </span>
    )
}

function SeverityBadge({
    severity,
}: {
    severity: string
}) {
    return (
        <span className="inline-flex items-center rounded-full border border-neutral-200 bg-neutral-50 px-2.5 py-1 text-xs font-medium capitalize text-neutral-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200">
            {severity}
        </span>
    )
}

function StatusBadge({
    label,
}: {
    label: string
}) {
    const normalized =
        label.toLowerCase()

    const icon =
        normalized ===
            'completed' ? (
            <CheckCircle2 className="h-3.5 w-3.5" />
        ) : (
            <Sparkles className="h-3.5 w-3.5" />
        )

    return (
        <span className="inline-flex items-center gap-1 rounded-full border border-neutral-200 bg-neutral-50 px-2.5 py-1 text-xs font-medium capitalize text-neutral-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200">
            {icon}
            {normalized}
        </span>
    )
}

function formatDateTime(
    value: string | null,
) {
    if (
        value === null ||
        value === ''
    ) {
        return '—'
    }

    const date =
        new Date(
            value,
        )

    if (
        Number.isNaN(
            date.getTime(),
        )
    ) {
        return '—'
    }

    return new Intl.DateTimeFormat(
        undefined,
        {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
        },
    ).format(date)
}
