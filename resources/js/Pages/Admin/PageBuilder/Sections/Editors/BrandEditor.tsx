import type { SectionConfig } from '@/types/page-builder'

import BrandSourceField from './BrandSourceField'
import type { BrandSourceConfig } from './brand-editor-types'

import type { SectionEditorProps } from '../types'

const MAX_EYEBROW_LENGTH = 120
const MAX_HEADING_LENGTH = 180
const MAX_DESCRIPTION_LENGTH = 1000
const MAX_LABEL_LENGTH = 100
const MAX_URL_LENGTH = 2048

type BrandAlignment = 'left' | 'center'

type BrandTextTheme = 'light' | 'dark'

interface BrandConfig {
    eyebrow: string | null
    heading: string
    description: string

    source: BrandSourceConfig

    limit: number
    columns: number
    alignment: BrandAlignment

    background_color: string
    text_theme: BrandTextTheme

    show_name: boolean
    show_description: boolean
    show_product_count: boolean

    view_all_label: string | null
    view_all_url: string | null

    primary_button_label: string | null
    primary_button_url: string | null

    secondary_button_label: string | null
    secondary_button_url: string | null

    marquee_duration: number
    pause_on_hover: boolean
}

export default function BrandEditor({ pageId, section, value, onChange }: SectionEditorProps) {
    const eyebrow = getNullableString(value.eyebrow)

    const heading = getString(value.heading)

    const description = getString(value.description)

    const source = getBrandSource(value.source)

    const limit = getInteger(value.limit, 12)

    const columns = getInteger(value.columns, 4)

    const alignment = getAlignment(value.alignment)

    const backgroundColor = getBackgroundColor(value.background_color)

    const textTheme = getTextTheme(value.text_theme)

    const showName = getBoolean(value.show_name, false)

    const showDescription = getBoolean(value.show_description, false)

    const showProductCount = getBoolean(value.show_product_count, false)

    const viewAllLabel = getNullableString(value.view_all_label)

    const viewAllUrl = getNullableString(value.view_all_url)

    const primaryButtonLabel = getNullableString(value.primary_button_label)

    const primaryButtonUrl = getNullableString(value.primary_button_url)

    const secondaryButtonLabel = getNullableString(value.secondary_button_label)

    const secondaryButtonUrl = getNullableString(value.secondary_button_url)

    const marqueeDuration = getInteger(value.marquee_duration, 25)

    const pauseOnHover = getBoolean(value.pause_on_hover, true)

    const isLogoStrip = section.template === 'logo_strip'

    const isBrandCards = section.template === 'brand_cards'

    const isLogoMarquee = section.template === 'logo_marquee'

    const isSpotlight = section.template === 'spotlight_banner'

    const showColumns = !isLogoMarquee

    const updateConfig = (overrides: Partial<BrandConfig>) => {
        const nextConfig: SectionConfig = {
            eyebrow: overrides.eyebrow !== undefined ? overrides.eyebrow : eyebrow,

            heading: overrides.heading ?? heading,

            description: overrides.description ?? description,

            source: overrides.source ?? source,

            limit: overrides.limit ?? limit,

            columns: overrides.columns ?? columns,

            alignment: overrides.alignment ?? alignment,

            background_color: overrides.background_color ?? backgroundColor,

            text_theme: overrides.text_theme ?? textTheme,

            show_name: overrides.show_name ?? showName,

            show_description: overrides.show_description ?? showDescription,

            show_product_count: overrides.show_product_count ?? showProductCount,

            view_all_label:
                overrides.view_all_label !== undefined ? overrides.view_all_label : viewAllLabel,

            view_all_url:
                overrides.view_all_url !== undefined ? overrides.view_all_url : viewAllUrl,

            primary_button_label:
                overrides.primary_button_label !== undefined
                    ? overrides.primary_button_label
                    : primaryButtonLabel,

            primary_button_url:
                overrides.primary_button_url !== undefined
                    ? overrides.primary_button_url
                    : primaryButtonUrl,

            secondary_button_label:
                overrides.secondary_button_label !== undefined
                    ? overrides.secondary_button_label
                    : secondaryButtonLabel,

            secondary_button_url:
                overrides.secondary_button_url !== undefined
                    ? overrides.secondary_button_url
                    : secondaryButtonUrl,

            marquee_duration: overrides.marquee_duration ?? marqueeDuration,

            pause_on_hover: overrides.pause_on_hover ?? pauseOnHover,
        }

        onChange(nextConfig)
    }

    return (
        <div className="space-y-6">
            <section className="rounded-xl border border-neutral-200 p-4 dark:border-neutral-800 sm:p-5">
                <SectionHeading
                    title="Brand section content"
                    description="Configure the heading and introduction displayed above this Brand section."
                />

                <div className="mt-5 space-y-5">
                    <TextField
                        id="brand-section-eyebrow"
                        label="Eyebrow"
                        value={eyebrow ?? ''}
                        maxLength={MAX_EYEBROW_LENGTH}
                        placeholder="Official Partners"
                        optional
                        onChange={(nextValue) =>
                            updateConfig({
                                eyebrow: nullableEditableString(nextValue),
                            })
                        }
                    />

                    <TextField
                        id="brand-section-heading"
                        label="Heading"
                        value={heading}
                        maxLength={MAX_HEADING_LENGTH}
                        placeholder="Shop by Brand"
                        onChange={(nextValue) =>
                            updateConfig({
                                heading: nextValue,
                            })
                        }
                    />

                    <TextAreaField
                        id="brand-section-description"
                        label="Description"
                        value={description}
                        maxLength={MAX_DESCRIPTION_LENGTH}
                        placeholder="Discover products from trusted brands."
                        optional
                        rows={4}
                        onChange={(nextValue) =>
                            updateConfig({
                                description: nextValue,
                            })
                        }
                    />
                </div>
            </section>

            <BrandSourceField
                pageId={pageId}
                source={source}
                onChange={(nextSource) =>
                    updateConfig({
                        source: nextSource,
                    })
                }
            />

            <section className="rounded-xl border border-neutral-200 p-4 dark:border-neutral-800 sm:p-5">
                <SectionHeading
                    title="Layout"
                    description="Control the amount of brands and how the section is arranged."
                />

                <div className={['mt-5 grid gap-5', showColumns ? 'sm:grid-cols-2' : ''].join(' ')}>
                    <NumberField
                        id="brand-section-limit"
                        label="Brand limit"
                        value={limit}
                        min={1}
                        max={24}
                        helpText="Maximum number of brands displayed in this section."
                        onChange={(nextValue) =>
                            updateConfig({
                                limit: nextValue,
                            })
                        }
                    />

                    {showColumns && (
                        <SelectField
                            id="brand-section-columns"
                            label="Columns"
                            value={String(columns)}
                            options={[
                                {
                                    value: '2',

                                    label: '2 columns',
                                },

                                {
                                    value: '3',

                                    label: '3 columns',
                                },

                                {
                                    value: '4',

                                    label: '4 columns',
                                },

                                {
                                    value: '5',

                                    label: '5 columns',
                                },

                                {
                                    value: '6',

                                    label: '6 columns',
                                },
                            ]}
                            onChange={(nextValue) =>
                                updateConfig({
                                    columns: Number(nextValue),
                                })
                            }
                        />
                    )}

                    <SelectField
                        id="brand-section-alignment"
                        label="Content alignment"
                        value={alignment}
                        options={[
                            {
                                value: 'left',

                                label: 'Left',
                            },

                            {
                                value: 'center',

                                label: 'Center',
                            },
                        ]}
                        onChange={(nextValue) =>
                            updateConfig({
                                alignment: nextValue as BrandAlignment,
                            })
                        }
                    />

                    <SelectField
                        id="brand-section-text-theme"
                        label="Text theme"
                        value={textTheme}
                        options={[
                            {
                                value: 'dark',

                                label: 'Dark text',
                            },

                            {
                                value: 'light',

                                label: 'Light text',
                            },
                        ]}
                        onChange={(nextValue) =>
                            updateConfig({
                                text_theme: nextValue as BrandTextTheme,
                            })
                        }
                    />
                </div>

                <div className="mt-5">
                    <ColorField
                        id="brand-section-background-color"
                        label="Background color"
                        value={backgroundColor}
                        onChange={(nextValue) =>
                            updateConfig({
                                background_color: nextValue,
                            })
                        }
                    />
                </div>
            </section>

            {(isLogoStrip || isBrandCards || isLogoMarquee || isSpotlight) && (
                <section className="rounded-xl border border-neutral-200 p-4 dark:border-neutral-800 sm:p-5">
                    <SectionHeading
                        title="Brand display"
                        description="Choose which catalog information accompanies the brand logo."
                    />

                    <div className="mt-5 grid gap-3 sm:grid-cols-2">
                        <ToggleField
                            id="brand-section-show-name"
                            label="Show brand name"
                            description="Display the brand name when appropriate for the selected design."
                            checked={showName}
                            onChange={(checked) =>
                                updateConfig({
                                    show_name: checked,
                                })
                            }
                        />

                        <ToggleField
                            id="brand-section-show-description"
                            label="Show description"
                            description="Display the catalog brand description."
                            checked={showDescription}
                            onChange={(checked) =>
                                updateConfig({
                                    show_description: checked,
                                })
                            }
                        />

                        {isBrandCards && (
                            <ToggleField
                                id="brand-section-show-product-count"
                                label="Show product count"
                                description="Show the number of available products associated with each brand."
                                checked={showProductCount}
                                onChange={(checked) =>
                                    updateConfig({
                                        show_product_count: checked,
                                    })
                                }
                            />
                        )}
                    </div>
                </section>
            )}

            {isBrandCards && (
                <section className="rounded-xl border border-neutral-200 p-4 dark:border-neutral-800 sm:p-5">
                    <SectionHeading
                        title="Brand Cards actions"
                        description="Optionally add the “View All Brands” action shown in the reference Brand Cards design."
                    />

                    <div className="mt-5 grid gap-5 sm:grid-cols-2">
                        <TextField
                            id="brand-section-view-all-label"
                            label="View all label"
                            value={viewAllLabel ?? ''}
                            maxLength={MAX_LABEL_LENGTH}
                            placeholder="View All Brands"
                            optional
                            onChange={(nextValue) =>
                                updateConfig({
                                    view_all_label: nullableEditableString(nextValue),
                                })
                            }
                        />

                        <TextField
                            id="brand-section-view-all-url"
                            label="View all URL"
                            value={viewAllUrl ?? ''}
                            maxLength={MAX_URL_LENGTH}
                            placeholder="/products"
                            optional
                            onChange={(nextValue) =>
                                updateConfig({
                                    view_all_url: nullableEditableString(nextValue),
                                })
                            }
                        />
                    </div>

                    <p className="mt-3 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                        Add both the label and URL, or leave both empty.
                    </p>
                </section>
            )}

            {isLogoMarquee && (
                <section className="rounded-xl border border-neutral-200 p-4 dark:border-neutral-800 sm:p-5">
                    <SectionHeading
                        title="Infinite carousel"
                        description="Control the continuous motion used by the attached Logo Carousel design."
                    />

                    <div className="mt-5 space-y-5">
                        <NumberField
                            id="brand-section-marquee-duration"
                            label="Loop duration"
                            value={marqueeDuration}
                            min={10}
                            max={60}
                            suffix="seconds"
                            helpText="Lower values move faster. Supported range: 10–60 seconds."
                            onChange={(nextValue) =>
                                updateConfig({
                                    marquee_duration: nextValue,
                                })
                            }
                        />

                        <ToggleField
                            id="brand-section-pause-on-hover"
                            label="Pause on hover"
                            description="Pause the moving logo track while a pointer is over the carousel."
                            checked={pauseOnHover}
                            onChange={(checked) =>
                                updateConfig({
                                    pause_on_hover: checked,
                                })
                            }
                        />
                    </div>
                </section>
            )}

            {isSpotlight && (
                <section className="rounded-xl border border-neutral-200 p-4 dark:border-neutral-800 sm:p-5">
                    <SectionHeading
                        title="Spotlight actions"
                        description="Configure the two optional action buttons displayed in the Brand Spotlight Banner."
                    />

                    <div className="mt-5 space-y-6">
                        <LinkPairFields
                            prefix="brand-section-primary"
                            title="Primary button"
                            label={primaryButtonLabel}
                            url={primaryButtonUrl}
                            labelPlaceholder="Browse Brand Directory"
                            urlPlaceholder="/products"
                            onLabelChange={(nextValue) =>
                                updateConfig({
                                    primary_button_label: nullableEditableString(nextValue),
                                })
                            }
                            onUrlChange={(nextValue) =>
                                updateConfig({
                                    primary_button_url: nullableEditableString(nextValue),
                                })
                            }
                        />

                        <div className="border-t border-neutral-200 dark:border-neutral-800" />

                        <LinkPairFields
                            prefix="brand-section-secondary"
                            title="Secondary button"
                            label={secondaryButtonLabel}
                            url={secondaryButtonUrl}
                            labelPlaceholder="Become a Partner Brand"
                            urlPlaceholder="/contact"
                            onLabelChange={(nextValue) =>
                                updateConfig({
                                    secondary_button_label: nullableEditableString(nextValue),
                                })
                            }
                            onUrlChange={(nextValue) =>
                                updateConfig({
                                    secondary_button_url: nullableEditableString(nextValue),
                                })
                            }
                        />
                    </div>
                </section>
            )}
        </div>
    )
}

