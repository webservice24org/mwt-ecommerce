import type { InertiaFormProps } from '@inertiajs/react'
import type { FormEvent } from 'react'

export type AttributeFormData = {
    name: string
    slug: string
    position: number
    is_active: boolean
}

type Props = {
    form: InertiaFormProps<AttributeFormData>
    submitLabel: string
    onSubmit: (event: FormEvent<HTMLFormElement>) => void
}

export default function AttributeForm({ form, submitLabel, onSubmit }: Props) {
    return (
        <form onSubmit={onSubmit} className="space-y-6">
            <section className="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                <h2 className="text-base font-semibold text-neutral-900">Attribute information</h2>

                <div className="mt-6 space-y-5">
                    <div>
                        <label htmlFor="name" className="text-sm font-medium text-neutral-900">
                            Name
                        </label>

                        <input
                            id="name"
                            value={form.data.name}
                            maxLength={120}
                            required
                            onChange={(event) => form.setData('name', event.target.value)}
                            placeholder="Color"
                            className="mt-1 block w-full rounded-lg border border-neutral-300 px-3 py-2.5 text-sm"
                        />

                        {form.errors.name && (
                            <p className="mt-1 text-sm text-red-600">{form.errors.name}</p>
                        )}
                    </div>

                    <div>
                        <label htmlFor="slug" className="text-sm font-medium text-neutral-900">
                            Slug
                        </label>

                        <input
                            id="slug"
                            value={form.data.slug}
                            maxLength={150}
                            onChange={(event) => form.setData('slug', event.target.value)}
                            placeholder="Leave blank to generate automatically"
                            className="mt-1 block w-full rounded-lg border border-neutral-300 px-3 py-2.5 text-sm"
                        />

                        {form.errors.slug && (
                            <p className="mt-1 text-sm text-red-600">{form.errors.slug}</p>
                        )}
                    </div>

                    <div>
                        <label htmlFor="position" className="text-sm font-medium text-neutral-900">
                            Position
                        </label>

                        <input
                            id="position"
                            type="number"
                            min={0}
                            value={form.data.position}
                            onChange={(event) =>
                                form.setData('position', Number(event.target.value))
                            }
                            className="mt-1 block w-full rounded-lg border border-neutral-300 px-3 py-2.5 text-sm"
                        />

                        {form.errors.position && (
                            <p className="mt-1 text-sm text-red-600">{form.errors.position}</p>
                        )}
                    </div>

                    <label className="flex items-center gap-3">
                        <input
                            type="checkbox"
                            checked={form.data.is_active}
                            onChange={(event) => form.setData('is_active', event.target.checked)}
                            className="h-4 w-4 rounded border-neutral-300"
                        />

                        <span className="text-sm font-medium text-neutral-900">Active</span>
                    </label>
                </div>
            </section>

            <button
                type="submit"
                disabled={form.processing}
                className="rounded-lg bg-neutral-900 px-5 py-2.5 text-sm font-medium text-white disabled:opacity-50"
            >
                {form.processing ? 'Saving...' : submitLabel}
            </button>
        </form>
    )
}
