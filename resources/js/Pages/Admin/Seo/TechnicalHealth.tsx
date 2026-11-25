import AdminLayout from '@/Layouts/Admin/AdminLayout'
import { Head, Link } from '@inertiajs/react'
import {
    Activity,
    AlertTriangle,
    BarChart3,
    Bot,
    CheckCircle2,
    CircleAlert,
    Gauge,
    Globe2,
    LayoutGrid,
    Search,
    ShieldCheck,
    Sparkles,
    TriangleAlert,
    XCircle,
} from 'lucide-react'
import {
    useMemo,
    useState,
} from 'react'

type Check = {
    id: string
    label: string
    category: string
    status: string
    score: number
    max_score: number
    detail: string
    recommendation: string
}

type Category = {
    category: string
    label: string
    score: number
    max_score: number
    percent: number
}

type CoverageRow = {
    entity_type: string
    label: string
    total: number
    analyzed: number
    percent: number
}

type StatusRow = {
    status: string
    label: string
    count: number
}

type Props = {
    health: {
        summary: {
            health_score: number
            health_grade: string
            passing_checks: number
            warning_checks: number
            failed_checks: number
            analysis_coverage_percent: number
            analysis_freshness_percent: number
        }
        checks: Check[]
        categories: Category[]
        coverage: {
            total: number
            analyzed: number
            percent: number
            by_type: CoverageRow[]
        }
        operations: {
            latest_analysis_count: number
            latest_noindex_count: number
            critical_findings: number
            warning_findings: number
            failed_requests_30d: number
            failed_enrichments_30d: number
            pending_suggestions: number
            request_statuses_30d: StatusRow[]
            enrichment_statuses_30d: StatusRow[]
        }
        settings_snapshot: {
            canonical_host: string | null
            default_robots_index: boolean
            default_robots_follow: boolean
            enable_sitemap: boolean
            enable_structured_data: boolean
            enable_open_graph: boolean
            enable_twitter_cards: boolean
            twitter_card: string
        }
    }
}

type ViewMode =
    | 'classic'
    | 'graphical'