interface SectionHeadingProps {
    title: string
    description: string
}

function SectionHeading({ title, description }: SectionHeadingProps) {
    return (
        <div>
            <h3 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                {title}
            </h3>

            <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                {description}
            </p>
        </div>
    )
}

interface TextFieldProps {
    id: string
    label: string
    value: string
    maxLength: number
    placeholder?: string
    optional?: boolean

    onChange: (value: string) => void
}

function TextField({
    id,
    label,
    value,
    maxLength,
    placeholder,
    optional = false,
    onChange,
}: TextFieldProps) {
    return (
        <div>
            <div className="flex items-center justify-between gap-3">
                <label
                    htmlFor={id}
                    className="text-sm font-medium text-neutral-900 dark:text-neutral-100"
                >
                    {label}

                    {optional && (
                        <span className="ml-1 font-normal text-neutral-400">(optional)</span>
                    )}
                </label>

                <span className="text-xs text-neutral-500 dark:text-neutral-400">
                    {value.length}/{maxLength}
                </span>
            </div>

            <input
                id={id}
                type="text"
                value={value}
                maxLength={maxLength}
                placeholder={placeholder}
                onChange={(event) => onChange(event.target.value)}
                className="mt-2 w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800"
            />
        </div>
    )
}

interface TextAreaFieldProps extends TextFieldProps {
    rows?: number
}

