import AdminLayout from '@/Layouts/Admin/AdminLayout'
import { Head, Link, router } from '@inertiajs/react'
import {
    Activity,
    AlertTriangle,
    ArrowLeft,
    BarChart3,
    Bot,
    CheckCircle2,
    Clock3,
    FileSearch,
    History,
    RefreshCw,
    Search,
    ShieldCheck,
    Sparkles,
    ThumbsDown,
    ThumbsUp,
    WandSparkles,
    XCircle,
} from 'lucide-react'

type Entity = {
    type: string
    id: number
    name: string
    slug: string
    current_seo: Record<string, string | null>
    content: Record<string, unknown>
    context: Record<string, unknown>
}

type Summary = {
    latest_score: number | null
    latest_grade: string | null
    analysis_stale: boolean
    finding_count: number
    suggestion_count: number
    pending_suggestions: number
    approved_suggestions: number
    rejected_suggestions: number
    stale_suggestions: number
    latest_analysis_at: string | null
    latest_enrichment_status: string | null
}

type Finding = {
    id?: string
    category?: string
    severity?: string
    title?: string
    message?: string
    recommendation?: string
}

type LatestAnalysis = {
    id: number
    score: number
    grade: string
    analysis_version: string
    snapshot_schema_version: string
    snapshot_hash: string
    analysis_input_hash: string
    is_stale: boolean
    breakdown: Record<
        string,
        {
            points?: number
            max_points?: number
        }
    >
    rules: Record<string, unknown>[]
    findings: Finding[]
    resolved_seo: Record<string, unknown>
    analyzed_by_admin_id: number | null
    analyzed_at: string
}

type EnrichmentAction = {
    finding_id?: string
    priority?: string
    explanation?: string
    action?: string
}

type LatestEnrichment = {
    id: number
    analysis_id: number
    status: string
    summary: string | null
    actions: EnrichmentAction[]
    provider: string
    model_name: string
    prompt_version: string
    error_code: string | null
    error_message: string | null
    started_at: string
    completed_at: string | null
}

type AnalysisHistory = {
    id: number
    score: number
    grade: string
    finding_count: number
    analyzed_at: string
}

type Suggestion = {
    id: number
    request_id: number
    field: string
    field_label: string
    existing_value: string | null
    suggested_value: string
    rationale: string | null
    review_status: string
    reviewed_at: string | null
    review_note: string | null
    freshness: {
        is_stale: boolean
        state: string
        source_snapshot_hash: string | null
        current_snapshot_hash: string
    }
    can_approve: boolean
    provider: string | null
    model_name: string | null
    prompt_version: string | null
    generated_at: string | null
}

type RequestHistory = {
    id: number
    status: string
    provider: string | null
    model_name: string | null
    prompt_version: string | null
    error_code: string | null
    error_message: string | null
    started_at: string | null
    completed_at: string | null
    cancelled_at: string | null
}

type Props = {
    workspace: {
        entity: Entity
        summary: Summary
        latest_analysis: LatestAnalysis | null
        latest_enrichment: LatestEnrichment | null
        analysis_history: AnalysisHistory[]
        suggestions: Suggestion[]
        requests: RequestHistory[]
    }
}

