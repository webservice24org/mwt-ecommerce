import type { SectionConfig } from '@/types/page-builder'

import PageBuilderImageField from '../../Media/PageBuilderImageField'
import type { SectionEditorProps } from '../types'

const MAX_HEADING_LENGTH = 160
const MAX_DESCRIPTION_LENGTH = 500
const MAX_CTA_LABEL_LENGTH = 80
const MAX_CTA_URL_LENGTH = 2048

const ALIGNMENT_OPTIONS = [
    { value: 'left', label: 'Left' },
    { value: 'center', label: 'Center' },
    { value: 'right', label: 'Right' },
] as const

type BannerAlignment = (typeof ALIGNMENT_OPTIONS)[number]['value']

export default function PromotionalBannerEditor({
    pageId,
    section,
    value,
    onChange,
}: SectionEditorProps) {
    const heading = getString(value.heading)
    const description = getString(value.description)
    const image = getNullableString(value.image)
    const ctaLabel = getNullableString(value.cta_label)
    const ctaUrl = getNullableString(value.cta_url)
    const alignment = getAlignment(value.alignment)

    const showImage = section.template === 'image_banner' || section.template === 'split_banner'

    const updateConfig = (overrides: Partial<PromotionalBannerConfig>) => {
        const nextConfig: SectionConfig = {
            heading: overrides.heading ?? heading,
            description: overrides.description ?? description,
            image: overrides.image !== undefined ? overrides.image : image,
            cta_label: overrides.cta_label !== undefined ? overrides.cta_label : ctaLabel,
            cta_url: overrides.cta_url !== undefined ? overrides.cta_url : ctaUrl,
            alignment: overrides.alignment ?? alignment,
        }

        onChange(nextConfig)
    }

    return (
        <div className="space-y-6">
            <section className="rounded-lg border border-neutral-200 p-4 dark:border-neutral-800">
                <div className="mb-4">
                    <h3 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                        Banner Content
                    </h3>
                    <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                        Configure the promotional message displayed in this banner.
                    </p>
                </div>

                <div className="space-y-5">
                    <div>
                        <div className="flex items-center justify-between gap-3">
                            <label
                                htmlFor="promotional-banner-heading"
                                className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                            >
                                Heading
                            </label>
                            <span className="text-xs text-neutral-500 dark:text-neutral-400">
                                {characterCount(heading)}/{MAX_HEADING_LENGTH}
                            </span>
                        </div>

                        <input
                            id="promotional-banner-heading"
                            type="text"
                            value={heading}
                            maxLength={MAX_HEADING_LENGTH}
                            onChange={(event) =>
                                updateConfig({
                                    heading: event.target.value,
                                })
                            }
                            placeholder="Special Offer"
                            className="mt-2 w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800"
                        />

                        <p className="mt-1.5 text-xs text-neutral-500 dark:text-neutral-400">
                            Required. Enter the main promotional headline.
                        </p>
                    </div>

                    <div>
                        <div className="flex items-center justify-between gap-3">
                            <label
                                htmlFor="promotional-banner-description"
                                className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                            >
                                Description
                            </label>
                            <span className="text-xs text-neutral-500 dark:text-neutral-400">
                                {characterCount(description)}/{MAX_DESCRIPTION_LENGTH}
                            </span>
                        </div>

                        <textarea
                            id="promotional-banner-description"
                            value={description}
                            maxLength={MAX_DESCRIPTION_LENGTH}
                            rows={4}
                            onChange={(event) =>
                                updateConfig({
                                    description: event.target.value,
                                })
                            }
                            placeholder="Add supporting promotional copy..."
                            className="mt-2 w-full resize-y rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800"
                        />
                    </div>
                </div>
            </section>

            {showImage && (
                <section className="rounded-lg border border-neutral-200 p-4 dark:border-neutral-800">
                    <div className="mb-4">
                        <h3 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                            Banner Image
                        </h3>

                        <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                            Upload the promotional image displayed by this banner.
                        </p>
                    </div>

                    <PageBuilderImageField
                        pageId={pageId}
                        label="Banner Image"
                        value={image}
                        helpText={
                            section.template === 'split_banner'
                                ? 'Upload the image displayed beside the promotional content.'
                                : 'Upload the background image used by this promotional banner.'
                        }
                        onChange={(value) =>
                            updateConfig({
                                image: value,
                            })
                        }
                    />
                </section>
            )}

            <section className="rounded-lg border border-neutral-200 p-4 dark:border-neutral-800">
                <div className="mb-4">
                    <h3 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                        Call to Action
                    </h3>
                    <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                        CTA label and URL are optional, but they must be provided together.
                    </p>
                </div>

                <div className="grid gap-5 sm:grid-cols-2">
                    <div>
                        <div className="flex items-center justify-between gap-3">
                            <label
                                htmlFor="promotional-banner-cta-label"
                                className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                            >
                                CTA label
                            </label>
                            <span className="text-xs text-neutral-500 dark:text-neutral-400">
                                {characterCount(ctaLabel ?? '')}/{MAX_CTA_LABEL_LENGTH}
                            </span>
                        </div>

                        <input
                            id="promotional-banner-cta-label"
                            type="text"
                            value={ctaLabel ?? ''}
                            maxLength={MAX_CTA_LABEL_LENGTH}
                            onChange={(event) =>
                                updateConfig({
                                    cta_label: nullableString(event.target.value),
                                })
                            }
                            placeholder="Shop Now"
                            className="mt-2 w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800"
                        />
                    </div>

                    <div>
                        <div className="flex items-center justify-between gap-3">
                            <label
                                htmlFor="promotional-banner-cta-url"
                                className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                            >
                                CTA URL
                            </label>
                            <span className="text-xs text-neutral-500 dark:text-neutral-400">
                                {characterCount(ctaUrl ?? '')}/{MAX_CTA_URL_LENGTH}
                            </span>
                        </div>

                        <input
                            id="promotional-banner-cta-url"
                            type="text"
                            value={ctaUrl ?? ''}
                            maxLength={MAX_CTA_URL_LENGTH}
                            onChange={(event) =>
                                updateConfig({
                                    cta_url: nullableString(event.target.value),
                                })
                            }
                            placeholder="/shop"
                            className="mt-2 w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800"
                        />
                    </div>
                </div>
            </section>

            <section className="rounded-lg border border-neutral-200 p-4 dark:border-neutral-800">
                <div className="mb-4">
                    <h3 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                        Content Alignment
                    </h3>
                    <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                        Choose how the banner content should be aligned.
                    </p>
                </div>

                <div
                    role="radiogroup"
                    aria-label="Promotional banner content alignment"
                    className="grid gap-3 sm:grid-cols-3"
                >
                    {ALIGNMENT_OPTIONS.map((option) => {
                        const selected = alignment === option.value

                        return (
                            <label
                                key={option.value}
                                className={[
                                    'cursor-pointer rounded-lg border px-4 py-3 text-center text-sm font-medium transition',
                                    selected
                                        ? 'border-neutral-900 bg-neutral-50 ring-1 ring-neutral-900 dark:border-neutral-100 dark:bg-neutral-900 dark:ring-neutral-100'
                                        : 'border-neutral-200 bg-white text-neutral-600 hover:border-neutral-400 dark:border-neutral-800 dark:bg-neutral-950 dark:text-neutral-300 dark:hover:border-neutral-600',
                                ].join(' ')}
                            >
                                <input
                                    type="radio"
                                    name="promotional_banner_alignment"
                                    value={option.value}
                                    checked={selected}
                                    onChange={() =>
                                        updateConfig({
                                            alignment: option.value,
                                        })
                                    }
                                    className="sr-only"
                                />
                                {option.label}
                            </label>
                        )
                    })}
                </div>
            </section>
        </div>
    )
}

interface PromotionalBannerConfig {
    heading: string
    description: string
    image: string | null
    cta_label: string | null
    cta_url: string | null
    alignment: BannerAlignment
}

function getString(value: SectionConfig[string]): string {
    return typeof value === 'string' ? value : ''
}

function getNullableString(value: SectionConfig[string]): string | null {
    return typeof value === 'string' ? value : null
}

function nullableString(value: string): string | null {
    return value.trim().length === 0 ? null : value
}

function getAlignment(value: SectionConfig[string]): BannerAlignment {
    return ALIGNMENT_OPTIONS.some((option) => option.value === value)
        ? (value as BannerAlignment)
        : 'center'
}

function characterCount(value: string): number {
    return Array.from(value).length
}
