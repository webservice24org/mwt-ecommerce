import type { FormEvent, ReactNode } from 'react'

import type {
    PageContentMode,
    PageFormOptions,
    PageFormValues,
    PageSection,
} from '@/types/page-builder'

interface Props {
    data: PageFormValues
    options: PageFormOptions
    errors: Partial<Record<keyof PageFormValues, string>>
    processing: boolean
    submitLabel: string
    sections: PageSection[]
    featuredImage: string | null
    onChange: <K extends keyof PageFormValues>(key: K, value: PageFormValues[K]) => void
    onSubmit: (event: FormEvent<HTMLFormElement>) => void
}

export default function PageForm({
    data,
    options,
    errors,
    processing,
    submitLabel,
    sections,
    featuredImage,
    onChange,
    onSubmit,
}: Props) {
    return (
        <form onSubmit={onSubmit} className="space-y-6">
            <div className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <section className={sectionClass}>
                        <h2 className="mb-5 text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                            Page Details
                        </h2>

                        <div className="space-y-5">
                            <Field label="Title" error={errors.title}>
                                <input
                                    type="text"
                                    value={data.title}
                                    onChange={(event) => onChange('title', event.target.value)}
                                    className={inputClass}
                                    required
                                    maxLength={255}
                                />
                            </Field>

                            <Field
                                label="Slug"
                                hint="Leave blank to generate it automatically from the title."
                                error={errors.slug}
                            >
                                <input
                                    type="text"
                                    value={data.slug}
                                    onChange={(event) => onChange('slug', event.target.value)}
                                    className={inputClass}
                                    maxLength={255}
                                />
                            </Field>
                        </div>
                    </section>

                    <section className={sectionClass}>
                        <div className="mb-5">
                            <h2 className="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                                Content Mode
                            </h2>

                            <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                Choose how this page should be rendered. Switching modes does not
                                delete content from the other mode.
                            </p>
                        </div>

                        {errors.content_mode && (
                            <p className="mb-4 text-sm text-red-600">{errors.content_mode}</p>
                        )}

                        <div
                            className="grid gap-4 sm:grid-cols-2"
                            role="radiogroup"
                            aria-label="Page content mode"
                        >
                            {options.content_modes.map((option) => {
                                const value = option.value as PageContentMode
                                const selected = data.content_mode === value

                                return (
                                    <label
                                        key={option.value}
                                        className={[
                                            'cursor-pointer rounded-xl border p-5 transition',
                                            selected
                                                ? 'border-neutral-900 bg-neutral-50 ring-1 ring-neutral-900 dark:border-neutral-100 dark:bg-neutral-900 dark:ring-neutral-100'
                                                : 'border-neutral-200 bg-white hover:border-neutral-400 dark:border-neutral-800 dark:bg-neutral-950 dark:hover:border-neutral-600',
                                        ].join(' ')}
                                    >
                                        <input
                                            type="radio"
                                            name="content_mode"
                                            value={value}
                                            checked={selected}
                                            onChange={() => onChange('content_mode', value)}
                                            className="sr-only"
                                        />

                                        <div className="flex items-start gap-3">
                                            <span
                                                aria-hidden="true"
                                                className={[
                                                    'mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border',
                                                    selected
                                                        ? 'border-neutral-900 dark:border-neutral-100'
                                                        : 'border-neutral-300 dark:border-neutral-700',
                                                ].join(' ')}
                                            >
                                                {selected && (
                                                    <span className="h-2.5 w-2.5 rounded-full bg-neutral-900 dark:bg-neutral-100" />
                                                )}
                                            </span>

                                            <span>
                                                <span className="block text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                                    {option.label}
                                                </span>

                                                <span className="mt-1 block text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                                                    {value === 'classic'
                                                        ? 'Create a traditional page with normal page content and a featured image.'
                                                        : 'Build the page from reusable, structured storefront sections.'}
                                                </span>
                                            </span>
                                        </div>
                                    </label>
                                )
                            })}
                        </div>
                    </section>

                    {data.content_mode === 'classic' ? (
                        <ClassicEditor
                            content={data.content}
                            featuredImage={featuredImage}
                            error={errors.content}
                            onChange={(value) => onChange('content', value)}
                        />
                    ) : (
                        <PageBuilderWorkspace sections={sections} />
                    )}

                    <section className={sectionClass}>
                        <h2 className="mb-5 text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                            SEO
                        </h2>

                        <div className="space-y-5">
                            <Field label="Meta Title" error={errors.meta_title}>
                                <input
                                    type="text"
                                    value={data.meta_title}
                                    onChange={(event) => onChange('meta_title', event.target.value)}
                                    className={inputClass}
                                    maxLength={255}
                                />
                            </Field>

                            <Field label="Meta Description" error={errors.meta_description}>
                                <textarea
                                    value={data.meta_description}
                                    onChange={(event) =>
                                        onChange('meta_description', event.target.value)
                                    }
                                    rows={5}
                                    className={inputClass}
                                    maxLength={500}
                                />
                            </Field>
                        </div>
                    </section>
                </div>

                <div className="space-y-6">
                    <section className={sectionClass}>
                        <h2 className="mb-5 text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                            Publishing
                        </h2>

                        <div className="space-y-5">
                            <Field label="Page Type" error={errors.type}>
                                <select
                                    value={data.type}
                                    onChange={(event) =>
                                        onChange(
                                            'type',
                                            event.target.value as PageFormValues['type'],
                                        )
                                    }
                                    className={inputClass}
                                >
                                    {options.types.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                            </Field>

                            <Field label="Status" error={errors.status}>
                                <select
                                    value={data.status}
                                    onChange={(event) =>
                                        onChange(
                                            'status',
                                            event.target.value as PageFormValues['status'],
                                        )
                                    }
                                    className={inputClass}
                                >
                                    {options.statuses.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                            </Field>

                            <Field
                                label="Publish At"
                                hint="Optional. Leave blank when no publication date is required."
                                error={errors.published_at}
                            >
                                <input
                                    type="datetime-local"
                                    value={data.published_at}
                                    onChange={(event) =>
                                        onChange('published_at', event.target.value)
                                    }
                                    className={inputClass}
                                />
                            </Field>
                        </div>
                    </section>

                    <section className={sectionClass}>
                        <h2 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                            Active Editor
                        </h2>

                        <p className="mt-2 text-sm text-neutral-500 dark:text-neutral-400">
                            {data.content_mode === 'classic'
                                ? 'Classic Editor is currently active for this page.'
                                : 'Page Builder is currently active for this page.'}
                        </p>
                    </section>

                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full rounded-lg bg-neutral-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-neutral-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-neutral-300"
                    >
                        {processing ? 'Saving...' : submitLabel}
                    </button>
                </div>
            </div>
        </form>
    )
}

interface ClassicEditorProps {
    content: string
    featuredImage: string | null
    error?: string
    onChange: (value: string) => void
}

function ClassicEditor({ content, featuredImage, error, onChange }: ClassicEditorProps) {
    return (
        <section className={sectionClass}>
            <div className="mb-5">
                <h2 className="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                    Classic Editor
                </h2>

                <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                    Write the main content for a traditional page.
                </p>
            </div>

            <div className="space-y-6">
                <Field
                    label="Content"
                    hint="A rich-text editor can replace this field later without changing the page content contract."
                    error={error}
                >
                    <textarea
                        value={content}
                        onChange={(event) => onChange(event.target.value)}
                        rows={14}
                        className={inputClass}
                        placeholder="Write your page content..."
                    />
                </Field>

                <div>
                    <div className="mb-1.5 text-sm font-medium text-neutral-800 dark:text-neutral-200">
                        Featured Image
                    </div>

                    {featuredImage ? (
                        <div className="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-800">
                            <img
                                src={featuredImage}
                                alt=""
                                className="max-h-72 w-full object-cover"
                            />
                        </div>
                    ) : (
                        <div className="flex min-h-36 items-center justify-center rounded-xl border border-dashed border-neutral-300 bg-neutral-50 p-6 text-center dark:border-neutral-700 dark:bg-neutral-900/50">
                            <div>
                                <p className="text-sm font-medium text-neutral-700 dark:text-neutral-200">
                                    No featured image
                                </p>

                                <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                                    Secure featured-image upload will be connected through the
                                    server-side media/storage workflow in a later builder step.
                                </p>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </section>
    )
}

interface PageBuilderWorkspaceProps {
    sections: PageSection[]
}

function PageBuilderWorkspace({ sections }: PageBuilderWorkspaceProps) {
    return (
        <section className={sectionClass}>
            <div className="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 className="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                        Page Builder
                    </h2>

                    <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                        Build this page from controlled storefront sections.
                    </p>
                </div>

                <div className="mt-2 rounded-full bg-neutral-100 px-3 py-1 text-xs font-medium text-neutral-600 sm:mt-0 dark:bg-neutral-800 dark:text-neutral-300">
                    {sections.length} {sections.length === 1 ? 'section' : 'sections'}
                </div>
            </div>

            <div className="mt-6">
                {sections.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-neutral-300 bg-neutral-50 px-6 py-10 text-center dark:border-neutral-700 dark:bg-neutral-900/50">
                        <p className="text-sm font-semibold text-neutral-800 dark:text-neutral-200">
                            No sections yet
                        </p>

                        <p className="mx-auto mt-2 max-w-lg text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                            This page is ready for Page Builder sections. Adding, removing and
                            reordering sections is introduced in step 4.9.
                        </p>
                    </div>
                ) : (
                    <div className="space-y-3">
                        {sections.map((section, index) => (
                            <div
                                key={section.id}
                                className="flex items-center gap-4 rounded-xl border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-900/50"
                            >
                                <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-sm font-semibold text-neutral-600 shadow-sm dark:bg-neutral-950 dark:text-neutral-300">
                                    {index + 1}
                                </div>

                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <p className="truncate text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                            {formatSectionType(section.type)}
                                        </p>

                                        {!section.is_enabled && (
                                            <span className="rounded-full bg-neutral-200 px-2 py-0.5 text-xs font-medium text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400">
                                                Disabled
                                            </span>
                                        )}
                                    </div>

                                    <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                        Template: {section.template}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>

            <div className="mt-5 rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm text-neutral-500 dark:border-neutral-800 dark:bg-neutral-900/50 dark:text-neutral-400">
                Section management controls are intentionally disabled in this editor-shell step.
                They will be introduced in 4.9.
            </div>
        </section>
    )
}

interface FieldProps {
    label: string
    hint?: string
    error?: string
    children: ReactNode
}

function Field({ label, hint, error, children }: FieldProps) {
    return (
        <div>
            <label className="mb-1.5 block text-sm font-medium text-neutral-800 dark:text-neutral-200">
                {label}
            </label>

            {children}

            {hint && !error && (
                <p className="mt-1.5 text-xs text-neutral-500 dark:text-neutral-400">{hint}</p>
            )}

            {error && <p className="mt-1.5 text-sm text-red-600">{error}</p>}
        </div>
    )
}

function formatSectionType(type: string): string {
    return type
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ')
}

const sectionClass =
    'rounded-xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-950'

const inputClass =
    'w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100 dark:focus:border-neutral-500 dark:focus:ring-neutral-800'
