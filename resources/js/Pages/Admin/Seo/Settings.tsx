import AdminLayout from '@/Layouts/Admin/AdminLayout'
import { Head, Link, useForm } from '@inertiajs/react'
import {
    Bot,
    Building2,
    Check,
    ExternalLink,
    Globe2,
    Image,
    Link2,
    Plus,
    Save,
    Search,
    Settings2,
    Share2,
    ShieldCheck,
    Sparkles,
    Trash2,
} from 'lucide-react'
import type {
    FormEvent,
    ReactNode,
} from 'react'

type SeoSettings = {
    site_name: string | null

    title_separator: string

    default_title_template: string

    default_meta_description:
        | string
        | null

    canonical_host:
        | string
        | null

    default_robots_index: boolean

    default_robots_follow: boolean

    default_og_image:
        | string
        | null

    twitter_card: string

    organization_name:
        | string
        | null

    organization_logo:
        | string
        | null

    organization_social_profiles:
        string[]

    google_site_verification:
        | string
        | null

    bing_site_verification:
        | string
        | null

    enable_structured_data: boolean

    enable_open_graph: boolean

    enable_twitter_cards: boolean

    enable_sitemap: boolean
}

type SelectOption = {
    value: string
    label: string
}

type Props = {
    settings: SeoSettings

    options: {
        twitter_cards: SelectOption[]

        title_tokens: string[]
    }
}

type FormData = {
    site_name: string

    title_separator: string

    default_title_template: string

    default_meta_description: string

    canonical_host: string

    default_robots_index: boolean

    default_robots_follow: boolean

    default_og_image: string

    twitter_card: string

    organization_name: string

    organization_logo: string

    organization_social_profiles:
        string[]

    google_site_verification:
        string

    bing_site_verification:
        string

    enable_structured_data: boolean

    enable_open_graph: boolean

    enable_twitter_cards: boolean

    enable_sitemap: boolean
}

