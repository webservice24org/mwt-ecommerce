import AdminLayout from '@/Layouts/Admin/AdminLayout'
import {
    Head,
    Link,
    useForm,
} from '@inertiajs/react'
import {
    Bot,
    BrainCircuit,
    History,
    Save,
    Search,
    ShieldCheck,
} from 'lucide-react'
import {
    FormEvent,
} from 'react'

type Policy = {
    allow_oai_search_bot: boolean
    allow_gpt_bot: boolean
    allow_google_extended: boolean
    version: number
}

type PolicySnapshot = {
    allow_oai_search_bot: boolean
    allow_gpt_bot: boolean
    allow_google_extended: boolean
}

type HistoryItem = {
    id: number
    version: number
    before_policy: PolicySnapshot | null
    after_policy: PolicySnapshot
    changed_by_admin_id: number | null
    created_at: string
}

type Props = {
    policy: Policy
    robotsPreview: string
    history: HistoryItem[]
    platformNotes: {
        oai_search_bot: string
        gpt_bot: string
        google_extended: string
        chatgpt_user: string
        shared_training_token: string
    }
}

type FormData = {
    allow_oai_search_bot: boolean
    allow_gpt_bot: boolean
    allow_google_extended: boolean
    version: number
}

export default function CrawlerPolicy({
    policy,
    robotsPreview,
    history,
    platformNotes,
}: Props) {
    const {
        data,
        setData,
        put,
        processing,
        errors,
        isDirty,
        recentlySuccessful,
    } = useForm<FormData>({
        allow_oai_search_bot:
            policy.allow_oai_search_bot,

        allow_gpt_bot:
            policy.allow_gpt_bot,

        allow_google_extended:
            policy.allow_google_extended,

        version:
            policy.version,
    })

    const submit = (
        event: FormEvent<HTMLFormElement>,
    ) => {
        event.preventDefault()

        put(
            route(
                'admin.seo.generative-search.crawlers.update',
            ),
            {
                preserveScroll:
                    true,
            },
        )
    }

    return (
        <AdminLayout title="">
            <Head title="AI Crawler Policy" />

            <form
                onSubmit={submit}
                className="space-y-6"
            >
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <Link
                            href={route(
                                'admin.seo.settings.edit',
                            )}
                            className="text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                        >
                            SEO settings
                        </Link>

                        <h1 className="mt-2 text-2xl font-bold tracking-tight text-neutral-950 dark:text-neutral-100">
                            AI Crawler / Grounding Policy
                        </h1>

                        <p className="mt-2 max-w-3xl text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                            Manage search/grounding and model-training crawler preferences independently without replacing the existing 10.4D robots policy.
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        {recentlySuccessful && (
                            <span className="text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                Saved
                            </span>
                        )}

                        <button
                            type="submit"
                            disabled={
                                processing ||
                                !isDirty
                            }
                            className="inline-flex h-10 items-center gap-2 rounded-lg bg-neutral-950 px-4 text-sm font-semibold text-white hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-40 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200"
                        >
                            <Save className="h-4 w-4" />
                            {processing
                                ? 'Saving...'
                                : 'Save policy'}
                        </button>
                    </div>
                </div>

                {errors.version && (
                    <div className="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900/60 dark:bg-red-950/20 dark:text-red-300">
                        {errors.version}
                    </div>
                )}

                <section className="rounded-xl border border-amber-200 bg-amber-50/70 p-4 dark:border-amber-900/60 dark:bg-amber-950/20">
                    <div className="flex items-start gap-3">
                        <ShieldCheck className="mt-0.5 h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400" />

                        <div>
                            <p className="text-sm font-semibold text-amber-950 dark:text-amber-100">
                                Separate purposes, separate controls
                            </p>

                            <p className="mt-1 text-sm leading-6 text-amber-800/80 dark:text-amber-300/80">
                                Ordinary search indexing remains controlled by the existing SEO robots/indexation system. These switches only add crawler-specific opt-outs when disabled.
                            </p>
                        </div>
                    </div>
                </section>

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
                    <main className="min-w-0 space-y-5">
                        <PolicyCard
                            icon={Search}
                            title="OAI-SearchBot"
                            subtitle="OpenAI search visibility / automatic search crawling"
                            checked={
                                data.allow_oai_search_bot
                            }
                            onChange={(
                                value,
                            ) =>
                                setData(
                                    'allow_oai_search_bot',
                                    value,
                                )
                            }
                            note={
                                platformNotes.oai_search_bot
                            }
                        />

                        <PolicyCard
                            icon={BrainCircuit}
                            title="GPTBot"
                            subtitle="OpenAI model-training crawler"
                            checked={
                                data.allow_gpt_bot
                            }
                            onChange={(
                                value,
                            ) =>
                                setData(
                                    'allow_gpt_bot',
                                    value,
                                )
                            }
                            note={
                                platformNotes.gpt_bot
                            }
                        />

                        <PolicyCard
                            icon={Bot}
                            title="Google-Extended"
                            subtitle="Google controlled Gemini training / grounding uses"
                            checked={
                                data.allow_google_extended
                            }
                            onChange={(
                                value,
                            ) =>
                                setData(
                                    'allow_google_extended',
                                    value,
                                )
                            }
                            note={
                                platformNotes.google_extended
                            }
                        />

                        <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                            <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">
                                Important non-switch controls
                            </h2>

                            <div className="mt-4 space-y-3 text-sm leading-6 text-neutral-600 dark:text-neutral-300">
                                <p>
                                    <strong>ChatGPT-User:</strong>{' '}
                                    {platformNotes.chatgpt_user}
                                </p>

                                <p>
                                    <strong>Shared AI-training token:</strong>{' '}
                                    {platformNotes.shared_training_token}
                                </p>
                            </div>
                        </section>

                        <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                            <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">
                                Managed robots.txt preview
                            </h2>

                            <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                Allowed crawlers inherit the normal 10.4D generic rules. Only disabled crawlers receive a dedicated full-site disallow group.
                            </p>

                            <pre className="mt-4 max-h-[420px] overflow-auto rounded-lg bg-neutral-950 p-4 text-xs leading-6 text-neutral-100">
                                {robotsPreview}
                            </pre>
                        </section>
                    </main>

                    <aside className="space-y-5 xl:sticky xl:top-6 xl:self-start">
                        <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                            <div className="flex items-center gap-2">
                                <History className="h-5 w-5 text-neutral-600 dark:text-neutral-300" />

                                <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">
                                    Policy history
                                </h2>
                            </div>

                            <p className="mt-2 text-xs text-neutral-500 dark:text-neutral-400">
                                Current version: {policy.version}
                            </p>

                            {history.length === 0 ? (
                                <p className="mt-4 text-sm text-neutral-500 dark:text-neutral-400">
                                    No policy changes have been recorded yet.
                                </p>
                            ) : (
                                <div className="mt-4 space-y-3">
                                    {history.map(
                                        (
                                            event,
                                        ) => (
                                            <div
                                                key={
                                                    event.id
                                                }
                                                className="rounded-lg border border-neutral-200 p-3 dark:border-neutral-800"
                                            >
                                                <div className="flex items-center justify-between gap-3">
                                                    <span className="text-xs font-semibold text-neutral-900 dark:text-neutral-100">
                                                        Version {event.version}
                                                    </span>

                                                    <span className="text-[11px] text-neutral-400">
                                                        #{event.id}
                                                    </span>
                                                </div>

                                                <p className="mt-2 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                                                    OAI Search: {state(event.after_policy.allow_oai_search_bot)}
                                                    {' • '}
                                                    GPTBot: {state(event.after_policy.allow_gpt_bot)}
                                                    {' • '}
                                                    Google-Extended: {state(event.after_policy.allow_google_extended)}
                                                </p>

                                                <p className="mt-2 text-[11px] text-neutral-400">
                                                    {formatDateTime(
                                                        event.created_at,
                                                    )}
                                                </p>
                                            </div>
                                        ),
                                    )}
                                </div>
                            )}
                        </section>

                        <section className="rounded-xl border border-indigo-200 bg-indigo-50/60 p-5 dark:border-indigo-900/60 dark:bg-indigo-950/20">
                            <p className="text-sm font-semibold text-indigo-950 dark:text-indigo-100">
                                Default behavior
                            </p>

                            <p className="mt-2 text-sm leading-6 text-indigo-800/80 dark:text-indigo-300/80">
                                Initial values are all allowed so L.2 preserves the site’s prior effective crawl behavior until an administrator explicitly opts out.
                            </p>
                        </section>
                    </aside>
                </div>
            </form>
        </AdminLayout>
    )
}

