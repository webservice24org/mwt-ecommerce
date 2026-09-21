import type { PageFormOptions, PageFormValues } from '@/types/page-builder'

interface Props {
    data: PageFormValues
    options: PageFormOptions
    errors: Partial<Record<keyof PageFormValues, string>>
    processing: boolean
    submitLabel: string
    onChange: <K extends keyof PageFormValues>(key: K, value: PageFormValues[K]) => void
    onSubmit: (event: React.FormEvent<HTMLFormElement>) => void
}

export default function PageForm({
    data,
    options,
    errors,
    processing,
    submitLabel,
    onChange,
    onSubmit,
}: Props) {
    return (
        <form onSubmit={onSubmit} className="space-y-6">
            <div className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <section className="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
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

                    <section className="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
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
                    <section className="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
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

interface FieldProps {
    label: string
    hint?: string
    error?: string
    children: React.ReactNode
}

function Field({ label, hint, error, children }: FieldProps) {
    return (
        <div>
            <label className="mb-1.5 block text-sm font-medium text-neutral-800 dark:text-neutral-200">
                {label}
            </label>

            {children}

            {hint && !error && <p className="mt-1.5 text-xs text-neutral-500">{hint}</p>}

            {error && <p className="mt-1.5 text-sm text-red-600">{error}</p>}
        </div>
    )
}

const inputClass =
    'w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100 dark:focus:border-neutral-500 dark:focus:ring-neutral-800'
