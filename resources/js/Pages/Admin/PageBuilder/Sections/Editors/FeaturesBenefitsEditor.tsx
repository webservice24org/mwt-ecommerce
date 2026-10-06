import FeatureBenefitIcon from '@/PageBuilder/feature-benefit-icons'
import {
    featureBenefitIconOptions,
    isFeatureBenefitIconName,
} from '@/PageBuilder/feature-benefit-icon-options'

import type { SectionConfig } from '@/types/page-builder'

import PageBuilderImageField from '../../Media/PageBuilderImageField'
import type { SectionEditorProps } from '../types'

const MAX_EYEBROW_LENGTH = 120
const MAX_HEADING_LENGTH = 180
const MAX_DESCRIPTION_LENGTH = 1000

const MAX_ITEMS = 12

const MAX_ITEM_TITLE_LENGTH = 160
const MAX_ITEM_DESCRIPTION_LENGTH = 1000
const MAX_LINK_LABEL_LENGTH = 80
const MAX_LINK_URL_LENGTH = 2048

const HEX_COLOR_PATTERN = /^#[0-9a-fA-F]{6}$/

type FeaturesAlignment = 'left' | 'center'

type FeaturesTextTheme = 'light' | 'dark'

type FeaturesColumns = 2 | 3 | 4

interface FeatureBenefitItem {
    title: string
    description: string
    icon: string | null
    image: string | null
    image_alt: string | null
    link_label: string | null
    link_url: string | null
}

interface FeaturesBenefitsConfig {
    eyebrow: string | null
    heading: string
    description: string

    items: FeatureBenefitItem[]

    columns: FeaturesColumns
    alignment: FeaturesAlignment

    background_color: string
    text_theme: FeaturesTextTheme
}