export default function Show({
    workspace,
}: Props) {
    const entity =
        workspace.entity

    const entityParams = {
        entityType:
            entity.type,
        entityId:
            entity.id,
    }

    const analyze = () => {
        router.post(
            route(
                'admin.seo.entities.analyze',
                entityParams,
            ),
            {},
            {
                preserveScroll: true,
            },
        )
    }

    const generateSuggestions = () => {
        router.post(
            route(
                'admin.seo.entities.suggestions.generate',
                entityParams,
            ),
            {},
            {
                preserveScroll: true,
            },
        )
    }

    const enrichAnalysis = () => {
        if (
            !workspace.latest_analysis
        ) {
            return
        }

        router.post(
            route(
                'admin.seo.entities.analyses.enrich',
                {
                    ...entityParams,
                    analysisId:
                        workspace.latest_analysis
                            .id,
                },
            ),
            {},
            {
                preserveScroll: true,
            },
        )
    }

    const review = (
        suggestion: Suggestion,
        decision:
            | 'approve'
            | 'reject',
    ) => {
        router.post(
            route(
                decision ===
                    'approve'
                    ? 'admin.seo.suggestions.approve'
                    : 'admin.seo.suggestions.reject',
                {
                    suggestionId:
                        suggestion.id,
                },
            ),
            {},
            {
                preserveScroll: true,
            },
        )
    }

    return (
        <AdminLayout title="">
            <Head
                title={`SEO — ${entity.name}`}
            />

            <div className="space-y-6">
                <Header
                    entity={entity}
                    summary={
                        workspace.summary
                    }
                    onAnalyze={
                        analyze
                    }
                    onGenerateSuggestions={
                        generateSuggestions
                    }
                />

                <SafetyNotice />

                <SummaryGrid
                    summary={
                        workspace.summary
                    }
                />

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_330px]">
                    <main className="min-w-0 space-y-6">
                        <AnalysisCard
                            analysis={
                                workspace.latest_analysis
                            }
                            enrichment={
                                workspace.latest_enrichment
                            }
                            onEnrich={
                                enrichAnalysis
                            }
                        />

                        <SuggestionsCard
                            suggestions={
                                workspace.suggestions
                            }
                            onReview={
                                review
                            }
                        />

                        <SeoSnapshotCard
                            seo={
                                entity.current_seo
                            }
                        />
                    </main>

                    <aside className="space-y-6 xl:sticky xl:top-6 xl:self-start">
                        <HistoryCard
                            history={
                                workspace.analysis_history
                            }
                        />

                        <RequestHistoryCard
                            requests={
                                workspace.requests
                            }
                        />

                        <WorkspacePolicyCard />
                    </aside>
                </div>
            </div>
        </AdminLayout>
    )
}

function Header({
    entity,
    summary,
    onAnalyze,
    onGenerateSuggestions,
}: {
    entity: Entity
    summary: Summary
    onAnalyze: () => void
    onGenerateSuggestions: () => void
}) {
    return (
        <div className="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <Link
                    href={route(
                        'admin.seo.overview',
                    )}
                    className="inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                >
                    <ArrowLeft className="h-4 w-4" />
                    SEO Overview
                </Link>

                <div className="mt-3 flex flex-wrap items-center gap-2">
                    <span className="rounded-full border border-neutral-200 bg-neutral-50 px-2.5 py-1 text-xs font-semibold capitalize text-neutral-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200">
                        {entity.type}
                    </span>

                    {summary.analysis_stale && (
                        <span className="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/20 dark:text-amber-300">
                            <RefreshCw className="h-3.5 w-3.5" />
                            Analysis stale
                        </span>
                    )}
                </div>

                <h1 className="mt-3 text-2xl font-bold tracking-tight text-neutral-950 dark:text-neutral-100">
                    {entity.name}
                </h1>

                <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                    {entity.slug} • Entity #{entity.id}
                </p>

                <p className="mt-3 max-w-3xl text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                    Unified SEO workspace for deterministic analysis,
                    AI explanations, generated metadata drafts and human
                    review history.
                </p>
            </div>

            <div className="flex flex-wrap gap-2">
                <button
                    type="button"
                    onClick={onAnalyze}
                    className="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-200 dark:hover:bg-neutral-900"
                >
                    <FileSearch className="h-4 w-4" />
                    Analyze now
                </button>

                <button
                    type="button"
                    onClick={onGenerateSuggestions}
                    className="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-neutral-950 px-4 text-sm font-semibold text-white transition hover:bg-neutral-800 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200"
                >
                    <WandSparkles className="h-4 w-4" />
                    Generate suggestions
                </button>
            </div>
        </div>
    )
}

function SafetyNotice() {
    return (
        <div className="rounded-xl border border-indigo-200 bg-indigo-50/70 p-4 dark:border-indigo-900/60 dark:bg-indigo-950/20">
            <div className="flex items-start gap-3">
                <ShieldCheck className="mt-0.5 h-5 w-5 shrink-0 text-indigo-600 dark:text-indigo-400" />

                <div>
                    <p className="text-sm font-semibold text-indigo-950 dark:text-indigo-100">
                        Analysis and suggestions remain non-destructive
                    </p>

                    <p className="mt-1 text-sm leading-6 text-indigo-800/80 dark:text-indigo-300/80">
                        Analyze, explain and generate operations append
                        history only. Approving a suggestion still does
                        not apply it to live entity SEO.
                    </p>
                </div>
            </div>
        </div>
    )
}

