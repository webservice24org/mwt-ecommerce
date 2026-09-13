import SeoFields from '@/Components/Admin/SeoFields'
import type { InertiaFormProps } from '@inertiajs/react'
import { useEffect, useState } from 'react'

export type BrandFormData = {
    name: string
    slug: string
    description: string
    logo: File | null
    remove_logo: boolean
    position: number
    is_active: boolean
    meta_title: string
    meta_description: string
}

type Props = {
    form: InertiaFormProps<BrandFormData>
    submitLabel: string
    onSubmit: () => void
    currentLogoUrl?: string | null
}

export default function BrandForm({ form, submitLabel, onSubmit, currentLogoUrl = null }: Props) {
    const inputClass =
        'mt-1 block w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-neutral-500 focus:outline-none focus:ring-1 focus:ring-neutral-500'

    const errorClass = 'mt-1 text-sm text-red-600'

    const [previewUrl, setPreviewUrl] = useState<string | null>(null)

    useEffect(() => {
        return () => {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl)
            }
        }
    }, [previewUrl])

    const clearPreview = (): void => {
        setPreviewUrl((current) => {
            if (current) {
                URL.revokeObjectURL(current)
            }

            return null
        })
    }

    const handleLogoChange = (event: React.ChangeEvent<HTMLInputElement>): void => {
        const file = event.target.files?.[0] ?? null

        form.setData('logo', file)

        if (!file) {
            clearPreview()

            return
        }

        setPreviewUrl((current) => {
            if (current) {
                URL.revokeObjectURL(current)
            }

            return URL.createObjectURL(file)
        })

        form.setData('remove_logo', false)
    }

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault()
                onSubmit()
            }}
            className="space-y-6"
        >
            <div className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
                        <h2 className="font-semibold text-neutral-900">Brand Information</h2>

                        <div className="mt-5 space-y-5">
                            <div>
                                <label htmlFor="name" className="text-sm font-medium">
                                    Name
                                </label>

                                <input
                                    id="name"
                                    value={form.data.name}
                                    onChange={(event) => form.setData('name', event.target.value)}
                                    className={inputClass}
                                />

                                {form.errors.name && (
                                    <p className={errorClass}>{form.errors.name}</p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="slug" className="text-sm font-medium">
                                    Slug
                                </label>

                                <input
                                    id="slug"
                                    value={form.data.slug}
                                    onChange={(event) => form.setData('slug', event.target.value)}
                                    placeholder="Leave blank to generate automatically"
                                    className={inputClass}
                                />

                                {form.errors.slug && (
                                    <p className={errorClass}>{form.errors.slug}</p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="description" className="text-sm font-medium">
                                    Description
                                </label>

                                <textarea
                                    id="description"
                                    rows={6}
                                    value={form.data.description}
                                    onChange={(event) =>
                                        form.setData('description', event.target.value)
                                    }
                                    className={inputClass}
                                />

                                {form.errors.description && (
                                    <p className={errorClass}>{form.errors.description}</p>
                                )}
                            </div>

                            <div>
                                <label className="text-sm font-medium">Brand Logo</label>

                                <p className="mt-1 text-xs text-neutral-500">
                                    JPG, JPEG, PNG or WebP. Maximum 5 MB.
                                </p>

                                {currentLogoUrl && !form.data.remove_logo && !form.data.logo && (
                                    <div className="mt-4">
                                        <div className="flex h-48 items-center justify-center overflow-hidden rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                                            <img
                                                src={currentLogoUrl}
                                                alt="Current brand logo"
                                                className="max-h-full max-w-full object-contain"
                                            />
                                        </div>

                                        <label className="mt-3 flex cursor-pointer items-center gap-2 text-sm text-red-600">
                                            <input
                                                type="checkbox"
                                                checked={form.data.remove_logo}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'remove_logo',
                                                        event.target.checked,
                                                    )
                                                }
                                                className="h-4 w-4 rounded"
                                            />
                                            Remove current logo
                                        </label>
                                    </div>
                                )}

                                {currentLogoUrl && form.data.remove_logo && !form.data.logo && (
                                    <div className="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                        The current logo will be removed when you save.
                                    </div>
                                )}

                                {previewUrl && (
                                    <div className="mt-4">
                                        <div className="flex h-48 items-center justify-center rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                                            <img
                                                src={previewUrl}
                                                alt="New brand logo"
                                                className="max-h-full max-w-full object-contain"
                                            />
                                        </div>

                                        {form.data.logo && (
                                            <div className="mt-2 flex justify-between gap-3">
                                                <span className="truncate text-xs text-neutral-500">
                                                    {form.data.logo.name}
                                                </span>

                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        form.setData('logo', null)

                                                        clearPreview()
                                                    }}
                                                    className="text-xs font-medium text-red-600"
                                                >
                                                    Remove selection
                                                </button>
                                            </div>
                                        )}
                                    </div>
                                )}

                                <input
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    onChange={handleLogoChange}
                                    className="mt-4 block w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm"
                                />

                                {form.errors.logo && (
                                    <p className={errorClass}>{form.errors.logo}</p>
                                )}
                            </div>
                        </div>
                    </section>

                    <SeoFields
                        metaTitle={form.data.meta_title}
                        metaDescription={form.data.meta_description}
                        titleError={form.errors.meta_title}
                        descriptionError={form.errors.meta_description}
                        onTitleChange={(value) => form.setData('meta_title', value)}
                        onDescriptionChange={(value) => form.setData('meta_description', value)}
                    />
                </div>

                <div className="space-y-6">
                    <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
                        <h2 className="font-semibold">Publishing</h2>

                        <div className="mt-5 space-y-5">
                            <div>
                                <label htmlFor="position" className="text-sm font-medium">
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
                                    className={inputClass}
                                />

                                {form.errors.position && (
                                    <p className={errorClass}>{form.errors.position}</p>
                                )}
                            </div>

                            <label className="flex cursor-pointer items-start gap-3 rounded-lg border border-neutral-200 p-3">
                                <input
                                    type="checkbox"
                                    checked={form.data.is_active}
                                    onChange={(event) =>
                                        form.setData('is_active', event.target.checked)
                                    }
                                    className="mt-0.5 h-4 w-4 rounded"
                                />

                                <span>
                                    <span className="block text-sm font-medium">Active</span>

                                    <span className="mt-0.5 block text-xs text-neutral-500">
                                        Allow this brand on the storefront.
                                    </span>
                                </span>
                            </label>
                        </div>
                    </section>

                    <button
                        type="submit"
                        disabled={form.processing}
                        className="w-full rounded-lg bg-neutral-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-neutral-800 disabled:opacity-50"
                    >
                        {form.processing ? 'Saving...' : submitLabel}
                    </button>
                </div>
            </div>
        </form>
    )
}