export default function TechnicalHealth({
    health,
}: Props) {
    const [view, setView] =
        useState<ViewMode>(
            'classic',
        )

    const topCards =
        useMemo(
            () => [
                {
                    label:
                        'Technical health',
                    value: `${health.summary.health_score}/100`,
                    note:
                        health.summary.health_grade.replaceAll(
                            '_',
                            ' ',
                        ),
                    icon:
                        Gauge,
                },
                {
                    label:
                        'Analysis coverage',
                    value: `${health.summary.analysis_coverage_percent}%`,
                    note: `${health.coverage.analyzed} of ${health.coverage.total} entities analyzed`,
                    icon:
                        Search,
                },
                {
                    label:
                        'Needs attention',
                    value:
                        String(
                            health.summary.warning_checks +
                                health.summary.failed_checks,
                        ),
                    note: `${health.summary.failed_checks} failed • ${health.summary.warning_checks} warnings`,
                    icon:
                        AlertTriangle,
                },
                {
                    label:
                        'AI review backlog',
                    value:
                        String(
                            health.operations.pending_suggestions,
                        ),
                    note: `${health.operations.failed_requests_30d} failed requests in 30d`,
                    icon:
                        Bot,
                },
            ],
            [
                health,
            ],
        )

    const maxRequestStatus =
        Math.max(
            1,
            ...health.operations.request_statuses_30d.map(
                (
                    row,
                ) =>
                    row.count,
            ),
        )

    const maxEnrichmentStatus =
        Math.max(
            1,
            ...health.operations.enrichment_statuses_30d.map(
                (
                    row,
                ) =>
                    row.count,
            ),
        )

    return (
        <AdminLayout title="">
            <Head title="SEO Technical Health" />

            <div className="space-y-6">
                <Header
                    view={view}
                    setView={setView}
                />

                <ScopeNotice />

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {topCards.map(
                        (
                            card,
                        ) => (
                            <SummaryCard
                                key={
                                    card.label
                                }
                                {...card}
                            />
                        ),
                    )}
                </div>

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
                    <main className="min-w-0 space-y-6">
                        {view ===
                        'classic' ? (
                            <>
                                <Section
                                    icon={ShieldCheck}
                                    title="Technical readiness checks"
                                    description="Transparent, weighted checks that make up the internal 0–100 technical health score."
                                >
                                    <ChecksTable
                                        checks={
                                            health.checks
                                        }
                                    />
                                </Section>

                                <Section
                                    icon={Search}
                                    title="Analysis coverage"
                                    description="Coverage of the current product, category, brand and page inventory."
                                >
                                    <CoverageGrid
                                        rows={
                                            health.coverage
                                                .by_type
                                        }
                                    />
                                </Section>

                                <Section
                                    icon={Activity}
                                    title="Operational risk signals"
                                    description="These signals are visible for triage but do not directly change the technical health score."
                                >
                                    <RiskGrid
                                        health={
                                            health
                                        }
                                    />
                                </Section>
                            </>
                        ) : (
                            <>
                                <div className="grid gap-6 xl:grid-cols-2">
                                    <Section
                                        icon={BarChart3}
                                        title="Health by category"
                                        description="Weighted readiness across discovery, presentation, structured identity and operations."
                                    >
                                        <BarRows
                                            rows={health.categories.map(
                                                (
                                                    category,
                                                ) => ({
                                                    label:
                                                        category.label,
                                                    value:
                                                        category.percent,
                                                    max:
                                                        100,
                                                    suffix:
                                                        '%',
                                                    note: `${category.score}/${category.max_score} points`,
                                                }),
                                            )}
                                        />
                                    </Section>

                                    <Section
                                        icon={LayoutGrid}
                                        title="Entity analysis coverage"
                                        description="Graphical coverage by SEO entity family."
                                    >
                                        <BarRows
                                            rows={health.coverage.by_type.map(
                                                (
                                                    row,
                                                ) => ({
                                                    label:
                                                        row.label,
                                                    value:
                                                        row.percent,
                                                    max:
                                                        100,
                                                    suffix:
                                                        '%',
                                                    note: `${row.analyzed}/${row.total} analyzed`,
                                                }),
                                            )}
                                        />
                                    </Section>
                                </div>

                                <div className="grid gap-6 xl:grid-cols-2">
                                    <Section
                                        icon={Bot}
                                        title="AI request lifecycle — 30 days"
                                        description="Operational visibility only; AI is optional and these counts are not weighted into technical SEO health."
                                    >
                                        <BarRows
                                            rows={health.operations.request_statuses_30d.map(
                                                (
                                                    row,
                                                ) => ({
                                                    label:
                                                        row.label,
                                                    value:
                                                        row.count,
                                                    max:
                                                        maxRequestStatus,
                                                    suffix:
                                                        '',
                                                    note: `${row.count} requests`,
                                                }),
                                            )}
                                        />
                                    </Section>

                                    <Section
                                        icon={Sparkles}
                                        title="Analysis enrichment lifecycle — 30 days"
                                        description="Recent AI explanation activity for deterministic findings."
                                    >
                                        <BarRows
                                            rows={health.operations.enrichment_statuses_30d.map(
                                                (
                                                    row,
                                                ) => ({
                                                    label:
                                                        row.label,
                                                    value:
                                                        row.count,
                                                    max:
                                                        maxEnrichmentStatus,
                                                    suffix:
                                                        '',
                                                    note: `${row.count} enrichments`,
                                                }),
                                            )}
                                        />
                                    </Section>
                                </div>
                            </>
                        )}
                    </main>

                    <aside className="space-y-6 xl:sticky xl:top-6 xl:self-start">
                        <HealthScoreCard
                            health={
                                health
                            }
                        />

                        <SettingsSnapshotCard
                            settings={
                                health.settings_snapshot
                            }
                        />

                        <Section
                            icon={Globe2}
                            title="SEO workspace"
                            description="Useful next destinations from technical health."
                        >
                            <div className="space-y-2">
                                <WorkspaceLink
                                    href={route(
                                        'admin.seo.overview',
                                    )}
                                    label="SEO Overview"
                                    note="Scores, coverage and findings"
                                />

                                <WorkspaceLink
                                    href={route(
                                        'admin.seo.suggestions.index',
                                    )}
                                    label="AI Suggestions"
                                    note="Human review inbox"
                                />
                            </div>
                        </Section>
                    </aside>
                </div>
            </div>
        </AdminLayout>
    )
}