function SummaryGrid({
    summary,
}: {
    summary: Summary
}) {
    const cards = [
        {
            label:
                'Latest SEO score',
            value:
                summary.latest_score ===
                null
                    ? '—'
                    : `${summary.latest_score}/100`,
            note:
                summary.latest_grade
                    ? summary.latest_grade.replaceAll(
                          '_',
                          ' ',
                      )
                    : 'No analysis yet',
            icon:
                Activity,
        },
        {
            label:
                'Deterministic findings',
            value:
                String(
                    summary.finding_count,
                ),
            note:
                summary.analysis_stale
                    ? 'Re-analysis recommended'
                    : 'Latest analysis state',
            icon:
                AlertTriangle,
        },
        {
            label:
                'Metadata suggestions',
            value:
                String(
                    summary.suggestion_count,
                ),
            note: `${summary.pending_suggestions} pending • ${summary.approved_suggestions} approved`,
            icon:
                Sparkles,
        },
        {
            label:
                'Stale suggestions',
            value:
                String(
                    summary.stale_suggestions,
                ),
            note:
                summary.stale_suggestions >
                0
                    ? 'Generate fresh drafts before approval'
                    : 'No stale drafts detected',
            icon:
                RefreshCw,
        },
    ]

    return (
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            {cards.map(
                (
                    card,
                ) => (
                    <div
                        key={
                            card.label
                        }
                        className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950"
                    >
                        <div className="flex items-start justify-between gap-4">
                            <div>
                                <p className="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                                    {card.label}
                                </p>

                                <p className="mt-2 text-2xl font-bold capitalize text-neutral-950 dark:text-neutral-100">
                                    {card.value}
                                </p>

                                <p className="mt-2 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                                    {card.note}
                                </p>
                            </div>

                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-neutral-100 text-neutral-700 dark:bg-neutral-900 dark:text-neutral-300">
                                <card.icon className="h-5 w-5" />
                            </div>
                        </div>
                    </div>
                ),
            )}
        </div>
    )
}