export default function FeaturesBenefitsEditor({
    pageId,
    section,
    value,
    onChange,
}: SectionEditorProps) {
    const eyebrow = getNullableEditableString(value.eyebrow)

    const heading = getString(value.heading)

    const description = getString(value.description)

    const items = getItems(value.items)

    const columns = getColumns(value.columns)

    const alignment = getAlignment(value.alignment)

    const backgroundColor = getString(value.background_color, '#ffffff')

    const textTheme = getTextTheme(value.text_theme)

    const updateConfig = (overrides: Partial<FeaturesBenefitsConfig>) => {
        const nextItems = overrides.items ?? items

        const nextConfig: SectionConfig = {
            eyebrow: overrides.eyebrow !== undefined ? overrides.eyebrow : eyebrow,

            heading: overrides.heading ?? heading,

            description: overrides.description ?? description,

            items: serializeItems(nextItems),

            columns: overrides.columns ?? columns,

            alignment: overrides.alignment ?? alignment,

            background_color: overrides.background_color ?? backgroundColor,

            text_theme: overrides.text_theme ?? textTheme,
        }

        onChange(nextConfig)
    }

    const updateItem = (index: number, overrides: Partial<FeatureBenefitItem>) => {
        const nextItems = items.map((item, itemIndex) =>
            itemIndex === index
                ? {
                      ...item,
                      ...overrides,
                  }
                : item,
        )

        updateConfig({
            items: nextItems,
        })
    }

    const addItem = () => {
        if (items.length >= MAX_ITEMS) {
            return
        }

        updateConfig({
            items: [...items, createEmptyItem()],
        })
    }

    const removeItem = (index: number) => {
        if (items.length <= 1) {
            return
        }

        updateConfig({
            items: items.filter((_item, itemIndex) => itemIndex !== index),
        })
    }

    const moveItem = (index: number, direction: -1 | 1) => {
        const targetIndex = index + direction

        if (targetIndex < 0 || targetIndex >= items.length) {
            return
        }

        const nextItems = [...items]

        const current = nextItems[index]

        const target = nextItems[targetIndex]

        if (!current || !target) {
            return
        }

        nextItems[index] = target

        nextItems[targetIndex] = current

        updateConfig({
            items: nextItems,
        })
    }

    const colorPickerValue = HEX_COLOR_PATTERN.test(backgroundColor) ? backgroundColor : '#ffffff'

    const usesIcons = section.template === 'icon_grid' || section.template === 'horizontal_benefits'

    const usesImages = section.template === 'image_grid'

    const showColumns = section.template !== 'horizontal_benefits'

    return (
        <div className="space-y-6">
            {/* SECTION CONTENT */}
            <section className="rounded-lg border border-neutral-200 p-4 dark:border-neutral-800">
                <SectionHeading
                    title="Section Content"
                    description="Configure the heading and supporting text displayed above the benefits."
                />

                <div className="space-y-5">
                    <TextField
                        id="features-benefits-eyebrow"
                        label="Eyebrow"
                        value={eyebrow ?? ''}
                        maxLength={MAX_EYEBROW_LENGTH}
                        placeholder="Why choose us"
                        helpText="Optional short text displayed above the main heading."
                        onChange={(nextValue) =>
                            updateConfig({
                                eyebrow: nullableEditableString(nextValue),
                            })
                        }
                    />

                    <TextField
                        id="features-benefits-heading"
                        label="Heading"
                        value={heading}
                        maxLength={MAX_HEADING_LENGTH}
                        required
                        placeholder="Benefits built around your shopping experience"
                        helpText="Required main heading for this section."
                        onChange={(nextValue) =>
                            updateConfig({
                                heading: nextValue,
                            })
                        }
                    />

                    <TextAreaField
                        id="features-benefits-description"
                        label="Description"
                        value={description}
                        maxLength={MAX_DESCRIPTION_LENGTH}
                        rows={4}
                        placeholder="Add supporting text for this benefits section..."
                        helpText="Optional supporting plain-text description."
                        onChange={(nextValue) =>
                            updateConfig({
                                description: nextValue,
                            })
                        }
                    />
                </div>
            </section>

            {/* BENEFIT ITEMS */}
            <section className="rounded-lg border border-neutral-200 p-4 dark:border-neutral-800">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <SectionHeading
                        title="Benefits"
                        description="Add, remove, reorder, and configure the content of each feature or benefit."
                        className="mb-0"
                    />

                    <div className="shrink-0">
                        <button
                            type="button"
                            disabled={items.length >= MAX_ITEMS}
                            onClick={addItem}
                            className="inline-flex min-h-10 items-center justify-center rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm font-semibold text-neutral-900 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:hover:bg-neutral-900"
                        >
                            Add Benefit
                        </button>
                    </div>
                </div>

                <div className="mt-5 space-y-4">
                    {items.map((item, index) => (
                        <BenefitItemEditor
                            key={index}
                            pageId={pageId}
                            item={item}
                            index={index}
                            count={items.length}
                            usesIcons={usesIcons}
                            usesImages={usesImages}
                            onChange={(overrides) => updateItem(index, overrides)}
                            onMoveUp={() => moveItem(index, -1)}
                            onMoveDown={() => moveItem(index, 1)}
                            onRemove={() => removeItem(index)}
                        />
                    ))}
                </div>

                <div className="mt-4 flex flex-wrap items-center justify-between gap-3 text-xs text-neutral-500 dark:text-neutral-400">
                    <span>
                        {items.length} of {MAX_ITEMS} benefits
                    </span>

                    <span>Minimum 1 benefit.</span>
                </div>
            </section>

            {/* LAYOUT */}
            <section className="rounded-lg border border-neutral-200 p-4 dark:border-neutral-800">
                <SectionHeading
                    title="Layout"
                    description="Control how the benefit items are aligned and arranged."
                />
                {showColumns && (
                    <div className={['grid gap-5', showColumns ? 'sm:grid-cols-2' : ''].join(' ')}>
                        <div>
                            <label
                                htmlFor="features-benefits-columns"
                                className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                            >
                                Columns
                            </label>

                            <select
                                id="features-benefits-columns"
                                value={columns}
                                onChange={(event) =>
                                    updateConfig({
                                        columns: Number(event.target.value) as FeaturesColumns,
                                    })
                                }
                                className="mt-2 w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800"
                            >
                                <option value={2}>2 columns</option>

                                <option value={3}>3 columns</option>

                                <option value={4}>4 columns</option>
                            </select>

                            <p className="mt-1.5 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                                Storefront templates will adapt these columns responsively on
                                smaller devices.
                            </p>
                        </div>

                        <div>
                            <label
                                htmlFor="features-benefits-alignment"
                                className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                            >
                                Content alignment
                            </label>

                            <select
                                id="features-benefits-alignment"
                                value={alignment}
                                onChange={(event) =>
                                    updateConfig({
                                        alignment: event.target.value as FeaturesAlignment,
                                    })
                                }
                                className="mt-2 w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800"
                            >
                                <option value="left">Left</option>

                                <option value="center">Center</option>
                            </select>
                        </div>
                    </div>
                )}
            </section>

            {/* APPEARANCE */}
            <section className="rounded-lg border border-neutral-200 p-4 dark:border-neutral-800">
                <SectionHeading
                    title="Appearance"
                    description="Configure the background and text theme used by this section."
                />

                <div className="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label
                            htmlFor="features-benefits-background-color"
                            className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                        >
                            Background color
                        </label>

                        <div className="mt-2 flex gap-3">
                            <input
                                type="color"
                                aria-label="Choose background color"
                                value={colorPickerValue}
                                onChange={(event) =>
                                    updateConfig({
                                        background_color: event.target.value,
                                    })
                                }
                                className="h-10 w-14 shrink-0 cursor-pointer rounded-lg border border-neutral-300 bg-white p-1 dark:border-neutral-700 dark:bg-neutral-950"
                            />

                            <input
                                id="features-benefits-background-color"
                                type="text"
                                value={backgroundColor}
                                maxLength={7}
                                placeholder="#ffffff"
                                onChange={(event) =>
                                    updateConfig({
                                        background_color: event.target.value,
                                    })
                                }
                                className="min-w-0 flex-1 rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800"
                            />
                        </div>

                        <p className="mt-1.5 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                            Use a 6-digit hexadecimal color such as #ffffff.
                        </p>
                    </div>

                    <div>
                        <label
                            htmlFor="features-benefits-text-theme"
                            className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                        >
                            Text theme
                        </label>

                        <select
                            id="features-benefits-text-theme"
                            value={textTheme}
                            onChange={(event) =>
                                updateConfig({
                                    text_theme: event.target.value as FeaturesTextTheme,
                                })
                            }
                            className="mt-2 w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800"
                        >
                            <option value="dark">Dark text</option>

                            <option value="light">Light text</option>
                        </select>
                    </div>
                </div>
            </section>
        </div>
    )
}