function TextAreaField({
    id,
    label,
    value,
    maxLength,
    placeholder,
    optional = false,
    rows = 4,
    onChange,
}: TextAreaFieldProps) {
    return (
        <div>
            <div className="flex items-center justify-between gap-3">
                <label
                    htmlFor={id}
                    className="text-sm font-medium text-neutral-900 dark:text-neutral-100"
                >
                    {label}

                    {optional && (
                        <span className="ml-1 font-normal text-neutral-400">(optional)</span>
                    )}
                </label>

                <span className="text-xs text-neutral-500 dark:text-neutral-400">
                    {value.length}/{maxLength}
                </span>
            </div>

            <textarea
                id={id}
                value={value}
                rows={rows}
                maxLength={maxLength}
                placeholder={placeholder}
                onChange={(event) => onChange(event.target.value)}
                className="mt-2 w-full resize-y rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm leading-6 text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800"
            />
        </div>
    )
}

interface NumberFieldProps {
    id: string
    label: string
    value: number
    min: number
    max: number
    suffix?: string
    helpText?: string

    onChange: (value: number) => void
}

function NumberField({ id, label, value, min, max, suffix, helpText, onChange }: NumberFieldProps) {
    return (
        <div>
            <label
                htmlFor={id}
                className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
            >
                {label}
            </label>

            <div className="relative mt-2">
                <input
                    id={id}
                    type="number"
                    value={value}
                    min={min}
                    max={max}
                    onChange={(event) => {
                        const nextValue = Number(event.target.value)

                        if (Number.isInteger(nextValue)) {
                            onChange(nextValue)
                        }
                    }}
                    className={[
                        'w-full rounded-lg border border-neutral-300 bg-white',
                        'px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none',
                        'transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200',
                        'dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100',
                        'dark:focus:ring-neutral-800',

                        suffix ? 'pr-20' : '',
                    ].join(' ')}
                />

                {suffix && (
                    <span className="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-neutral-500">
                        {suffix}
                    </span>
                )}
            </div>

            {helpText && (
                <p className="mt-1.5 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                    {helpText}
                </p>
            )}
        </div>
    )
}

