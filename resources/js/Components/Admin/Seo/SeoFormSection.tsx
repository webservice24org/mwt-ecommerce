import type {
    EntitySeoFormData,
    SeoBooleanOverride,
} from '@/types/seo'
import { uploadSeoImage } from '@/utils/upload-seo-image'
import {
    useState,
    type ChangeEvent,
} from 'react'

type SeoErrors = Partial<
    Record<
        | 'canonical_url'
        | 'robots_index'
        | 'robots_follow'
        | 'og_title'
        | 'og_description'
        | 'og_image'
        | 'twitter_title'
        | 'twitter_description'
        | 'twitter_image',
        string
    >
>

type Props = {
    metaTitle: string
    metaDescription: string
    seo: EntitySeoFormData

    errors?: SeoErrors & {
        meta_title?: string
        meta_description?: string
    }

    onMetaTitleChange: (value: string) => void
    onMetaDescriptionChange: (value: string) => void

    onSeoChange: <K extends keyof EntitySeoFormData>(
        key: K,
        value: EntitySeoFormData[K],
    ) => void

    titleMaxLength?: number
    descriptionMaxLength?: number
}

const inputClass =
    'mt-1 block w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm shadow-sm outline-none transition focus:border-neutral-500 focus:ring-1 focus:ring-neutral-500 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100'

const errorClass =
    'mt-1 text-sm text-red-600 dark:text-red-400'