interface BenefitItemEditorProps {
    pageId: number

    item: FeatureBenefitItem
    index: number
    count: number

    usesIcons: boolean
    usesImages: boolean

    onChange: (overrides: Partial<FeatureBenefitItem>) => void

    onMoveUp: () => void
    onMoveDown: () => void
    onRemove: () => void
}

function BenefitItemEditor({
    pageId,
    item,
    index,
    count,
    usesIcons,
    usesImages,
    onChange,
    onMoveUp,
    onMoveDown,
    onRemove,
}: BenefitItemEditorProps) {
    const number = index + 1

    return (
        <article className="rounded-xl border border-neutral-200 bg-neutral-50/60 p-4 dark:border-neutral-800 dark:bg-neutral-900/30">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h4 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                        Benefit {number}
                    </h4>

                    <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                        Configure the content for this benefit item.
                    </p>
                </div>

                <div className="flex flex-wrap gap-2">
                    <ItemActionButton label="Move up" disabled={index === 0} onClick={onMoveUp} />

                    <ItemActionButton
                        label="Move down"
                        disabled={index === count - 1}
                        onClick={onMoveDown}
                    />

                    <button
                        type="button"
                        disabled={count <= 1}
                        onClick={onRemove}
                        className="inline-flex min-h-9 items-center justify-center rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-red-900/60 dark:bg-neutral-950 dark:text-red-300 dark:hover:bg-red-950/30"
                    >
                        Remove
                    </button>
                </div>
            </div>

            <div className="mt-5 space-y-5">
                <TextField
                    id={`features-benefits-item-${index}-title`}
                    label="Title"
                    value={item.title}
                    maxLength={MAX_ITEM_TITLE_LENGTH}
                    required
                    placeholder="Fast Delivery"
                    onChange={(nextValue) =>
                        onChange({
                            title: nextValue,
                        })
                    }
                />

                <TextAreaField
                    id={`features-benefits-item-${index}-description`}
                    label="Description"
                    value={item.description}
                    maxLength={MAX_ITEM_DESCRIPTION_LENGTH}
                    rows={3}
                    placeholder="Describe this benefit..."
                    onChange={(nextValue) =>
                        onChange({
                            description: nextValue,
                        })
                    }
                />

                {usesIcons && (
                    <FeatureIconField
                        value={item.icon}
                        groupName={`feature-benefit-icon-${index}`}
                        onChange={(icon) =>
                            onChange({
                                icon,
                            })
                        }
                    />
                )}

                {usesImages && (
                    <div className="space-y-5">
                        <PageBuilderImageField
                            pageId={pageId}
                            label="Benefit image"
                            value={item.image}
                            helpText="Upload the image displayed for this benefit card."
                            onChange={(image) =>
                                onChange({
                                    image,
                                })
                            }
                        />

                        <TextField
                            id={`features-benefits-item-${index}-image-alt`}
                            label="Image alt text"
                            value={item.image_alt ?? ''}
                            maxLength={255}
                            placeholder="Describe the image"
                            helpText="Describe meaningful images for screen-reader users. Leave blank only when the image is purely decorative."
                            onChange={(nextValue) =>
                                onChange({
                                    image_alt: nullableEditableString(nextValue),
                                })
                            }
                        />
                    </div>
                )}

                <div className="grid gap-5 sm:grid-cols-2">
                    <TextField
                        id={`features-benefits-item-${index}-link-label`}
                        label="Link label"
                        value={item.link_label ?? ''}
                        maxLength={MAX_LINK_LABEL_LENGTH}
                        placeholder="Learn more"
                        helpText="Optional. Add both a label and URL, or leave both blank."
                        onChange={(nextValue) =>
                            onChange({
                                link_label: nullableEditableString(nextValue),
                            })
                        }
                    />

                    <TextField
                        id={`features-benefits-item-${index}-link-url`}
                        label="Link URL"
                        value={item.link_url ?? ''}
                        maxLength={MAX_LINK_URL_LENGTH}
                        placeholder="/shipping"
                        helpText="Use an internal /path, a #section anchor, or an http/https URL. Add both a link label and URL."
                        onChange={(nextValue) =>
                            onChange({
                                link_url: nullableEditableString(nextValue),
                            })
                        }
                    />
                </div>
            </div>
        </article>
    )
}