function Header({
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
                    <Activity className="h-4 w-4" />
                    Technical SEO
                </div>

                <h1 className="mt-2 text-2xl font-bold tracking-tight text-neutral-950 dark:text-neutral-100">
                    Technical Health
                </h1>

                <p className="mt-2 max-w-3xl text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                    Monitor global SEO infrastructure, indexing defaults,
                    analysis coverage and operational SEO risk from one
                    dashboard.
                </p>
            </div>

            <div className="inline-flex rounded-xl border border-neutral-200 bg-white p-1 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                <Toggle
                    active={
                        view ===
                        'classic'
                    }
                    onClick={() =>
                        setView(
                            'classic',
                        )
                    }
                    label="Classic"
                    icon={LayoutGrid}
                />

                <Toggle
                    active={
                        view ===
                        'graphical'
                    }
                    onClick={() =>
                        setView(
                            'graphical',
                        )
                    }
                    label="Graphical"
                    icon={BarChart3}
                />
            </div>
        </div>
    )
}

function Toggle({
    active,
    onClick,
    label,
    icon: Icon,
}: {
    active: boolean
    onClick: () => void
    label: string
    icon: typeof LayoutGrid
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={[
                'inline-flex h-10 items-center gap-2 rounded-lg px-4 text-sm font-semibold transition',
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

function ScopeNotice() {
    return (
        <div className="rounded-xl border border-blue-200 bg-blue-50/70 p-4 dark:border-blue-900/60 dark:bg-blue-950/20">
            <div className="flex items-start gap-3">
                <ShieldCheck className="mt-0.5 h-5 w-5 shrink-0 text-blue-600 dark:text-blue-400" />

                <div>
                    <p className="text-sm font-semibold text-blue-950 dark:text-blue-100">
                        Internal technical-readiness score
                    </p>

                    <p className="mt-1 text-sm leading-6 text-blue-800/80 dark:text-blue-300/80">
                        This 0–100 score is an explainable application
                        metric based on your configured SEO infrastructure
                        and operational coverage. It is not a Google
                        ranking score or ranking guarantee.
                    </p>
                </div>
            </div>
        </div>
    )
}

function SummaryCard({
    label,
    value,
    note,
    icon: Icon,
}: {
    label: string
    value: string
    note: string
    icon: typeof Gauge
}) {
    return (
        <div className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                        {label}
                    </p>

                    <p className="mt-2 text-2xl font-bold capitalize text-neutral-950 dark:text-neutral-100">
                        {value}
                    </p>

                    <p className="mt-2 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                        {note}
                    </p>
                </div>

                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-neutral-100 text-neutral-700 dark:bg-neutral-900 dark:text-neutral-300">
                    <Icon className="h-5 w-5" />
                </div>
            </div>
        </div>
    )
}

function ChecksTable({
    checks,
}: {
    checks: Check[]
}) {
    return (
        <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-neutral-200 text-sm dark:divide-neutral-800">
                <thead>
                    <tr className="text-left text-xs uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                        <th className="pb-3 pr-4 font-medium">
                            Check
                        </th>
                        <th className="pb-3 pr-4 font-medium">
                            Status
                        </th>
                        <th className="pb-3 pr-4 font-medium">
                            Score
                        </th>
                        <th className="pb-3 font-medium">
                            Detail
                        </th>
                    </tr>
                </thead>

                <tbody className="divide-y divide-neutral-100 dark:divide-neutral-900">
                    {checks.map(
                        (
                            check,
                        ) => (
                            <tr
                                key={
                                    check.id
                                }
                            >
                                <td className="py-4 pr-4 align-top">
                                    <p className="font-semibold text-neutral-900 dark:text-neutral-100">
                                        {check.label}
                                    </p>

                                    <p className="mt-1 text-xs capitalize text-neutral-500 dark:text-neutral-400">
                                        {check.category.replaceAll(
                                            '_',
                                            ' ',
                                        )}
                                    </p>
                                </td>

                                <td className="py-4 pr-4 align-top">
                                    <CheckStatus
                                        status={
                                            check.status
                                        }
                                    />
                                </td>

                                <td className="py-4 pr-4 align-top font-semibold text-neutral-900 dark:text-neutral-100">
                                    {check.score}/
                                    {
                                        check.max_score
                                    }
                                </td>

                                <td className="py-4 align-top">
                                    <p className="text-neutral-600 dark:text-neutral-300">
                                        {check.detail}
                                    </p>

                                    {check.status !==
                                        'pass' && (
                                        <p className="mt-2 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                                            Recommended:{' '}
                                            {
                                                check.recommendation
                                            }
                                        </p>
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

function CoverageGrid({
    rows,
}: {
    rows: CoverageRow[]
}) {
    return (
        <div className="grid gap-4 md:grid-cols-2">
            {rows.map(
                (
                    row,
                ) => (
                    <div
                        key={
                            row.entity_type
                        }
                        className="rounded-xl border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-900/60"
                    >
                        <div className="flex items-center justify-between gap-4">
                            <div>
                                <p className="font-semibold text-neutral-900 dark:text-neutral-100">
                                    {row.label}
                                </p>

                                <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                    {
                                        row.analyzed
                                    }{' '}
                                    of{' '}
                                    {
                                        row.total
                                    }{' '}
                                    analyzed
                                </p>
                            </div>

                            <span className="text-lg font-bold text-neutral-950 dark:text-neutral-100">
                                {row.percent}%
                            </span>
                        </div>

                        <div className="mt-3 h-2 rounded-full bg-neutral-200 dark:bg-neutral-800">
                            <div
                                className="h-2 rounded-full bg-neutral-950 dark:bg-white"
                                style={{
                                    width: `${Math.max(
                                        0,
                                        Math.min(
                                            100,
                                            row.percent,
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

function RiskGrid({
    health,
}: {
    health: Props['health']
}) {
    const risks = [
        {
            label:
                'Latest noindex analyses',
            value:
                health.operations
                    .latest_noindex_count,
            note:
                'May be intentional; verify policy.',
        },
        {
            label:
                'Critical findings',
            value:
                health.operations
                    .critical_findings,
            note:
                'Across latest deterministic analyses.',
        },
        {
            label:
                'Warning findings',
            value:
                health.operations
                    .warning_findings,
            note:
                'Across latest deterministic analyses.',
        },
        {
            label:
                'Failed AI requests',
            value:
                health.operations
                    .failed_requests_30d,
            note:
                'Last 30 days; operational only.',
        },
        {
            label:
                'Failed enrichments',
            value:
                health.operations
                    .failed_enrichments_30d,
            note:
                'Last 30 days; operational only.',
        },
        {
            label:
                'Pending suggestions',
            value:
                health.operations
                    .pending_suggestions,
            note:
                'Awaiting human review.',
        },
    ]

    return (
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            {risks.map(
                (
                    risk,
                ) => (
                    <div
                        key={
                            risk.label
                        }
                        className="rounded-xl border border-neutral-200 p-4 dark:border-neutral-800"
                    >
                        <p className="text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                            {risk.label}
                        </p>

                        <p className="mt-2 text-2xl font-bold text-neutral-950 dark:text-neutral-100">
                            {risk.value}
                        </p>

                        <p className="mt-2 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                            {risk.note}
                        </p>
                    </div>
                ),
            )}
        </div>
    )
}

function BarRows({
    rows,
}: {
    rows: {
        label: string
        value: number
        max: number
        suffix: string
        note: string
    }[]
}) {
    return (
        <div className="space-y-4">
            {rows.map(
                (
                    row,
                ) => {
                    const width =
                        row.max <=
                        0
                            ? 0
                            : Math.max(
                                  row.value >
                                      0
                                      ? 4
                                      : 0,
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
                        >
                            <div className="mb-2 flex items-center justify-between gap-4">
                                <div>
                                    <p className="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                        {row.label}
                                    </p>

                                    <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                        {row.note}
                                    </p>
                                </div>

                                <span className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                    {row.value}
                                    {row.suffix}
                                </span>
                            </div>

                            <div className="h-2 rounded-full bg-neutral-200 dark:bg-neutral-800">
                                <div
                                    className="h-2 rounded-full bg-neutral-950 dark:bg-white"
                                    style={{
                                        width: `${Math.min(
                                            100,
                                            width,
                                        )}%`,
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

function HealthScoreCard({
    health,
}: {
    health: Props['health']
}) {
    return (
        <Section
            icon={Gauge}
            title="Health score"
            description="Weighted technical-readiness summary."
        >
            <div className="text-center">
                <p className="text-5xl font-black tracking-tight text-neutral-950 dark:text-neutral-100">
                    {
                        health.summary
                            .health_score
                    }
                </p>

                <p className="mt-2 text-sm font-semibold capitalize text-neutral-600 dark:text-neutral-300">
                    {health.summary.health_grade.replaceAll(
                        '_',
                        ' ',
                    )}
                </p>
            </div>

            <div className="mt-5 space-y-3 text-sm">
                <StatusLine
                    label="Passing"
                    value={
                        health.summary
                            .passing_checks
                    }
                    icon={
                        CheckCircle2
                    }
                />

                <StatusLine
                    label="Warnings"
                    value={
                        health.summary
                            .warning_checks
                    }
                    icon={
                        TriangleAlert
                    }
                />

                <StatusLine
                    label="Failed"
                    value={
                        health.summary
                            .failed_checks
                    }
                    icon={
                        XCircle
                    }
                />
            </div>
        </Section>
    )
}

function SettingsSnapshotCard({
    settings,
}: {
    settings: Props['health']['settings_snapshot']
}) {
    const rows = [
        {
            label:
                'Canonical host',
            value:
                settings.canonical_host ??
                'Not configured',
        },
        {
            label:
                'Default robots',
            value: `${settings.default_robots_index ? 'index' : 'noindex'}, ${settings.default_robots_follow ? 'follow' : 'nofollow'}`,
        },
        {
            label:
                'Sitemap',
            value:
                settings.enable_sitemap
                    ? 'Enabled'
                    : 'Disabled',
        },
        {
            label:
                'Structured data',
            value:
                settings.enable_structured_data
                    ? 'Enabled'
                    : 'Disabled',
        },
        {
            label:
                'Open Graph',
            value:
                settings.enable_open_graph
                    ? 'Enabled'
                    : 'Disabled',
        },
        {
            label:
                'X/Twitter',
            value: settings.enable_twitter_cards
                ? `${settings.twitter_card} enabled`
                : 'Disabled',
        },
    ]

    return (
        <Section
            icon={Globe2}
            title="Global SEO snapshot"
            description="Current global technical configuration."
        >
            <div className="space-y-3">
                {rows.map(
                    (
                        row,
                    ) => (
                        <div
                            key={
                                row.label
                            }
                            className="flex items-start justify-between gap-4 border-b border-neutral-100 pb-3 last:border-0 last:pb-0 dark:border-neutral-900"
                        >
                            <span className="text-sm text-neutral-500 dark:text-neutral-400">
                                {row.label}
                            </span>

                            <span className="max-w-[160px] break-words text-right text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                {row.value}
                            </span>
                        </div>
                    ),
                )}
            </div>
        </Section>
    )
}

function WorkspaceLink({
    href,
    label,
    note,
}: {
    href: string
    label: string
    note: string
}) {
    return (
        <Link
            href={href}
            className="block rounded-lg border border-neutral-200 p-3 transition hover:bg-neutral-50 dark:border-neutral-800 dark:hover:bg-neutral-900"
        >
            <p className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                {label}
            </p>

            <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                {note}
            </p>
        </Link>
    )
}

function CheckStatus({
    status,
}: {
    status: string
}) {
    const Icon =
        status ===
        'pass'
            ? CheckCircle2
            : status ===
                'warn'
              ? CircleAlert
              : XCircle

    return (
        <span className="inline-flex items-center gap-1.5 rounded-full border border-neutral-200 bg-neutral-50 px-2.5 py-1 text-xs font-semibold capitalize text-neutral-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200">
            <Icon className="h-3.5 w-3.5" />
            {status}
        </span>
    )
}

function StatusLine({
    label,
    value,
    icon: Icon,
}: {
    label: string
    value: number
    icon: typeof CheckCircle2
}) {
    return (
        <div className="flex items-center justify-between gap-3">
            <span className="inline-flex items-center gap-2 text-neutral-600 dark:text-neutral-300">
                <Icon className="h-4 w-4" />
                {label}
            </span>

            <span className="font-semibold text-neutral-950 dark:text-neutral-100">
                {value}
            </span>
        </div>
    )
}

function Section({
    icon: Icon,
    title,
    description,
    children,
}: {
    icon: typeof Activity
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
