import AdminLayout from '@/Layouts/Admin/AdminLayout'
import { Head, Link, router } from '@inertiajs/react'
import {
    Bot,
    CheckCircle2,
    ClipboardCheck,
    Filter,
    Layers3,
    RefreshCw,
    Search,
    ShieldCheck,
    Sparkles,
    X,
} from 'lucide-react'
import {
    FormEvent,
    useMemo,
    useState,
} from 'react'

type Candidate = {
    entity_type: string
    entity_id: number
    name: string
    slug: string
    current_snapshot_hash: string
    latest_request_id: number | null
    latest_request_snapshot_hash: string | null
    state: 'ready' | 'stale' | 'fresh'
    eligible: boolean
    reason: string
}

type Option = {
    value: string
    label: string
}

type RecentPlan = {
    id: number
    reference: string
    status: string
    plan_hash: string
    entity_types: string[]
    summary: {
        total?: number
        generate?: number
        skip?: number
        ready?: number
        stale?: number
        fresh?: number
    }
    planned_by_admin_id: number | null
    planned_at: string
}

type Props = {
    workspace: {
        summary: {
            total: number
            ready: number
            stale: number
            fresh: number
            eligible: number
        }
        items: Candidate[]
        filters: {
            q: string
            entity_types: string[]
            state: string
        }
        options: {
            entity_types: Option[]
            states: Option[]
            max_candidates: number
        }
        recent_plans: RecentPlan[]
    }
}

type Target = {
    entity_type: string
    entity_id: number
}