export default function SeoFormSection({
    metaTitle,
    metaDescription,
    seo,
    errors = {},
    onMetaTitleChange,
    onMetaDescriptionChange,
    onSeoChange,
    titleMaxLength = 255,
    descriptionMaxLength = 500,
}: Props) {
    return (
        <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <div>
                <h2 className="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                    Search Engine Optimization
                </h2>

                <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                    Configure search, canonical, robots, Open Graph and social metadata.
                    Leave override fields empty to inherit their normal fallback values.
                </p>
            </div>

            <div className="mt-6 space-y-8">
                <section>
                    <h3 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                        Search result
                    </h3>

                    <div className="mt-4 space-y-5">
                        <div>
                            <div className="flex items-center justify-between gap-4">
                                <label className="text-sm font-medium text-neutral-700 dark:text-neutral-300">
                                    Meta title
                                </label>

                                <span className="text-xs text-neutral-500">
                                    {metaTitle.length}/{titleMaxLength}
                                </span>
                            </div>

                            <input
                                type="text"
                                value={metaTitle}
                                onChange={(event) =>
                                    onMetaTitleChange(event.target.value)
                                }
                                className={inputClass}
                                maxLength={titleMaxLength}
                            />

                            {errors.meta_title && (
                                <p className={errorClass}>
                                    {errors.meta_title}
                                </p>
                            )}
                        </div>

                        <div>
                            <div className="flex items-center justify-between gap-4">
                                <label className="text-sm font-medium text-neutral-700 dark:text-neutral-300">
                                    Meta description
                                </label>

                                <span className="text-xs text-neutral-500">
                                    {metaDescription.length}/{descriptionMaxLength}
                                </span>
                            </div>

                            <textarea
                                value={metaDescription}
                                onChange={(event) =>
                                    onMetaDescriptionChange(event.target.value)
                                }
                                rows={4}
                                className={inputClass}
                                maxLength={descriptionMaxLength}
                            />

                            {errors.meta_description && (
                                <p className={errorClass}>
                                    {errors.meta_description}
                                </p>
                            )}
                        </div>
                    </div>
                </section>

                <section className="border-t border-neutral-200 pt-6 dark:border-neutral-800">
                    <h3 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                        Canonical URL
                    </h3>

                    <p className="mt-1 text-xs text-neutral-500">
                        Leave blank to use the normal URL generated by the application.
                    </p>

                    <input
                        type="url"
                        value={seo.canonical_url}
                        onChange={(event) =>
                            onSeoChange('canonical_url', event.target.value)
                        }
                        placeholder="https://example.com/..."
                        className={inputClass}
                        maxLength={2048}
                    />

                    {errors.canonical_url && (
                        <p className={errorClass}>
                            {errors.canonical_url}
                        </p>
                    )}
                </section>

                <section className="border-t border-neutral-200 pt-6 dark:border-neutral-800">
                    <h3 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                        Search engine directives
                    </h3>

                    <div className="mt-4 grid gap-5 sm:grid-cols-2">
                        <RobotsSelect
                            label="Indexing"
                            value={seo.robots_index}
                            onChange={(value) =>
                                onSeoChange('robots_index', value)
                            }
                        />

                        <RobotsSelect
                            label="Link following"
                            value={seo.robots_follow}
                            onChange={(value) =>
                                onSeoChange('robots_follow', value)
                            }
                            followMode
                        />
                    </div>

                    {errors.robots_index && (
                        <p className={errorClass}>
                            {errors.robots_index}
                        </p>
                    )}

                    {errors.robots_follow && (
                        <p className={errorClass}>
                            {errors.robots_follow}
                        </p>
                    )}
                </section>

                <section className="border-t border-neutral-200 pt-6 dark:border-neutral-800">
                    <h3 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                        Open Graph
                    </h3>

                    <p className="mt-1 text-xs text-neutral-500">
                        Used by Facebook, LinkedIn and other social platforms.
                    </p>

                    <div className="mt-4 space-y-5">
                        <TextField
                            label="Open Graph title"
                            value={seo.og_title}
                            maxLength={255}
                            error={errors.og_title}
                            onChange={(value) =>
                                onSeoChange('og_title', value)
                            }
                        />

                        <TextareaField
                            label="Open Graph description"
                            value={seo.og_description}
                            maxLength={500}
                            error={errors.og_description}
                            onChange={(value) =>
                                onSeoChange('og_description', value)
                            }
                        />

                        <SeoImageField
                            label="Open Graph image"
                            value={seo.og_image}
                            error={errors.og_image}
                            recommendedText="Recommended social size: 1200 × 630 px."
                            onChange={(value) =>
                                onSeoChange('og_image', value)
                            }
                        />
                    </div>
                </section>

                <section className="border-t border-neutral-200 pt-6 dark:border-neutral-800">
                    <h3 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                        Twitter / X
                    </h3>

                    <p className="mt-1 text-xs text-neutral-500">
                        Leave the image empty to allow the SEO resolver to fall back to the Open Graph image.
                    </p>

                    <div className="mt-4 space-y-5">
                        <TextField
                            label="Twitter / X title"
                            value={seo.twitter_title}
                            maxLength={255}
                            error={errors.twitter_title}
                            onChange={(value) =>
                                onSeoChange('twitter_title', value)
                            }
                        />

                        <TextareaField
                            label="Twitter / X description"
                            value={seo.twitter_description}
                            maxLength={500}
                            error={errors.twitter_description}
                            onChange={(value) =>
                                onSeoChange('twitter_description', value)
                            }
                        />

                        <SeoImageField
                            label="Twitter / X image"
                            value={seo.twitter_image}
                            error={errors.twitter_image}
                            recommendedText="Optional. If empty, Open Graph can be used as the later resolver fallback."
                            onChange={(value) =>
                                onSeoChange('twitter_image', value)
                            }
                        />
                    </div>
                </section>
            </div>
        </section>
    )
}