function AnalysisCard({
    analysis,
    enrichment,
    onEnrich,
}: {
    analysis: LatestAnalysis | null
    enrichment: LatestEnrichment | null
    onEnrich: () => void
}) {
    if (!analysis) {
        return (
            <Section
                icon={BarChart3}
                title="SEO analysis"
                description="No deterministic analysis has been recorded for this entity yet."
            >
                <div className="rounded-xl border border-dashed border-neutral-300 bg-neutral-50 p-6 text-sm text-neutral-500 dark:border-neutral-700 dark:bg-neutral-900/60 dark:text-neutral-400">
                    Use “Analyze now” to create the first immutable score
                    snapshot.
                </div>
            </Section>
        )
    }

    const breakdown =
        Object.entries(
            analysis.breakdown,
        )

    return (
        <Section
            icon={BarChart3}
            title="Latest deterministic analysis"
            description={`Analysis #${analysis.id} • ${formatDateTime(
                analysis.analyzed_at,
            )}`}
            action={
                analysis.findings.length >
                0 ? (
                    <button
                        type="button"
                        onClick={onEnrich}
                        className="inline-flex h-9 items-center gap-2 rounded-lg border border-neutral-300 bg-white px-3 text-xs font-semibold text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-200 dark:hover:bg-neutral-900"
                    >
                        <Bot className="h-4 w-4" />
                        Explain with AI
                    </button>
                ) : null
            }
        >
            <div className="grid gap-5 lg:grid-cols-[180px_minmax(0,1fr)]">
                <div className="rounded-xl border border-neutral-200 bg-neutral-50 p-5 text-center dark:border-neutral-800 dark:bg-neutral-900/60">
                    <p className="text-5xl font-black tracking-tight text-neutral-950 dark:text-neutral-100">
                        {analysis.score}
                    </p>

                    <p className="mt-2 text-sm font-semibold capitalize text-neutral-600 dark:text-neutral-300">
                        {analysis.grade.replaceAll(
                            '_',
                            ' ',
                        )}
                    </p>

                    {analysis.is_stale && (
                        <div className="mt-4 inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/20 dark:text-amber-300">
                            <RefreshCw className="h-3.5 w-3.5" />
                            Stale snapshot
                        </div>
                    )}
                </div>

                <div className="space-y-4">
                    {breakdown.map(
                        ([
                            key,
                            value,
                        ]) => {
                            const points =
                                value.points ??
                                0

                            const max =
                                value.max_points ??
                                0

                            const percent =
                                max <= 0
                                    ? 0
                                    : Math.round(
                                          (points /
                                              max) *
                                              100,
                                      )

                            return (
                                <div
                                    key={key}
                                >
                                    <div className="mb-2 flex items-center justify-between gap-3 text-sm">
                                        <span className="font-medium capitalize text-neutral-900 dark:text-neutral-100">
                                            {key.replaceAll(
                                                '_',
                                                ' ',
                                            )}
                                        </span>

                                        <span className="text-neutral-500 dark:text-neutral-400">
                                            {points}/{max}
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
                                                        percent,
                                                    ),
                                                )}%`,
                                            }}
                                        />
                                    </div>
                                </div>
                            )
                        },
                    )}
                </div>
            </div>

            <div className="mt-6 space-y-3">
                <h3 className="text-sm font-semibold text-neutral-950 dark:text-neutral-100">
                    Findings
                </h3>

                {analysis.findings.length ===
                0 ? (
                    <p className="text-sm text-neutral-500 dark:text-neutral-400">
                        No deterministic findings were recorded.
                    </p>
                ) : (
                    analysis.findings.map(
                        (
                            finding,
                            index,
                        ) => (
                            <div
                                key={`${finding.id ?? 'finding'}-${index}`}
                                className="rounded-xl border border-neutral-200 p-4 dark:border-neutral-800"
                            >
                                <div className="flex flex-wrap items-center gap-2">
                                    <SeverityBadge
                                        severity={
                                            finding.severity ??
                                            'info'
                                        }
                                    />

                                    <span className="text-xs text-neutral-500 dark:text-neutral-400">
                                        {
                                            finding.category
                                        }
                                    </span>
                                </div>

                                <p className="mt-2 font-semibold text-neutral-900 dark:text-neutral-100">
                                    {
                                        finding.title ??
                                        finding.id ??
                                        'SEO finding'
                                    }
                                </p>

                                {finding.message && (
                                    <p className="mt-2 text-sm leading-6 text-neutral-600 dark:text-neutral-300">
                                        {
                                            finding.message
                                        }
                                    </p>
                                )}

                                {finding.recommendation && (
                                    <p className="mt-2 text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                                        Recommended:{' '}
                                        {
                                            finding.recommendation
                                        }
                                    </p>
                                )}
                            </div>
                        ),
                    )
                )}
            </div>

            <EnrichmentPanel
                enrichment={
                    enrichment
                }
            />
        </Section>
    )
}

function EnrichmentPanel({
    enrichment,
}: {
    enrichment: LatestEnrichment | null
}) {
    if (!enrichment) {
        return null
    }

    return (
        <div className="mt-6 rounded-xl border border-violet-200 bg-violet-50/60 p-4 dark:border-violet-900/60 dark:bg-violet-950/20">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div className="flex items-center gap-2">
                    <Bot className="h-5 w-5 text-violet-600 dark:text-violet-400" />

                    <p className="font-semibold text-violet-950 dark:text-violet-100">
                        AI analysis explanation
                    </p>
                </div>

                <span className="rounded-full border border-violet-200 bg-white/70 px-2.5 py-1 text-xs font-semibold capitalize text-violet-700 dark:border-violet-900 dark:bg-violet-950/40 dark:text-violet-300">
                    {enrichment.status}
                </span>
            </div>

            {enrichment.summary && (
                <p className="mt-3 text-sm leading-6 text-violet-900/80 dark:text-violet-200/80">
                    {enrichment.summary}
                </p>
            )}

            {enrichment.actions.length >
                0 && (
                <div className="mt-4 space-y-3">
                    {enrichment.actions.map(
                        (
                            action,
                            index,
                        ) => (
                            <div
                                key={`${action.finding_id ?? 'action'}-${index}`}
                                className="rounded-lg border border-violet-200 bg-white/60 p-3 dark:border-violet-900/60 dark:bg-violet-950/20"
                            >
                                <div className="flex flex-wrap gap-2 text-xs">
                                    <span className="font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">
                                        {
                                            action.priority ??
                                            'advisory'
                                        }
                                    </span>

                                    <span className="text-violet-500 dark:text-violet-400">
                                        {
                                            action.finding_id
                                        }
                                    </span>
                                </div>

                                {action.explanation && (
                                    <p className="mt-2 text-sm leading-6 text-neutral-700 dark:text-neutral-300">
                                        {
                                            action.explanation
                                        }
                                    </p>
                                )}

                                {action.action && (
                                    <p className="mt-2 text-sm font-medium leading-6 text-neutral-900 dark:text-neutral-100">
                                        {
                                            action.action
                                        }
                                    </p>
                                )}
                            </div>
                        ),
                    )}
                </div>
            )}
        </div>
    )
}

function SuggestionsCard({
    suggestions,
    onReview,
}: {
    suggestions: Suggestion[]
    onReview: (
        suggestion: Suggestion,
        decision:
            | 'approve'
            | 'reject',
    ) => void
}) {
    return (
        <Section
            icon={Sparkles}
            title="Metadata suggestions"
            description="AI-generated draft metadata and current human review state."
            action={
                <Link
                    href={route(
                        'admin.seo.suggestions.index',
                    )}
                    className="text-xs font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                >
                    Open full inbox
                </Link>
            }
        >
            {suggestions.length ===
            0 ? (
                <div className="rounded-xl border border-dashed border-neutral-300 bg-neutral-50 p-6 text-sm text-neutral-500 dark:border-neutral-700 dark:bg-neutral-900/60 dark:text-neutral-400">
                    No completed metadata suggestions exist for this
                    entity yet.
                </div>
            ) : (
                <div className="space-y-4">
                    {suggestions.map(
                        (
                            suggestion,
                        ) => (
                            <div
                                key={
                                    suggestion.id
                                }
                                className="rounded-xl border border-neutral-200 p-4 dark:border-neutral-800"
                            >
                                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <div className="flex flex-wrap gap-2">
                                            <span className="rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 dark:border-indigo-900 dark:bg-indigo-950/20 dark:text-indigo-300">
                                                {
                                                    suggestion.field_label
                                                }
                                            </span>

                                            <ReviewBadge
                                                status={
                                                    suggestion.review_status
                                                }
                                            />

                                            <FreshnessBadge
                                                stale={
                                                    suggestion.freshness
                                                        .is_stale
                                                }
                                            />
                                        </div>

                                        <p className="mt-2 text-xs text-neutral-500 dark:text-neutral-400">
                                            Request #
                                            {
                                                suggestion.request_id
                                            }{' '}
                                            •{' '}
                                            {
                                                suggestion.provider ??
                                                'provider unavailable'
                                            }
                                            {suggestion.model_name
                                                ? ` • ${suggestion.model_name}`
                                                : ''}
                                        </p>
                                    </div>

                                    <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                        {formatDateTime(
                                            suggestion.generated_at,
                                        )}
                                    </p>
                                </div>

                                <div className="mt-4 grid gap-3 lg:grid-cols-2">
                                    <ValueBlock
                                        label="Existing at generation"
                                        value={
                                            suggestion.existing_value
                                        }
                                        muted
                                    />

                                    <ValueBlock
                                        label="AI suggestion"
                                        value={
                                            suggestion.suggested_value
                                        }
                                    />
                                </div>

                                {suggestion.rationale && (
                                    <p className="mt-3 text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                                        {
                                            suggestion.rationale
                                        }
                                    </p>
                                )}

                                <div className="mt-4 flex flex-col gap-3 border-t border-neutral-200 pt-4 dark:border-neutral-800 sm:flex-row sm:items-center sm:justify-between">
                                    <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                        {suggestion.reviewed_at
                                            ? `Last reviewed ${formatDateTime(
                                                  suggestion.reviewed_at,
                                              )}`
                                            : 'Not reviewed yet'}
                                    </p>

                                    <div className="flex gap-2">
                                        <button
                                            type="button"
                                            onClick={() =>
                                                onReview(
                                                    suggestion,
                                                    'reject',
                                                )
                                            }
                                            className="inline-flex h-9 items-center gap-2 rounded-lg border border-red-200 px-3 text-xs font-semibold text-red-700 hover:bg-red-50 dark:border-red-900/60 dark:text-red-400 dark:hover:bg-red-950/20"
                                        >
                                            <ThumbsDown className="h-3.5 w-3.5" />
                                            Reject
                                        </button>

                                        <button
                                            type="button"
                                            disabled={
                                                !suggestion.can_approve
                                            }
                                            onClick={() =>
                                                onReview(
                                                    suggestion,
                                                    'approve',
                                                )
                                            }
                                            className="inline-flex h-9 items-center gap-2 rounded-lg bg-neutral-950 px-3 text-xs font-semibold text-white hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-40 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200"
                                        >
                                            <ThumbsUp className="h-3.5 w-3.5" />
                                            Approve
                                        </button>
                                    </div>
                                </div>
                            </div>
                        ),
                    )}
                </div>
            )}
        </Section>
    )
}

function SeoSnapshotCard({
    seo,
}: {
    seo: Record<string, string | null>
}) {
    return (
        <Section
            icon={Search}
            title="Current explicit SEO snapshot"
            description="The current editable text SEO values from the H.2 entity snapshot."
        >
            <div className="grid gap-4 md:grid-cols-2">
                {Object.entries(
                    seo,
                ).map(
                    ([
                        key,
                        value,
                    ]) => (
                        <ValueBlock
                            key={key}
                            label={key.replaceAll(
                                '_',
                                ' ',
                            )}
                            value={value}
                            muted
                        />
                    ),
                )}
            </div>
        </Section>
    )
}

function HistoryCard({
    history,
}: {
    history: AnalysisHistory[]
}) {
    const max =
        Math.max(
            1,
            ...history.map(
                (
                    item,
                ) =>
                    item.score,
            ),
        )

    return (
        <Section
            icon={History}
            title="Score history"
            description="Newest deterministic analyses first."
        >
            {history.length ===
            0 ? (
                <p className="text-sm text-neutral-500 dark:text-neutral-400">
                    No analysis history yet.
                </p>
            ) : (
                <div className="space-y-4">
                    {history.map(
                        (
                            item,
                        ) => (
                            <div
                                key={
                                    item.id
                                }
                            >
                                <div className="mb-2 flex items-center justify-between gap-3 text-xs">
                                    <span className="font-semibold text-neutral-900 dark:text-neutral-100">
                                        {
                                            item.score
                                        }
                                        /100
                                    </span>

                                    <span className="capitalize text-neutral-500 dark:text-neutral-400">
                                        {item.grade.replaceAll(
                                            '_',
                                            ' ',
                                        )}
                                    </span>
                                </div>

                                <div className="h-2 rounded-full bg-neutral-200 dark:bg-neutral-800">
                                    <div
                                        className="h-2 rounded-full bg-neutral-950 dark:bg-white"
                                        style={{
                                            width: `${Math.round(
                                                (item.score /
                                                    max) *
                                                    100,
                                            )}%`,
                                        }}
                                    />
                                </div>

                                <p className="mt-1 text-[11px] text-neutral-400">
                                    {
                                        item.finding_count
                                    }{' '}
                                    findings •{' '}
                                    {formatDateTime(
                                        item.analyzed_at,
                                    )}
                                </p>
                            </div>
                        ),
                    )}
                </div>
            )}
        </Section>
    )
}

function RequestHistoryCard({
    requests,
}: {
    requests: RequestHistory[]
}) {
    return (
        <Section
            icon={Clock3}
            title="Generation history"
            description="Recent metadata-generation request lifecycle."
        >
            {requests.length ===
            0 ? (
                <p className="text-sm text-neutral-500 dark:text-neutral-400">
                    No AI generation requests yet.
                </p>
            ) : (
                <div className="space-y-3">
                    {requests.map(
                        (
                            request,
                        ) => (
                            <div
                                key={
                                    request.id
                                }
                                className="rounded-lg border border-neutral-200 p-3 dark:border-neutral-800"
                            >
                                <div className="flex items-center justify-between gap-3">
                                    <p className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Request #
                                        {
                                            request.id
                                        }
                                    </p>

                                    <RequestBadge
                                        status={
                                            request.status
                                        }
                                    />
                                </div>

                                <p className="mt-2 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                                    {
                                        request.provider ??
                                        'provider unavailable'
                                    }
                                    {request.model_name
                                        ? ` • ${request.model_name}`
                                        : ''}
                                </p>

                                <p className="mt-1 text-xs text-neutral-400">
                                    {formatDateTime(
                                        request.completed_at ??
                                            request.started_at,
                                    )}
                                </p>

                                {request.error_message && (
                                    <p className="mt-2 text-xs leading-5 text-red-600 dark:text-red-400">
                                        {
                                            request.error_message
                                        }
                                    </p>
                                )}
                            </div>
                        ),
                    )}
                </div>
            )}
        </Section>
    )
}

function WorkspacePolicyCard() {
    return (
        <Section
            icon={ShieldCheck}
            title="Workspace policy"
            description="Safety boundaries for 10.4J.3."
        >
            <ul className="space-y-3 text-sm leading-6 text-neutral-600 dark:text-neutral-300">
                <li>
                    • Deterministic score remains authoritative.
                </li>
                <li>
                    • AI explanations do not alter the score.
                </li>
                <li>
                    • AI metadata remains draft-only.
                </li>
                <li>
                    • Stale suggestions cannot be approved.
                </li>
                <li>
                    • No approved suggestion is applied in J.3.
                </li>
            </ul>
        </Section>
    )
}

function Section({
    icon: Icon,
    title,
    description,
    action,
    children,
}: {
    icon: typeof Search
    title: string
    description: string
    action?: React.ReactNode
    children: React.ReactNode
}) {
    return (
        <section className="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <div className="flex flex-col gap-3 border-b border-neutral-200 px-5 py-4 dark:border-neutral-800 sm:flex-row sm:items-start sm:justify-between sm:px-6">
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

                {action}
            </div>

            <div className="p-5 sm:p-6">
                {children}
            </div>
        </section>
    )
}

function ValueBlock({
    label,
    value,
    muted = false,
}: {
    label: string
    value: string | null
    muted?: boolean
}) {
    return (
        <div
            className={[
                'rounded-xl border p-4',
                muted
                    ? 'border-neutral-200 bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-900/60'
                    : 'border-indigo-200 bg-indigo-50/50 dark:border-indigo-900/60 dark:bg-indigo-950/20',
            ].join(' ')}
        >
            <p className="text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                {label}
            </p>

            <p className="mt-2 whitespace-pre-wrap break-words text-sm leading-6 text-neutral-800 dark:text-neutral-200">
                {value ??
                    'No explicit value.'}
            </p>
        </div>
    )
}

function ReviewBadge({
    status,
}: {
    status: string
}) {
    const Icon =
        status ===
        'approved'
            ? CheckCircle2
            : status ===
                'rejected'
              ? XCircle
              : Clock3

    return (
        <span className="inline-flex items-center gap-1.5 rounded-full border border-neutral-200 bg-neutral-50 px-2.5 py-1 text-xs font-semibold capitalize text-neutral-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200">
            <Icon className="h-3.5 w-3.5" />
            {status}
        </span>
    )
}

function FreshnessBadge({
    stale,
}: {
    stale: boolean
}) {
    return stale ? (
        <span className="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/20 dark:text-amber-300">
            <RefreshCw className="h-3.5 w-3.5" />
            Stale
        </span>
    ) : (
        <span className="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/20 dark:text-emerald-300">
            <CheckCircle2 className="h-3.5 w-3.5" />
            Fresh
        </span>
    )
}

function SeverityBadge({
    severity,
}: {
    severity: string
}) {
    return (
        <span className="rounded-full border border-neutral-200 bg-neutral-50 px-2.5 py-1 text-xs font-semibold capitalize text-neutral-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200">
            {severity}
        </span>
    )
}

function RequestBadge({
    status,
}: {
    status: string
}) {
    const Icon =
        status ===
        'completed'
            ? CheckCircle2
            : status ===
                'failed'
              ? XCircle
              : status ===
                  'processing'
                ? RefreshCw
                : Clock3

    return (
        <span className="inline-flex items-center gap-1 rounded-full border border-neutral-200 bg-neutral-50 px-2 py-1 text-[11px] font-semibold capitalize text-neutral-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200">
            <Icon className="h-3 w-3" />
            {status}
        </span>
    )
}

function formatDateTime(
    value: string | null,
) {
    if (!value) {
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
            year:
                'numeric',
            month:
                'short',
            day:
                'numeric',
            hour:
                'numeric',
            minute:
                '2-digit',
        },
    ).format(
        date,
    )
}
