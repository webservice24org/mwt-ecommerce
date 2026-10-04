import type { SectionConfig } from '@/types/page-builder'

import PageBuilderImageField from '../../Media/PageBuilderImageField'
import type { SectionEditorProps } from '../types'

const MAX_HEADING_LENGTH = 160
const MAX_BODY_LENGTH = 5000
const MAX_IMAGE_ALT_LENGTH = 255

const ALIGNMENT_OPTIONS = [
    { value: 'left', label: 'Left' },
    { value: 'center', label: 'Center' },
    { value: 'right', label: 'Right' },
] as const

type ContentAlignment = (typeof ALIGNMENT_OPTIONS)[number]['value']

interface ContentConfig {
    heading: string
    body: string
    image: string | null
    image_alt: string | null
    alignment: ContentAlignment
}

export default function ContentEditor({ pageId, section, value, onChange }: SectionEditorProps) {
    const heading = getString(value.heading)
    const body = getString(value.body)
    const image = getNullableString(value.image)
    const imageAlt = getNullableString(value.image_alt)
    const alignment = getAlignment(value.alignment)

    const showImage =
        section.template === 'image_text' ||
        section.template === 'text_image' ||
        section.template === 'centered_content'

    const updateConfig = (overrides: Partial<ContentConfig>) => {
        const nextConfig: SectionConfig = {
            heading: overrides.heading ?? heading,
            body: overrides.body ?? body,
            image: overrides.image !== undefined ? overrides.image : image,
            image_alt: overrides.image_alt !== undefined ? overrides.image_alt : imageAlt,
            alignment: overrides.alignment ?? alignment,
        }

        onChange(nextConfig)
    }

    return (
        <div className="space-y-6">
            <section className="rounded-lg border border-neutral-200 p-4 dark:border-neutral-800">
                <div className="mb-4">
                    <h3 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                        Content
                    </h3>

                    <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                        Add the heading and plain-text content displayed by this section.
                    </p>
                </div>

                <div className="space-y-5">
                    <div>
                        <div className="flex items-center justify-between gap-3">
                            <label
                                htmlFor="content-section-heading"
                                className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                            >
                                Heading
                            </label>

                            <span className="text-xs text-neutral-500 dark:text-neutral-400">
                                {characterCount(heading)}/{MAX_HEADING_LENGTH}
                            </span>
                        </div>

                        <input
                            id="content-section-heading"
                            type="text"
                            value={heading}
                            maxLength={MAX_HEADING_LENGTH}
                            onChange={(event) =>
                                updateConfig({
                                    heading: event.target.value,
                                })
                            }
                            placeholder="Content"
                            className="mt-2 w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800"
                        />

                        <p className="mt-1.5 text-xs text-neutral-500 dark:text-neutral-400">
                            Optional. Leave blank when the section does not need a visible heading.
                        </p>
                    </div>

                    <div>
                        <div className="flex items-center justify-between gap-3">
                            <label
                                htmlFor="content-section-body"
                                className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                            >
                                Body
                            </label>

                            <span className="text-xs text-neutral-500 dark:text-neutral-400">
                                {characterCount(body)}/{MAX_BODY_LENGTH}
                            </span>
                        </div>

                        <textarea
                            id="content-section-body"
                            value={body}
                            maxLength={MAX_BODY_LENGTH}
                            rows={9}
                            onChange={(event) =>
                                updateConfig({
                                    body: event.target.value,
                                })
                            }
                            placeholder="Add your content here..."
                            className="mt-2 w-full resize-y rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm leading-6 text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800"
                        />

                        <p className="mt-1.5 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                            Required. Plain text only. Line breaks are preserved; arbitrary HTML is
                            not part of this Content section contract.
                        </p>
                    </div>
                </div>
            </section>

            {showImage && (
                <section className="rounded-lg border border-neutral-200 p-4 dark:border-neutral-800">
                    <div className="mb-4">
                        <h3 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                            Content Image
                        </h3>

                        <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                            Upload the image displayed by this Content section.
                        </p>
                    </div>

                    <div className="space-y-5">
                        <PageBuilderImageField
                            pageId={pageId}
                            label="Content Image"
                            value={image}
                            helpText={getImageHelpText(section.template)}
                            onChange={(value) =>
                                updateConfig({
                                    image: value,
                                })
                            }
                        />

                        <div>
                            <div className="flex items-center justify-between gap-3">
                                <label
                                    htmlFor="content-section-image-alt"
                                    className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                                >
                                    Image alt text
                                </label>

                                <span className="text-xs text-neutral-500 dark:text-neutral-400">
                                    {characterCount(imageAlt ?? '')}/{MAX_IMAGE_ALT_LENGTH}
                                </span>
                            </div>

                            <input
                                id="content-section-image-alt"
                                type="text"
                                value={imageAlt ?? ''}
                                maxLength={MAX_IMAGE_ALT_LENGTH}
                                onChange={(event) =>
                                    updateConfig({
                                        image_alt: nullableString(event.target.value),
                                    })
                                }
                                placeholder="Describe the image"
                                className="mt-2 w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800"
                            />

                            <p className="mt-1.5 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                                Optional for now. Use meaningful alternative text when the image
                                communicates information.
                            </p>
                        </div>
                    </div>
                </section>
            )}

            <section className="rounded-lg border border-neutral-200 p-4 dark:border-neutral-800">
                <div className="mb-4">
                    <h3 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                        Content Alignment
                    </h3>

                    <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                        Choose how the textual content should be aligned inside this section.
                    </p>
                </div>

                <div
                    role="radiogroup"
                    aria-label="Content section alignment"
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
                                    name="content_section_alignment"
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

function getString(value: SectionConfig[string]): string {
    return typeof value === 'string' ? value : ''
}

function getNullableString(value: SectionConfig[string]): string | null {
    return typeof value === 'string' ? value : null
}

function nullableString(value: string): string | null {
    return value.trim().length === 0 ? null : value
}

function getAlignment(value: SectionConfig[string]): ContentAlignment {
    return ALIGNMENT_OPTIONS.some((option) => option.value === value)
        ? (value as ContentAlignment)
        : 'left'
}

function getImageHelpText(template: string): string {
    switch (template) {
        case 'image_text':
            return 'Upload the image displayed before the text content.'

        case 'text_image':
            return 'Upload the image displayed after the text content.'

        case 'centered_content':
            return 'Upload the optional image displayed with the centered content.'

        default:
            return 'Upload the image displayed by this Content section.'
    }
}

function characterCount(value: string): number {
    return Array.from(value).length
}