interface SelectOption {
    value: string
    label: string
}

interface SelectFieldProps {
    id: string
    label: string
    value: string
    options: SelectOption[]

    onChange: (value: string) => void
}

function SelectField({ id, label, value, options, onChange }: SelectFieldProps) {
    return (
        <div>
            <label
                htmlFor={id}
                className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
            >
                {label}
            </label>

            <select
                id={id}
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className="mt-2 w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800"
            >
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
        </div>
    )
}

interface ColorFieldProps {
    id: string
    label: string
    value: string

    onChange: (value: string) => void
}

function ColorField({ id, label, value, onChange }: ColorFieldProps) {
    return (
        <div>
            <label
                htmlFor={id}
                className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
            >
                {label}
            </label>

            <div className="mt-2 flex min-w-0 items-center gap-3">
                <input
                    id={`${id}-picker`}
                    type="color"
                    value={isHexColor(value) ? value : '#ffffff'}
                    aria-label={`${label} color picker`}
                    onChange={(event) => onChange(event.target.value)}
                    className="h-10 w-12 shrink-0 cursor-pointer rounded-lg border border-neutral-300 bg-white p-1 dark:border-neutral-700 dark:bg-neutral-950"
                />

                <input
                    id={id}
                    type="text"
                    value={value}
                    maxLength={7}
                    placeholder="#ffffff"
                    onChange={(event) => onChange(event.target.value)}
                    className="min-w-0 flex-1 rounded-lg border border-neutral-300 bg-white px-3 py-2 font-mono text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800"
                />
            </div>

            <p className="mt-1.5 text-xs text-neutral-500 dark:text-neutral-400">
                Use a six-digit hexadecimal color.
            </p>
        </div>
    )
}