function PolicyCard({
    icon: Icon,
    title,
    subtitle,
    checked,
    onChange,
    note,
}: {
    icon: typeof Search
    title: string
    subtitle: string
    checked: boolean
    onChange: (
        value: boolean,
    ) => void
    note: string
}) {
    return (
        <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div className="flex min-w-0 items-start gap-3">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-neutral-100 text-neutral-700 dark:bg-neutral-900 dark:text-neutral-300">
                        <Icon className="h-5 w-5" />
                    </div>

                    <div>
                        <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">
                            {title}
                        </h2>

                        <p className="mt-1 text-xs font-medium text-neutral-500 dark:text-neutral-400">
                            {subtitle}
                        </p>

                        <p className="mt-3 max-w-3xl text-sm leading-6 text-neutral-600 dark:text-neutral-300">
                            {note}
                        </p>
                    </div>
                </div>

                <label className="inline-flex shrink-0 items-center gap-3 rounded-lg border border-neutral-200 px-3 py-2 dark:border-neutral-700">
                    <input
                        type="checkbox"
                        checked={checked}
                        onChange={(
                            event,
                        ) =>
                            onChange(
                                event.target.checked,
                            )
                        }
                        className="h-4 w-4 rounded border-neutral-300"
                    />

                    <span className="text-sm font-semibold text-neutral-800 dark:text-neutral-200">
                        {checked
                            ? 'Allowed'
                            : 'Disallowed'}
                    </span>
                </label>
            </div>
        </section>
    )
}

function state(
    allowed: boolean,
): string {
    return allowed
        ? 'allow'
        : 'block'
}

function formatDateTime(
    value: string,
): string {
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