function SeoImageField({
    label,
    value,
    onChange,
    error,
    recommendedText,
}: {
    label: string
    value: string
    onChange: (value: string) => void
    error?: string
    recommendedText: string
}) {
    const [uploading, setUploading] = useState(false)
    const [uploadError, setUploadError] = useState<string | null>(null)

    const handleUpload = async (
        event: ChangeEvent<HTMLInputElement>,
    ) => {
        const file = event.target.files?.[0] ?? null

        if (file === null) {
            return
        }

        setUploadError(null)
        setUploading(true)

        try {
            const response = await uploadSeoImage(file)

            onChange(response.image.url)
        } catch (exception) {
            setUploadError(
                exception instanceof Error
                    ? exception.message
                    : 'The SEO image could not be uploaded.',
            )
        } finally {
            setUploading(false)
            event.target.value = ''
        }
    }

    return (
        <div>
            <label className="text-sm font-medium text-neutral-700 dark:text-neutral-300">
                {label}
            </label>

            <p className="mt-1 text-xs text-neutral-500">
                {recommendedText}
            </p>

            {value !== '' && (
                <div className="mt-3 overflow-hidden rounded-lg border border-neutral-200 bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-900">
                    <img
                        src={value}
                        alt={`${label} preview`}
                        className="aspect-[1200/630] w-full object-cover"
                        loading="lazy"
                    />

                    <div className="flex flex-wrap items-center justify-between gap-3 border-t border-neutral-200 px-3 py-2 dark:border-neutral-800">
                        <p className="min-w-0 flex-1 truncate text-xs text-neutral-500">
                            {value}
                        </p>

                        <button
                            type="button"
                            onClick={() => onChange('')}
                            className="shrink-0 text-xs font-medium text-red-600 hover:text-red-700 dark:text-red-400"
                        >
                            Remove
                        </button>
                    </div>
                </div>
            )}

            <div className="mt-3 flex flex-wrap items-center gap-3">
                <input
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    disabled={uploading}
                    onChange={handleUpload}
                    className="block min-w-0 flex-1 rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-700 file:mr-4 file:rounded-md file:border-0 file:bg-neutral-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-neutral-700 hover:file:bg-neutral-200 disabled:cursor-not-allowed disabled:opacity-60 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-200"
                />

                {uploading && (
                    <span
                        role="status"
                        className="text-xs font-medium text-neutral-500"
                    >
                        Uploading…
                    </span>
                )}
            </div>

            <p className="mt-2 text-xs text-neutral-500">
                JPG, PNG or WebP. Maximum 5 MB.
            </p>

            {uploadError && (
                <p className={errorClass}>
                    {uploadError}
                </p>
            )}

            {error && (
                <p className={errorClass}>
                    {error}
                </p>
            )}
        </div>
    )
}

function RobotsSelect({
    label,
    value,
    onChange,
    followMode = false,
}: {
    label: string
    value: SeoBooleanOverride
    onChange: (value: SeoBooleanOverride) => void
    followMode?: boolean
}) {
    return (
        <div>
            <label className="text-sm font-medium text-neutral-700 dark:text-neutral-300">
                {label}
            </label>

            <select
                value={
                    value === null
                        ? 'inherit'
                        : value
                          ? 'true'
                          : 'false'
                }
                onChange={(event) => {
                    const value = event.target.value

                    onChange(
                        value === 'inherit'
                            ? null
                            : value === 'true',
                    )
                }}
                className={inputClass}
            >
                <option value="inherit">
                    Inherit default
                </option>

                <option value="true">
                    {followMode ? 'Follow' : 'Index'}
                </option>

                <option value="false">
                    {followMode ? 'No follow' : 'No index'}
                </option>
            </select>
        </div>
    )
}

function TextField({
    label,
    value,
    onChange,
    maxLength,
    error,
    placeholder,
}: {
    label: string
    value: string
    onChange: (value: string) => void
    maxLength: number
    error?: string
    placeholder?: string
}) {
    return (
        <div>
            <div className="flex items-center justify-between gap-4">
                <label className="text-sm font-medium text-neutral-700 dark:text-neutral-300">
                    {label}
                </label>

                {maxLength <= 500 && (
                    <span className="text-xs text-neutral-500">
                        {value.length}/{maxLength}
                    </span>
                )}
            </div>

            <input
                type="text"
                value={value}
                onChange={(event) => onChange(event.target.value)}
                placeholder={placeholder}
                maxLength={maxLength}
                className={inputClass}
            />

            {error && (
                <p className={errorClass}>
                    {error}
                </p>
            )}
        </div>
    )
}

function TextareaField({
    label,
    value,
    onChange,
    maxLength,
    error,
}: {
    label: string
    value: string
    onChange: (value: string) => void
    maxLength: number
    error?: string
}) {
    return (
        <div>
            <div className="flex items-center justify-between gap-4">
                <label className="text-sm font-medium text-neutral-700 dark:text-neutral-300">
                    {label}
                </label>

                <span className="text-xs text-neutral-500">
                    {value.length}/{maxLength}
                </span>
            </div>

            <textarea
                value={value}
                onChange={(event) => onChange(event.target.value)}
                rows={4}
                maxLength={maxLength}
                className={inputClass}
            />

            {error && (
                <p className={errorClass}>
                    {error}
                </p>
            )}
        </div>
    )
}