interface ToggleFieldProps {
    id: string
    label: string
    description: string
    checked: boolean

    onChange: (checked: boolean) => void
}

function ToggleField({ id, label, description, checked, onChange }: ToggleFieldProps) {
    return (
        <label
            htmlFor={id}
            className="flex cursor-pointer gap-3 rounded-xl border border-neutral-200 p-4 transition hover:border-neutral-300 dark:border-neutral-800 dark:hover:border-neutral-700"
        >
            <input
                id={id}
                type="checkbox"
                checked={checked}
                onChange={(event) => onChange(event.target.checked)}
                className="mt-0.5 h-4 w-4 shrink-0 rounded border-neutral-300"
            />

            <span>
                <span className="block text-sm font-medium text-neutral-900 dark:text-neutral-100">
                    {label}
                </span>

                <span className="mt-1 block text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                    {description}
                </span>
            </span>
        </label>
    )
}

interface LinkPairFieldsProps {
    prefix: string
    title: string

    label: string | null
    url: string | null

    labelPlaceholder: string
    urlPlaceholder: string

    onLabelChange: (value: string) => void

    onUrlChange: (value: string) => void
}

function LinkPairFields({
    prefix,
    title,
    label,
    url,
    labelPlaceholder,
    urlPlaceholder,
    onLabelChange,
    onUrlChange,
}: LinkPairFieldsProps) {
    return (
        <div>
            <p className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">{title}</p>

            <div className="mt-3 grid gap-5 sm:grid-cols-2">
                <TextField
                    id={`${prefix}-label`}
                    label="Label"
                    value={label ?? ''}
                    maxLength={MAX_LABEL_LENGTH}
                    placeholder={labelPlaceholder}
                    optional
                    onChange={onLabelChange}
                />

                <TextField
                    id={`${prefix}-url`}
                    label="URL"
                    value={url ?? ''}
                    maxLength={MAX_URL_LENGTH}
                    placeholder={urlPlaceholder}
                    optional
                    onChange={onUrlChange}
                />
            </div>

            <p className="mt-2 text-xs text-neutral-500 dark:text-neutral-400">
                Add both values or leave both empty.
            </p>
        </div>
    )
}

function getString(value: unknown): string {
    return typeof value === 'string' ? value : ''
}

function getNullableString(value: unknown): string | null {
    return typeof value === 'string' ? value : null
}

function nullableEditableString(value: string): string | null {
    return value === '' ? null : value
}

function getInteger(value: unknown, fallback: number): number {
    return Number.isInteger(value) ? Number(value) : fallback
}

function getBoolean(value: unknown, fallback: boolean): boolean {
    return typeof value === 'boolean' ? value : fallback
}

function getAlignment(value: unknown): BrandAlignment {
    return value === 'left' ? 'left' : 'center'
}

function getTextTheme(value: unknown): BrandTextTheme {
    return value === 'light' ? 'light' : 'dark'
}

function getBackgroundColor(value: unknown): string {
    return typeof value === 'string' ? value : '#ffffff'
}

function getBrandSource(value: unknown): BrandSourceConfig {
    if (!isRecord(value)) {
        return {
            type: 'all',
        }
    }

    if (value.type === 'manual') {
        return {
            type: 'manual',

            brand_ids: Array.isArray(value.brand_ids)
                ? value.brand_ids.filter(
                      (item): item is number => Number.isInteger(item) && item > 0,
                  )
                : [],
        }
    }

    return {
        type: 'all',
    }
}

function isHexColor(value: string): boolean {
    return /^#[0-9a-fA-F]{6}$/.test(value)
}

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value)
}