interface FeatureIconFieldProps {
    value: string | null
    groupName: string

    onChange: (value: string | null) => void
}

function FeatureIconField({ value, groupName, onChange }: FeatureIconFieldProps) {
    const hasSelectedIcon = isFeatureBenefitIconName(value)

    return (
        <fieldset>
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <legend className="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                        Icon
                    </legend>

                    <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                        Choose the icon displayed with this benefit.
                    </p>
                </div>

                {hasSelectedIcon && (
                    <div
                        aria-hidden="true"
                        className="flex h-10 w-10 items-center justify-center rounded-lg border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-950"
                    >
                        <FeatureBenefitIcon name={value} className="h-5 w-5" />
                    </div>
                )}
            </div>

            <div className="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                {featureBenefitIconOptions.map((option) => {
                    const selected = value === option.value

                    return (
                        <label
                            key={option.value}
                            className={[
                                'relative flex min-h-14 cursor-pointer items-center gap-2',
                                'rounded-lg border px-3 py-2',
                                'text-left text-xs font-medium transition',
                                'has-[:focus-visible]:ring-2',
                                'has-[:focus-visible]:ring-neutral-500',
                                'has-[:focus-visible]:ring-offset-2',
                                selected
                                    ? 'border-neutral-900 bg-neutral-900 text-white dark:border-neutral-100 dark:bg-neutral-100 dark:text-neutral-900'
                                    : 'border-neutral-200 bg-white text-neutral-700 hover:border-neutral-400 hover:bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-950 dark:text-neutral-200 dark:hover:border-neutral-600 dark:hover:bg-neutral-900',
                            ].join(' ')}
                        >
                            <input
                                type="radio"
                                name={groupName}
                                value={option.value}
                                checked={selected}
                                onChange={() => onChange(option.value)}
                                className="sr-only"
                            />

                            <FeatureBenefitIcon name={option.value} className="h-4 w-4 shrink-0" />

                            <span className="min-w-0 break-words">{option.label}</span>
                        </label>
                    )
                })}
            </div>

            <button
                type="button"
                disabled={value === null}
                onClick={() => onChange(null)}
                className={[
                    'mt-3 min-h-10 rounded-md px-2',
                    'text-xs font-medium text-neutral-500',
                    'underline underline-offset-4 transition',
                    'hover:text-neutral-900',
                    'focus-visible:outline-none focus-visible:ring-2',
                    'focus-visible:ring-neutral-500 focus-visible:ring-offset-2',
                    'disabled:cursor-not-allowed disabled:opacity-40',
                    'dark:hover:text-neutral-100',
                ].join(' ')}
            >
                Clear icon
            </button>
        </fieldset>
    )
}

interface ItemActionButtonProps {
    label: string
    disabled: boolean
    onClick: () => void
}