export default function Index({
    workspace,
}: Props) {
    const [q, setQ] =
        useState(
            workspace.filters.q,
        )

    const [
        entityTypes,
        setEntityTypes,
    ] = useState<string[]>(
        workspace.filters.entity_types,
    )

    const [state, setState] =
        useState(
            workspace.filters.state,
        )

    const [selected, setSelected] =
        useState<Set<string>>(
            new Set(),
        )

    const selectedTargets =
        useMemo(
            () =>
                workspace.items
                    .filter(
                        (
                            item,
                        ) =>
                            selected.has(
                                keyFor(item),
                            ),
                    )
                    .map(
                        (
                            item,
                        ): Target => ({
                            entity_type:
                                item.entity_type,
                            entity_id:
                                item.entity_id,
                        }),
                    ),
            [
                selected,
                workspace.items,
            ],
        )

    const eligibleSelected =
        useMemo(
            () =>
                workspace.items.filter(
                    (
                        item,
                    ) =>
                        selected.has(
                            keyFor(item),
                        ) &&
                        item.eligible,
                ).length,
            [
                selected,
                workspace.items,
            ],
        )

    const skippedSelected =
        selectedTargets.length -
        eligibleSelected

    const submitFilters = (
        event: FormEvent<HTMLFormElement>,
    ) => {
        event.preventDefault()

        router.get(
            route(
                'admin.seo.bulk.index',
            ),
            {
                q:
                    q.trim() ||
                    undefined,
                entity_types:
                    entityTypes.length >
                    0
                        ? entityTypes
                        : undefined,
                state:
                    state ||
                    undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }

    const toggleType = (
        value: string,
    ) => {
        setEntityTypes(
            (
                current,
            ) =>
                current.includes(value)
                    ? current.filter(
                          (
                              item,
                          ) =>
                              item !==
                              value,
                      )
                    : [
                          ...current,
                          value,
                      ],
        )
    }

    const toggleItem = (
        item: Candidate,
    ) => {
        const key =
            keyFor(item)

        setSelected(
            (
                current,
            ) => {
                const next =
                    new Set(current)

                if (
                    next.has(key)
                ) {
                    next.delete(key)
                } else {
                    next.add(key)
                }

                return next
            },
        )
    }

    const selectEligible = () => {
        setSelected(
            new Set(
                workspace.items
                    .filter(
                        (
                            item,
                        ) =>
                            item.eligible,
                    )
                    .map(keyFor),
            ),
        )
    }

    const clearSelection = () => {
        setSelected(
            new Set(),
        )
    }

    const createPlan = () => {
        if (
            selectedTargets.length ===
            0
        ) {
            return
        }

        router.post(
            route(
                'admin.seo.bulk.plans.store',
            ),
            {
                targets:
                    selectedTargets,
                filters: {
                    q:
                        workspace.filters.q,
                    entity_types:
                        workspace.filters
                            .entity_types,
                    state:
                        workspace.filters
                            .state,
                },
            },
            {
                preserveScroll: true,
                onSuccess:
                    clearSelection,
            },
        )
    }

    return (
        <AdminLayout title="">
            <Head title="Bulk AI Optimization" />

            <div className="space-y-6">
                <Header />

                <SafetyNotice />

                <SummaryGrid
                    workspace={
                        workspace
                    }
                />

                <FilterPanel
                    q={q}
                    setQ={setQ}
                    entityTypes={
                        entityTypes
                    }
                    toggleType={
                        toggleType
                    }
                    state={state}
                    setState={
                        setState
                    }
                    options={
                        workspace.options
                    }
                    onSubmit={
                        submitFilters
                    }
                />

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_330px]">
                    <main className="min-w-0 space-y-4">
                        <SelectionToolbar
                            selected={
                                selectedTargets.length
                            }
                            eligible={
                                eligibleSelected
                            }
                            skipped={
                                skippedSelected
                            }
                            onSelectEligible={
                                selectEligible
                            }
                            onClear={
                                clearSelection
                            }
                            onCreatePlan={
                                createPlan
                            }
                        />

                        <CandidateTable
                            items={
                                workspace.items
                            }
                            selected={
                                selected
                            }
                            onToggle={
                                toggleItem
                            }
                        />
                    </main>

                    <aside className="space-y-6 xl:sticky xl:top-6 xl:self-start">
                        <PlanningPolicy />

                        <RecentPlans
                            plans={
                                workspace.recent_plans
                            }
                        />

                        <WorkspaceLinks />
                    </aside>
                </div>
            </div>
        </AdminLayout>
    )
}

function Header() {
    return (
        <div>
            <div className="flex items-center gap-2 text-sm font-medium text-indigo-600 dark:text-indigo-400">
                <Bot className="h-4 w-4" />
                AI SEO
            </div>

            <h1 className="mt-2 text-2xl font-bold tracking-tight text-neutral-950 dark:text-neutral-100">
                Bulk AI Optimization
            </h1>

            <p className="mt-2 max-w-3xl text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                Select SEO entities, inspect current generation freshness,
                and create an immutable dry-run plan before any queued AI
                execution is allowed.
            </p>
        </div>
    )
}

function SafetyNotice() {
    return (
        <div className="rounded-xl border border-violet-200 bg-violet-50/70 p-4 dark:border-violet-900/60 dark:bg-violet-950/20">
            <div className="flex items-start gap-3">
                <ShieldCheck className="mt-0.5 h-5 w-5 shrink-0 text-violet-600 dark:text-violet-400" />

                <div>
                    <p className="text-sm font-semibold text-violet-950 dark:text-violet-100">
                        K.1 is planning only
                    </p>

                    <p className="mt-1 text-sm leading-6 text-violet-800/80 dark:text-violet-300/80">
                        Creating a plan does not call an AI provider,
                        create metadata suggestions, approve drafts, or
                        apply live SEO. Fresh entities are skipped by
                        default to prevent duplicate provider spend in K.2.
                    </p>
                </div>
            </div>
        </div>
    )
}

function SummaryGrid({
    workspace,
}: {
    workspace: Props['workspace']
}) {
    const cards = [
        {
            label:
                'Visible candidates',
            value:
                workspace.summary.total,
            note: `Maximum ${workspace.options.max_candidates} per preview`,
            icon:
                Layers3,
        },
        {
            label:
                'Ready',
            value:
                workspace.summary.ready,
            note:
                'No completed AI metadata request',
            icon:
                Sparkles,
        },
        {
            label:
                'Stale',
            value:
                workspace.summary.stale,
            note:
                'Entity changed since generation',
            icon:
                RefreshCw,
        },
        {
            label:
                'Eligible',
            value:
                workspace.summary.eligible,
            note: `${workspace.summary.fresh} fresh entities skipped by default`,
            icon:
                CheckCircle2,
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

                                <p className="mt-2 text-2xl font-bold text-neutral-950 dark:text-neutral-100">
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

function FilterPanel({
    q,
    setQ,
    entityTypes,
    toggleType,
    state,
    setState,
    options,
    onSubmit,
}: {
    q: string
    setQ: (
        value: string,
    ) => void
    entityTypes: string[]
    toggleType: (
        value: string,
    ) => void
    state: string
    setState: (
        value: string,
    ) => void
    options: Props['workspace']['options']
    onSubmit: (
        event: FormEvent<HTMLFormElement>,
    ) => void
}) {
    return (
        <form
            onSubmit={onSubmit}
            className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950 sm:p-6"
        >
            <div className="mb-4 flex items-center gap-3">
                <Filter className="h-5 w-5 text-neutral-500" />

                <div>
                    <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">
                        Candidate filters
                    </h2>

                    <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                        Narrow the dry-run preview before selecting entities.
                    </p>
                </div>
            </div>

            <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_240px]">
                <label className="relative">
                    <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400" />

                    <input
                        value={q}
                        onChange={(
                            event,
                        ) =>
                            setQ(
                                event
                                    .target
                                    .value,
                            )
                        }
                        placeholder="Search entity name..."
                        className={`${inputClasses} pl-9`}
                    />
                </label>

                <select
                    value={state}
                    onChange={(
                        event,
                    ) =>
                        setState(
                            event
                                .target
                                .value,
                        )
                    }
                    className={inputClasses}
                >
                    {options.states.map(
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
                                {option.label}
                            </option>
                        ),
                    )}
                </select>
            </div>

            <div className="mt-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex flex-wrap gap-2">
                    {options.entity_types.map(
                        (
                            option,
                        ) => {
                            const active =
                                entityTypes.includes(
                                    option.value,
                                )

                            return (
                                <button
                                    key={
                                        option.value
                                    }
                                    type="button"
                                    onClick={() =>
                                        toggleType(
                                            option.value,
                                        )
                                    }
                                    className={[
                                        'rounded-full border px-3 py-1.5 text-xs font-semibold transition',
                                        active
                                            ? 'border-neutral-950 bg-neutral-950 text-white dark:border-white dark:bg-white dark:text-neutral-950'
                                            : 'border-neutral-300 text-neutral-600 hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-900',
                                    ].join(
                                        ' ',
                                    )}
                                >
                                    {option.label}
                                </button>
                            )
                        },
                    )}
                </div>

                <button
                    type="submit"
                    className="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-neutral-950 px-4 text-sm font-semibold text-white dark:bg-white dark:text-neutral-950"
                >
                    <Search className="h-4 w-4" />
                    Preview
                </button>
            </div>
        </form>
    )
}

function SelectionToolbar({
    selected,
    eligible,
    skipped,
    onSelectEligible,
    onClear,
    onCreatePlan,
}: {
    selected: number
    eligible: number
    skipped: number
    onSelectEligible: () => void
    onClear: () => void
    onCreatePlan: () => void
}) {
    return (
        <div className="flex flex-col gap-3 rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                    {selected} selected
                </p>

                <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                    {eligible} planned for generation • {skipped} planned to skip
                </p>
            </div>

            <div className="flex flex-wrap gap-2">
                <button
                    type="button"
                    onClick={onSelectEligible}
                    className="inline-flex h-9 items-center gap-2 rounded-lg border border-neutral-300 px-3 text-xs font-semibold text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-900"
                >
                    <CheckCircle2 className="h-3.5 w-3.5" />
                    Select eligible
                </button>

                <button
                    type="button"
                    onClick={onClear}
                    className="inline-flex h-9 items-center gap-2 rounded-lg border border-neutral-300 px-3 text-xs font-semibold text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-900"
                >
                    <X className="h-3.5 w-3.5" />
                    Clear
                </button>

                <button
                    type="button"
                    disabled={
                        selected ===
                        0
                    }
                    onClick={onCreatePlan}
                    className="inline-flex h-9 items-center gap-2 rounded-lg bg-neutral-950 px-3 text-xs font-semibold text-white disabled:cursor-not-allowed disabled:opacity-40 dark:bg-white dark:text-neutral-950"
                >
                    <ClipboardCheck className="h-3.5 w-3.5" />
                    Create dry-run plan
                </button>
            </div>
        </div>
    )
}

function CandidateTable({
    items,
    selected,
    onToggle,
}: {
    items: Candidate[]
    selected: Set<string>
    onToggle: (
        item: Candidate,
    ) => void
}) {
    if (
        items.length ===
        0
    ) {
        return (
            <div className="rounded-xl border border-dashed border-neutral-300 bg-white p-10 text-center text-sm text-neutral-500 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-400">
                No SEO entities match the current preview filters.
            </div>
        )
    }

    return (
        <div className="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-neutral-200 text-sm dark:divide-neutral-800">
                    <thead>
                        <tr className="text-left text-xs uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                            <th className="px-5 py-3 font-medium">
                                Select
                            </th>
                            <th className="px-5 py-3 font-medium">
                                Entity
                            </th>
                            <th className="px-5 py-3 font-medium">
                                State
                            </th>
                            <th className="px-5 py-3 font-medium">
                                Latest request
                            </th>
                            <th className="px-5 py-3 font-medium">
                                Planned action
                            </th>
                        </tr>
                    </thead>

                    <tbody className="divide-y divide-neutral-100 dark:divide-neutral-900">
                        {items.map(
                            (
                                item,
                            ) => (
                                <tr
                                    key={
                                        keyFor(item)
                                    }
                                >
                                    <td className="px-5 py-4 align-top">
                                        <input
                                            type="checkbox"
                                            checked={selected.has(
                                                keyFor(item),
                                            )}
                                            onChange={() =>
                                                onToggle(
                                                    item,
                                                )
                                            }
                                            className="h-4 w-4 rounded border-neutral-300"
                                        />
                                    </td>

                                    <td className="px-5 py-4 align-top">
                                        <Link
                                            href={route(
                                                'admin.seo.entities.show',
                                                {
                                                    entityType:
                                                        item.entity_type,
                                                    entityId:
                                                        item.entity_id,
                                                },
                                            )}
                                            className="font-semibold text-neutral-900 hover:text-indigo-600 dark:text-neutral-100 dark:hover:text-indigo-400"
                                        >
                                            {item.name}
                                        </Link>

                                        <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                            {item.entity_type} #{item.entity_id} • {item.slug}
                                        </p>

                                        <p className="mt-2 max-w-xl text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                                            {item.reason}
                                        </p>
                                    </td>

                                    <td className="px-5 py-4 align-top">
                                        <StateBadge
                                            state={
                                                item.state
                                            }
                                        />
                                    </td>

                                    <td className="px-5 py-4 align-top text-neutral-600 dark:text-neutral-300">
                                        {item.latest_request_id
                                            ? `#${item.latest_request_id}`
                                            : '—'}
                                    </td>

                                    <td className="px-5 py-4 align-top">
                                        <span className="text-xs font-semibold uppercase tracking-wide text-neutral-700 dark:text-neutral-300">
                                            {item.eligible
                                                ? 'Generate'
                                                : 'Skip'}
                                        </span>
                                    </td>
                                </tr>
                            ),
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    )
}

function StateBadge({
    state,
}: {
    state: Candidate['state']
}) {
    const label =
        state ===
        'ready'
            ? 'Ready'
            : state ===
                'stale'
              ? 'Stale'
              : 'Fresh'

    return (
        <span className="inline-flex items-center gap-1.5 rounded-full border border-neutral-200 bg-neutral-50 px-2.5 py-1 text-xs font-semibold text-neutral-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200">
            {state ===
            'fresh' ? (
                <CheckCircle2 className="h-3.5 w-3.5" />
            ) : state ===
              'stale' ? (
                <RefreshCw className="h-3.5 w-3.5" />
            ) : (
                <Sparkles className="h-3.5 w-3.5" />
            )}
            {label}
        </span>
    )
}

function PlanningPolicy() {
    return (
        <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <div className="flex items-center gap-2">
                <ShieldCheck className="h-5 w-5 text-emerald-600" />

                <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">
                    Planning policy
                </h2>
            </div>

            <div className="mt-4 space-y-3 text-sm leading-6 text-neutral-600 dark:text-neutral-300">
                <p>
                    Ready and stale entities are eligible for future generation.
                </p>

                <p>
                    Fresh entities remain selectable for audit visibility but are planned as skip.
                </p>

                <p>
                    Plans persist exact snapshot hashes so K.2 can detect drift before execution.
                </p>

                <p>
                    Maximum plan size: 250 entities.
                </p>
            </div>
        </section>
    )
}

function RecentPlans({
    plans,
}: {
    plans: RecentPlan[]
}) {
    return (
        <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <div className="flex items-center gap-2">
                <ClipboardCheck className="h-5 w-5 text-neutral-600 dark:text-neutral-300" />

                <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">
                    Recent dry-run plans
                </h2>
            </div>

            {plans.length ===
            0 ? (
                <p className="mt-4 text-sm text-neutral-500 dark:text-neutral-400">
                    No bulk plans created yet.
                </p>
            ) : (
                <div className="mt-4 space-y-3">
                    {plans.map(
                        (
                            plan,
                        ) => (
                            <Link
                                key={
                                    plan.id
                                }
                                href={route(
                                    'admin.seo.bulk.plans.show',
                                    {
                                        bulkJobId:
                                            plan.id,
                                    },
                                )}
                                className="block rounded-lg border border-neutral-200 p-3 transition hover:bg-neutral-50 dark:border-neutral-800 dark:hover:bg-neutral-900"
                            >
                                <div className="flex items-center justify-between gap-3">
                                    <p className="truncate text-xs font-semibold text-neutral-900 dark:text-neutral-100">
                                        {plan.reference}
                                    </p>

                                    <span className="text-[11px] font-semibold uppercase tracking-wide text-neutral-500">
                                        {plan.status}
                                    </span>
                                </div>

                                <p className="mt-2 text-xs text-neutral-500 dark:text-neutral-400">
                                    {plan.summary.generate ?? 0} generate • {plan.summary.skip ?? 0} skip
                                </p>

                                <p className="mt-1 text-[11px] text-neutral-400">
                                    {formatDateTime(
                                        plan.planned_at,
                                    )}
                                </p>
                            </Link>
                        ),
                    )}
                </div>
            )}
        </section>
    )
}

function WorkspaceLinks() {
    return (
        <section className="rounded-xl border border-indigo-200 bg-indigo-50/60 p-5 dark:border-indigo-900/60 dark:bg-indigo-950/20">
            <p className="text-sm font-semibold text-indigo-950 dark:text-indigo-100">
                Related SEO workspaces
            </p>

            <div className="mt-3 space-y-2">
                <Link
                    href={route(
                        'admin.seo.overview',
                    )}
                    className="block text-sm font-medium text-indigo-700 hover:text-indigo-800 dark:text-indigo-300"
                >
                    SEO Overview
                </Link>

                <Link
                    href={route(
                        'admin.seo.suggestions.index',
                    )}
                    className="block text-sm font-medium text-indigo-700 hover:text-indigo-800 dark:text-indigo-300"
                >
                    AI Suggestions Inbox
                </Link>

                <Link
                    href={route(
                        'admin.seo.technical-health',
                    )}
                    className="block text-sm font-medium text-indigo-700 hover:text-indigo-800 dark:text-indigo-300"
                >
                    Technical Health
                </Link>
            </div>
        </section>
    )
}

function keyFor(
    item: Candidate,
) {
    return `${item.entity_type}:${item.entity_id}`
}

function formatDateTime(
    value: string | null,
) {
    if (!value) {
        return '—'
    }

    const date =
        new Date(value)

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
    ).format(date)
}

const inputClasses =
    'block h-10 w-full rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 shadow-sm outline-none transition placeholder:text-neutral-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:placeholder:text-neutral-600'