export default function Settings({
    settings,
    options,
}: Props) {
    const {
        data,
        setData,
        put,
        processing,
        errors,
        recentlySuccessful,
        isDirty,
    } = useForm<FormData>({
        site_name:
            settings.site_name ??
            '',

        title_separator:
            settings.title_separator,

        default_title_template:
            settings.default_title_template,

        default_meta_description:
            settings.default_meta_description ??
            '',

        canonical_host:
            settings.canonical_host ??
            '',

        default_robots_index:
            settings.default_robots_index,

        default_robots_follow:
            settings.default_robots_follow,

        default_og_image:
            settings.default_og_image ??
            '',

        twitter_card:
            settings.twitter_card,

        organization_name:
            settings.organization_name ??
            '',

        organization_logo:
            settings.organization_logo ??
            '',

        organization_social_profiles:
            settings
                .organization_social_profiles ??
            [],

        google_site_verification:
            settings.google_site_verification ??
            '',

        bing_site_verification:
            settings.bing_site_verification ??
            '',

        enable_structured_data:
            settings.enable_structured_data,

        enable_open_graph:
            settings.enable_open_graph,

        enable_twitter_cards:
            settings.enable_twitter_cards,

        enable_sitemap:
            settings.enable_sitemap,
    })

    const submit = (
        event: FormEvent<HTMLFormElement>,
    ) => {
        event.preventDefault()

        put(
            route(
                'admin.seo.settings.update',
            ),
            {
                preserveScroll: true,
            },
        )
    }

    const addSocialProfile = () => {
        setData(
            'organization_social_profiles',
            [
                ...data.organization_social_profiles,
                '',
            ],
        )
    }

    const updateSocialProfile = (
        index: number,
        value: string,
    ) => {
        const next =
            [
                ...data
                    .organization_social_profiles,
            ]

        next[index] =
            value

        setData(
            'organization_social_profiles',
            next,
        )
    }

    const removeSocialProfile = (
        index: number,
    ) => {
        setData(
            'organization_social_profiles',
            data
                .organization_social_profiles
                .filter(
                    (
                        _profile,
                        profileIndex,
                    ) =>
                        profileIndex !==
                        index,
                ),
        )
    }

    const insertTitleToken = (
        token: string,
    ) => {
        const current =
            data
                .default_title_template
                .trimEnd()

        setData(
            'default_title_template',
            current === ''
                ? token
                : `${current} ${token}`,
        )
    }

    return (
        <AdminLayout title=''>
            <Head title="SEO Settings" />

            <form
                onSubmit={submit}
                className="space-y-6"
            >
                <PageHeader
                    processing={
                        processing
                    }
                    recentlySuccessful={
                        recentlySuccessful
                    }
                    isDirty={isDirty}
                />

                <SeoNotice />

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
                    <div className="min-w-0 space-y-6">
                        <SettingsCard
                            icon={Globe2}
                            title="Site identity"
                            description="Global identity used when an individual page does not provide its own SEO values."
                        >
                            <div className="grid gap-5 md:grid-cols-2">
                                <Field
                                    label="Site name"
                                    description="Your public storefront or brand name."
                                    error={
                                        errors.site_name
                                    }
                                >
                                    <Input
                                        value={
                                            data.site_name
                                        }
                                        onChange={(
                                            value,
                                        ) =>
                                            setData(
                                                'site_name',
                                                value,
                                            )
                                        }
                                        placeholder="MWT Store"
                                    />
                                </Field>

                                <Field
                                    label="Title separator"
                                    description="Used between the page title and site name."
                                    error={
                                        errors.title_separator
                                    }
                                >
                                    <Input
                                        value={
                                            data.title_separator
                                        }
                                        onChange={(
                                            value,
                                        ) =>
                                            setData(
                                                'title_separator',
                                                value,
                                            )
                                        }
                                        placeholder="|"
                                    />
                                </Field>
                            </div>
                        </SettingsCard>

                        <SettingsCard
                            icon={Search}
                            title="Search metadata defaults"
                            description="Fallback metadata used when a product, category, brand or page has no explicit SEO override."
                        >
                            <div className="space-y-5">
                                <Field
                                    label="Default title template"
                                    description="The {page_title} token is required."
                                    error={
                                        errors.default_title_template
                                    }
                                >
                                    <Input
                                        value={
                                            data.default_title_template
                                        }
                                        onChange={(
                                            value,
                                        ) =>
                                            setData(
                                                'default_title_template',
                                                value,
                                            )
                                        }
                                    />

                                    <div className="mt-3 flex flex-wrap gap-2">
                                        {options.title_tokens.map(
                                            (
                                                token,
                                            ) => (
                                                <button
                                                    key={
                                                        token
                                                    }
                                                    type="button"
                                                    onClick={() =>
                                                        insertTitleToken(
                                                            token,
                                                        )
                                                    }
                                                    className="rounded-md border border-neutral-200 bg-neutral-50 px-2.5 py-1 text-xs font-medium text-neutral-700 transition hover:bg-neutral-100 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-300 dark:hover:bg-neutral-800"
                                                >
                                                    {
                                                        token
                                                    }
                                                </button>
                                            ),
                                        )}
                                    </div>
                                </Field>

                                <Field
                                    label="Default meta description"
                                    description="Fallback description for pages without entity-specific SEO copy."
                                    error={
                                        errors.default_meta_description
                                    }
                                >
                                    <Textarea
                                        value={
                                            data.default_meta_description
                                        }
                                        onChange={(
                                            value,
                                        ) =>
                                            setData(
                                                'default_meta_description',
                                                value,
                                            )
                                        }
                                        placeholder="Shop quality products online..."
                                        rows={4}
                                    />

                                    <CharacterCount
                                        current={
                                            data
                                                .default_meta_description
                                                .length
                                        }
                                        recommended="Aim for concise, useful copy rather than filling a fixed character limit."
                                    />
                                </Field>
                            </div>
                        </SettingsCard>

                        <SettingsCard
                            icon={Link2}
                            title="Canonical & indexing defaults"
                            description="Control the canonical storefront host and default search-engine crawling behavior."
                        >
                            <div className="space-y-5">
                                <Field
                                    label="Canonical host"
                                    description="Use only the scheme and hostname. Do not include a page path."
                                    error={
                                        errors.canonical_host
                                    }
                                >
                                    <Input
                                        value={
                                            data.canonical_host
                                        }
                                        onChange={(
                                            value,
                                        ) =>
                                            setData(
                                                'canonical_host',
                                                value,
                                            )
                                        }
                                        placeholder="https://example.com"
                                    />
                                </Field>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <ToggleCard
                                        title="Index pages by default"
                                        description="Allow eligible storefront pages to appear in search results unless a page-specific rule overrides it."
                                        checked={
                                            data.default_robots_index
                                        }
                                        onChange={(
                                            checked,
                                        ) =>
                                            setData(
                                                'default_robots_index',
                                                checked,
                                            )
                                        }
                                    />

                                    <ToggleCard
                                        title="Follow links by default"
                                        description="Allow search crawlers to follow links from eligible storefront pages."
                                        checked={
                                            data.default_robots_follow
                                        }
                                        onChange={(
                                            checked,
                                        ) =>
                                            setData(
                                                'default_robots_follow',
                                                checked,
                                            )
                                        }
                                    />
                                </div>
                            </div>
                        </SettingsCard>

                        <SettingsCard
                            icon={Share2}
                            title="Social sharing defaults"
                            description="Default Open Graph and Twitter/X metadata used when entity-specific social metadata is unavailable."
                        >
                            <div className="space-y-5">
                                <Field
                                    label="Default Open Graph image"
                                    description="Absolute image URL used as the fallback social preview image."
                                    error={
                                        errors.default_og_image
                                    }
                                >
                                    <Input
                                        value={
                                            data.default_og_image
                                        }
                                        onChange={(
                                            value,
                                        ) =>
                                            setData(
                                                'default_og_image',
                                                value,
                                            )
                                        }
                                        placeholder="https://example.com/images/og-default.jpg"
                                    />
                                </Field>

                                <Field
                                    label="Twitter/X card"
                                    description="Default social card presentation."
                                    error={
                                        errors.twitter_card
                                    }
                                >
                                    <select
                                        value={
                                            data.twitter_card
                                        }
                                        onChange={(
                                            event,
                                        ) =>
                                            setData(
                                                'twitter_card',
                                                event
                                                    .target
                                                    .value,
                                            )
                                        }
                                        className={inputClasses}
                                    >
                                        {options.twitter_cards.map(
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
                                </Field>

                                {data.default_og_image !==
                                    '' && (
                                    <ImagePreview
                                        src={
                                            data.default_og_image
                                        }
                                    />
                                )}
                            </div>
                        </SettingsCard>

                        <SettingsCard
                            icon={Building2}
                            title="Organization identity"
                            description="Business identity used later by Organization and merchant structured data."
                        >
                            <div className="space-y-5">
                                <div className="grid gap-5 md:grid-cols-2">
                                    <Field
                                        label="Organization name"
                                        error={
                                            errors.organization_name
                                        }
                                    >
                                        <Input
                                            value={
                                                data.organization_name
                                            }
                                            onChange={(
                                                value,
                                            ) =>
                                                setData(
                                                    'organization_name',
                                                    value,
                                                )
                                            }
                                            placeholder="MicroWeb Technology"
                                        />
                                    </Field>

                                    <Field
                                        label="Organization logo"
                                        description="Absolute URL to the public organization logo."
                                        error={
                                            errors.organization_logo
                                        }
                                    >
                                        <Input
                                            value={
                                                data.organization_logo
                                            }
                                            onChange={(
                                                value,
                                            ) =>
                                                setData(
                                                    'organization_logo',
                                                    value,
                                                )
                                            }
                                            placeholder="https://example.com/logo.png"
                                        />
                                    </Field>
                                </div>

                                <div>
                                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <p className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                                Social
                                                profiles
                                            </p>

                                            <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                                                Public
                                                official
                                                profiles
                                                that may
                                                later be
                                                included in
                                                organization
                                                structured
                                                data.
                                            </p>
                                        </div>

                                        <button
                                            type="button"
                                            onClick={
                                                addSocialProfile
                                            }
                                            className="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg border border-neutral-300 bg-white px-3 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-200 dark:hover:bg-neutral-900"
                                        >
                                            <Plus className="h-4 w-4" />

                                            Add
                                            profile
                                        </button>
                                    </div>

                                    <div className="mt-4 space-y-3">
                                        {data
                                            .organization_social_profiles
                                            .length ===
                                        0 ? (
                                            <EmptySocialProfiles />
                                        ) : (
                                            data.organization_social_profiles.map(
                                                (
                                                    profile,
                                                    index,
                                                ) => (
                                                    <div
                                                        key={
                                                            index
                                                        }
                                                        className="flex gap-2"
                                                    >
                                                        <input
                                                            type="url"
                                                            value={
                                                                profile
                                                            }
                                                            onChange={(
                                                                event,
                                                            ) =>
                                                                updateSocialProfile(
                                                                    index,
                                                                    event
                                                                        .target
                                                                        .value,
                                                                )
                                                            }
                                                            placeholder="https://..."
                                                            className={
                                                                inputClasses
                                                            }
                                                        />

                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                removeSocialProfile(
                                                                    index,
                                                                )
                                                            }
                                                            aria-label="Remove social profile"
                                                            className="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-red-200 text-red-600 transition hover:bg-red-50 dark:border-red-900/60 dark:text-red-400 dark:hover:bg-red-950/30"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </button>
                                                    </div>
                                                ),
                                            )
                                        )}

                                        {errors.organization_social_profiles && (
                                            <ErrorMessage
                                                message={
                                                    errors.organization_social_profiles
                                                }
                                            />
                                        )}
                                    </div>
                                </div>
                            </div>
                        </SettingsCard>

                        <SettingsCard
                            icon={
                                ShieldCheck
                            }
                            title="Search engine verification"
                            description="Store verification values used to prove ownership of the storefront to search platforms."
                        >
                            <div className="grid gap-5 md:grid-cols-2">
                                <Field
                                    label="Google verification"
                                    description="Enter only the verification token/value, not an entire meta tag."
                                    error={
                                        errors.google_site_verification
                                    }
                                >
                                    <Input
                                        value={
                                            data.google_site_verification
                                        }
                                        onChange={(
                                            value,
                                        ) =>
                                            setData(
                                                'google_site_verification',
                                                value,
                                            )
                                        }
                                        placeholder="Verification token"
                                    />
                                </Field>

                                <Field
                                    label="Bing verification"
                                    description="Enter the Bing site verification value."
                                    error={
                                        errors.bing_site_verification
                                    }
                                >
                                    <Input
                                        value={
                                            data.bing_site_verification
                                        }
                                        onChange={(
                                            value,
                                        ) =>
                                            setData(
                                                'bing_site_verification',
                                                value,
                                            )
                                        }
                                        placeholder="Verification token"
                                    />
                                </Field>
                            </div>
                        </SettingsCard>

                        <SettingsCard
                            icon={Settings2}
                            title="SEO feature controls"
                            description="Global feature switches. Entity-specific controls will be added in later SEO phases."
                        >
                            <div className="grid gap-4 md:grid-cols-2">
                                <ToggleCard
                                    title="Structured data"
                                    description="Allow JSON-LD structured data to be generated when supported."
                                    checked={
                                        data.enable_structured_data
                                    }
                                    onChange={(
                                        checked,
                                    ) =>
                                        setData(
                                            'enable_structured_data',
                                            checked,
                                        )
                                    }
                                />

                                <ToggleCard
                                    title="Open Graph"
                                    description="Enable social Open Graph metadata."
                                    checked={
                                        data.enable_open_graph
                                    }
                                    onChange={(
                                        checked,
                                    ) =>
                                        setData(
                                            'enable_open_graph',
                                            checked,
                                        )
                                    }
                                />

                                <ToggleCard
                                    title="Twitter/X cards"
                                    description="Enable Twitter/X card metadata."
                                    checked={
                                        data.enable_twitter_cards
                                    }
                                    onChange={(
                                        checked,
                                    ) =>
                                        setData(
                                            'enable_twitter_cards',
                                            checked,
                                        )
                                    }
                                />

                                <ToggleCard
                                    title="XML sitemap"
                                    description="Allow sitemap generation when the sitemap engine is introduced."
                                    checked={
                                        data.enable_sitemap
                                    }
                                    onChange={(
                                        checked,
                                    ) =>
                                        setData(
                                            'enable_sitemap',
                                            checked,
                                        )
                                    }
                                />
                            </div>
                        </SettingsCard>
                    </div>

                    <aside className="space-y-6 xl:sticky xl:top-6 xl:self-start">
                        <SeoStatusCard
                            data={data}
                        />

                        <AiSeoPreviewCard />

                        <SaveCard
                            processing={
                                processing
                            }
                            recentlySuccessful={
                                recentlySuccessful
                            }
                            isDirty={
                                isDirty
                            }
                        />
                    </aside>
                </div>
            </form>
        </AdminLayout>
    )
}

function PageHeader({
    processing,
    recentlySuccessful,
    isDirty,
}: {
    processing: boolean
    recentlySuccessful: boolean
    isDirty: boolean
}) {
    return (
        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div className="flex items-center gap-2 text-sm font-medium text-indigo-600 dark:text-indigo-400">
                    <Search className="h-4 w-4" />

                    Search optimization
                </div>

                <h1 className="mt-2 text-2xl font-bold tracking-tight text-neutral-950 dark:text-neutral-100">
                    SEO Settings
                </h1>

                <p className="mt-2 max-w-3xl text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                    Configure global
                    search-engine,
                    canonical, social and
                    organization defaults.
                    Individual products,
                    categories, brands and
                    pages will be able to
                    override these values in
                    later SEO stages.
                </p>
            </div>
            <div className='flex gap-2'>
                <Link
                href={route(
                    'admin.seo.generative-search.crawlers.index',
                )}
                className="inline-flex h-10 items-center justify-center rounded-lg border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-200 dark:hover:bg-neutral-900"
            >
                AI crawler policy
            </Link>
            <button
                type="submit"
                disabled={
                    processing ||
                    !isDirty
                }
                className="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg bg-neutral-950 px-4 text-sm font-semibold text-white transition hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200"
            >
                {recentlySuccessful ? (
                    <>
                        <Check className="h-4 w-4" />
                        Saved
                    </>
                ) : (
                    <>
                        <Save className="h-4 w-4" />

                        {processing
                            ? 'Saving...'
                            : 'Save settings'}
                    </>
                )}
            </button>
            </div>
            
        </div>
    )
}

function SeoNotice() {
    return (
        <div className="rounded-xl border border-indigo-200 bg-indigo-50/70 p-4 dark:border-indigo-900/60 dark:bg-indigo-950/20">
            <div className="flex items-start gap-3">
                <Sparkles className="mt-0.5 h-5 w-5 shrink-0 text-indigo-600 dark:text-indigo-400" />

                <div>
                    <p className="text-sm font-semibold text-indigo-950 dark:text-indigo-100">
                        SEO foundation
                    </p>

                    <p className="mt-1 text-sm leading-6 text-indigo-800/80 dark:text-indigo-300/80">
                        These settings are
                        authoritative global
                        defaults. AI-driven SEO
                        will be layered on top as
                        recommendations and
                        drafts; AI will not
                        silently override
                        canonical, robots or
                        structured-data rules.
                    </p>
                </div>
            </div>
        </div>
    )
}

function SettingsCard({
    icon: Icon,
    title,
    description,
    children,
}: {
    icon: typeof Search
    title: string
    description: string
    children: ReactNode
}) {
    return (
        <section className="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <div className="border-b border-neutral-200 px-5 py-4 dark:border-neutral-800 sm:px-6">
                <div className="flex items-start gap-3">
                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-neutral-100 text-neutral-700 dark:bg-neutral-900 dark:text-neutral-300">
                        <Icon className="h-4 w-4" />
                    </div>

                    <div className="min-w-0">
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

function Field({
    label,
    description,
    error,
    children,
}: {
    label: string
    description?: string
    error?: string
    children: ReactNode
}) {
    return (
        <label className="block">
            <span className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                {label}
            </span>

            {description && (
                <span className="mt-1 block text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                    {description}
                </span>
            )}

            <div className="mt-2">
                {children}
            </div>

            {error && (
                <ErrorMessage
                    message={error}
                />
            )}
        </label>
    )
}

function Input({
    value,
    onChange,
    placeholder,
}: {
    value: string
    onChange: (value: string) => void
    placeholder?: string
}) {
    return (
        <input
            type="text"
            value={value}
            onChange={(event) =>
                onChange(
                    event.target.value,
                )
            }
            placeholder={placeholder}
            className={inputClasses}
        />
    )
}

function Textarea({
    value,
    onChange,
    placeholder,
    rows,
}: {
    value: string
    onChange: (value: string) => void
    placeholder?: string
    rows: number
}) {
    return (
        <textarea
            value={value}
            onChange={(event) =>
                onChange(
                    event.target.value,
                )
            }
            placeholder={placeholder}
            rows={rows}
            className={`${inputClasses} resize-y`}
        />
    )
}

function ToggleCard({
    title,
    description,
    checked,
    onChange,
}: {
    title: string
    description: string
    checked: boolean
    onChange: (checked: boolean) => void
}) {
    return (
        <button
            type="button"
            onClick={() =>
                onChange(!checked)
            }
            aria-pressed={checked}
            className={[
                'flex min-h-28 w-full items-start justify-between gap-4 rounded-xl border p-4 text-left transition',
                checked
                    ? 'border-indigo-300 bg-indigo-50/60 dark:border-indigo-800 dark:bg-indigo-950/20'
                    : 'border-neutral-200 bg-white hover:bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-950 dark:hover:bg-neutral-900',
            ].join(' ')}
        >
            <span>
                <span className="block text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                    {title}
                </span>

                <span className="mt-1.5 block text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                    {description}
                </span>
            </span>

            <ToggleIndicator
                checked={checked}
            />
        </button>
    )
}

function ToggleIndicator({
    checked,
}: {
    checked: boolean
}) {
    return (
        <span
            className={[
                'relative mt-0.5 inline-flex h-6 w-11 shrink-0 rounded-full transition',
                checked
                    ? 'bg-indigo-600'
                    : 'bg-neutral-300 dark:bg-neutral-700',
            ].join(' ')}
        >
            <span
                className={[
                    'absolute top-1 h-4 w-4 rounded-full bg-white shadow-sm transition-transform',
                    checked
                        ? 'translate-x-6'
                        : 'translate-x-1',
                ].join(' ')}
            />
        </span>
    )
}

function ErrorMessage({
    message,
}: {
    message: string
}) {
    return (
        <p className="mt-2 text-xs font-medium text-red-600 dark:text-red-400">
            {message}
        </p>
    )
}

function CharacterCount({
    current,
    recommended,
}: {
    current: number
    recommended: string
}) {
    return (
        <div className="mt-2 flex flex-col gap-1 text-xs text-neutral-500 dark:text-neutral-400 sm:flex-row sm:items-center sm:justify-between">
            <span>
                {current} characters
            </span>

            <span>
                {recommended}
            </span>
        </div>
    )
}

function ImagePreview({
    src,
}: {
    src: string
}) {
    return (
        <div className="overflow-hidden rounded-xl border border-neutral-200 bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-900">
            <div className="flex items-center justify-between border-b border-neutral-200 px-4 py-3 dark:border-neutral-800">
                <div className="flex items-center gap-2 text-sm font-semibold text-neutral-800 dark:text-neutral-200">
                    <Image className="h-4 w-4" />
                    Preview
                </div>

                <a
                    href={src}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:underline dark:text-indigo-400"
                >
                    Open
                    <ExternalLink className="h-3.5 w-3.5" />
                </a>
            </div>

            <div className="aspect-[1.91/1] max-h-64 overflow-hidden bg-neutral-100 dark:bg-neutral-900">
                <img
                    src={src}
                    alt=""
                    className="h-full w-full object-cover"
                    onError={(
                        event,
                    ) => {
                        event.currentTarget.style.display =
                            'none'
                    }}
                />
            </div>
        </div>
    )
}

function EmptySocialProfiles() {
    return (
        <div className="rounded-lg border border-dashed border-neutral-300 px-4 py-6 text-center dark:border-neutral-700">
            <Share2 className="mx-auto h-5 w-5 text-neutral-400" />

            <p className="mt-2 text-sm font-medium text-neutral-700 dark:text-neutral-300">
                No social profiles
            </p>

            <p className="mt-1 text-xs text-neutral-500">
                Add official public
                organization profiles when
                available.
            </p>
        </div>
    )
}

function SeoStatusCard({
    data,
}: {
    data: FormData
}) {
    const checks = [
        {
            label:
                'Site name',
            complete:
                data.site_name.trim() !==
                '',
        },
        {
            label:
                'Canonical host',
            complete:
                data.canonical_host.trim() !==
                '',
        },
        {
            label:
                'Meta description',
            complete:
                data
                    .default_meta_description
                    .trim() !== '',
        },
        {
            label:
                'Organization',
            complete:
                data
                    .organization_name
                    .trim() !== '',
        },
        {
            label:
                'Default OG image',
            complete:
                data
                    .default_og_image
                    .trim() !== '',
        },
    ]

    const completed =
        checks.filter(
            (check) =>
                check.complete,
        ).length

    return (
        <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <div className="flex items-center gap-2">
                <ShieldCheck className="h-5 w-5 text-emerald-600" />

                <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">
                    Foundation status
                </h2>
            </div>

            <div className="mt-4">
                <div className="flex items-center justify-between text-xs">
                    <span className="font-medium text-neutral-600 dark:text-neutral-400">
                        Setup progress
                    </span>

                    <span className="font-semibold text-neutral-900 dark:text-neutral-100">
                        {completed}/
                        {checks.length}
                    </span>
                </div>

                <div className="mt-2 h-2 overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800">
                    <div
                        className="h-full rounded-full bg-emerald-500 transition-all"
                        style={{
                            width: `${
                                (completed /
                                    checks.length) *
                                100
                            }%`,
                        }}
                    />
                </div>
            </div>

            <div className="mt-5 space-y-3">
                {checks.map(
                    (check) => (
                        <div
                            key={
                                check.label
                            }
                            className="flex items-center justify-between gap-3 text-sm"
                        >
                            <span className="text-neutral-600 dark:text-neutral-400">
                                {
                                    check.label
                                }
                            </span>

                            <span
                                className={[
                                    'inline-flex h-6 w-6 items-center justify-center rounded-full',
                                    check.complete
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400'
                                        : 'bg-neutral-100 text-neutral-400 dark:bg-neutral-900',
                                ].join(
                                    ' ',
                                )}
                            >
                                {check.complete && (
                                    <Check className="h-3.5 w-3.5" />
                                )}
                            </span>
                        </div>
                    ),
                )}
            </div>
        </section>
    )
}

function AiSeoPreviewCard() {
    return (
        <section className="rounded-xl border border-violet-200 bg-gradient-to-br from-violet-50 to-indigo-50 p-5 dark:border-violet-900/50 dark:from-violet-950/20 dark:to-indigo-950/20">
            <div className="flex items-center gap-2">
                <Bot className="h-5 w-5 text-violet-600 dark:text-violet-400" />

                <h2 className="font-semibold text-violet-950 dark:text-violet-100">
                    AI SEO
                </h2>
            </div>

            <p className="mt-3 text-sm leading-6 text-violet-800/80 dark:text-violet-300/80">
                AI-assisted titles,
                descriptions, alt text,
                search-intent analysis and
                optimization suggestions will
                be added after the deterministic
                SEO foundation is complete.
            </p>

            <div className="mt-4 inline-flex items-center gap-2 rounded-full border border-violet-200 bg-white/70 px-3 py-1.5 text-xs font-semibold text-violet-700 dark:border-violet-800 dark:bg-violet-950/40 dark:text-violet-300">
                <Sparkles className="h-3.5 w-3.5" />

                Foundation first
            </div>
        </section>
    )
}

function SaveCard({
    processing,
    recentlySuccessful,
    isDirty,
}: {
    processing: boolean
    recentlySuccessful: boolean
    isDirty: boolean
}) {
    return (
        <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">
                Save changes
            </h2>

            <p className="mt-2 text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                Settings are validated on the
                server and the global SEO cache
                is invalidated automatically
                after a successful update.
            </p>

            <button
                type="submit"
                disabled={
                    processing ||
                    !isDirty
                }
                className="mt-4 inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg bg-neutral-950 px-4 text-sm font-semibold text-white transition hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200"
            >
                {recentlySuccessful ? (
                    <>
                        <Check className="h-4 w-4" />
                        Saved
                    </>
                ) : (
                    <>
                        <Save className="h-4 w-4" />

                        {processing
                            ? 'Saving...'
                            : isDirty
                              ? 'Save SEO settings'
                              : 'No changes'}
                    </>
                )}
            </button>
        </section>
    )
}

const inputClasses =
    'block h-10 w-full rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 shadow-sm outline-none transition placeholder:text-neutral-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:placeholder:text-neutral-600'