function ItemActionButton({ label, disabled, onClick }: ItemActionButtonProps) {
    return (
        <button
            type="button"
            disabled={disabled}
            onClick={onClick}
            className="inline-flex min-h-9 items-center justify-center rounded-lg border border-neutral-300 bg-white px-3 py-1.5 text-xs font-semibold text-neutral-700 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-200 dark:hover:bg-neutral-900"
        >
            {label}
        </button>
    )
}

interface SectionHeadingProps {
    title: string
    description: string
    className?: string
}

function SectionHeading({ title, description, className = 'mb-4' }: SectionHeadingProps) {
    return (
        <div className={className}>
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
    helpText?: string
    required?: boolean

    onChange: (value: string) => void
}

function TextField({
    id,
    label,
    value,
    maxLength,
    placeholder,
    helpText,
    required = false,
    onChange,
}: TextFieldProps) {
    return (
        <div>
            <div className="flex items-center justify-between gap-3">
                <label
                    htmlFor={id}
                    className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                >
                    {label}

                    {required && <span className="ml-1 text-red-500">*</span>}
                </label>

                <span className="shrink-0 text-xs text-neutral-500 dark:text-neutral-400">
                    {characterCount(value)}/{maxLength}
                </span>
            </div>

            <input
                id={id}
                type="text"
                value={value}
                maxLength={maxLength}
                required={required}
                onChange={(event) => onChange(event.target.value)}
                placeholder={placeholder}
                className="mt-2 w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800"
            />

            {helpText && (
                <p className="mt-1.5 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                    {helpText}
                </p>
            )}
        </div>
    )
}

interface TextAreaFieldProps {
    id: string
    label: string
    value: string
    maxLength: number

    rows?: number
    placeholder?: string
    helpText?: string

    onChange: (value: string) => void
}

function TextAreaField({
    id,
    label,
    value,
    maxLength,
    rows = 4,
    placeholder,
    helpText,
    onChange,
}: TextAreaFieldProps) {
    return (
        <div>
            <div className="flex items-center justify-between gap-3">
                <label
                    htmlFor={id}
                    className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                >
                    {label}
                </label>

                <span className="shrink-0 text-xs text-neutral-500 dark:text-neutral-400">
                    {characterCount(value)}/{maxLength}
                </span>
            </div>

            <textarea
                id={id}
                value={value}
                maxLength={maxLength}
                rows={rows}
                onChange={(event) => onChange(event.target.value)}
                placeholder={placeholder}
                className="mt-2 w-full resize-y rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm leading-6 text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800"
            />

            {helpText && (
                <p className="mt-1.5 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                    {helpText}
                </p>
            )}
        </div>
    )
}

function createEmptyItem(): FeatureBenefitItem {
    return {
        title: 'New benefit',

        description: '',

        icon: null,

        image: null,

        image_alt: null,

        link_label: null,

        link_url: null,
    }
}

function getItems(value: unknown): FeatureBenefitItem[] {
    if (!Array.isArray(value)) {
        return []
    }

    return value.map((item) => normalizeItem(item))
}

function normalizeItem(value: unknown): FeatureBenefitItem {
    if (!isObject(value)) {
        return createEmptyItem()
    }

    return {
        title: getString(value.title),

        description: getString(value.description),

        icon: getNullableEditableString(value.icon),

        image: getNullableEditableString(value.image),

        image_alt: getNullableEditableString(value.image_alt),

        link_label: getNullableEditableString(value.link_label),

        link_url: getNullableEditableString(value.link_url),
    }
}

function serializeItems(items: FeatureBenefitItem[]) {
    return items.map((item) => ({
        title: item.title,

        description: item.description,

        icon: item.icon,

        image: item.image,

        image_alt: item.image_alt,

        link_label: item.link_label,

        link_url: item.link_url,
    }))
}

function getString(value: unknown, fallback = ''): string {
    return typeof value === 'string' ? value : fallback
}

function getNullableEditableString(value: unknown): string | null {
    return typeof value === 'string' ? value : null
}

function nullableEditableString(value: string): string | null {
    return value === '' ? null : value
}

function getColumns(value: unknown): FeaturesColumns {
    if (value === 2 || value === 3 || value === 4) {
        return value
    }

    return 3
}

function getAlignment(value: unknown): FeaturesAlignment {
    return value === 'center' ? 'center' : 'left'
}

function getTextTheme(value: unknown): FeaturesTextTheme {
    return value === 'light' ? 'light' : 'dark'
}

function characterCount(value: string): number {
    return Array.from(value).length
}

function isObject(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value)
}
