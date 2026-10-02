import { useState } from 'react'

import type { SectionConfig } from '@/types/page-builder'

import type { SectionEditorProps } from '../types'

const MAX_TITLE_LENGTH = 120

const COLUMN_OPTIONS = [2, 3, 4, 5, 6] as const

const MIN_AUTOPLAY_DELAY = 1000
const MAX_AUTOPLAY_DELAY = 30000

const CAROUSEL_EFFECT_OPTIONS = [
    {
        value: 'fade',
        label: 'Fade',
    },
    {
        value: 'slide_left',
        label: 'Slide left',
    },
    {
        value: 'slide_right',
        label: 'Slide right',
    },
    {
        value: 'slide_up',
        label: 'Slide up',
    },
    {
        value: 'slide_down',
        label: 'Slide down',
    },
] as const

type CarouselEffect = (typeof CAROUSEL_EFFECT_OPTIONS)[number]['value']

export default function ProductCategoriesEditor({
    section,
    value,
    categoryOptions,
    onChange,
}: SectionEditorProps) {
    const parentTitle = getString(value.title)

    const [title, setTitle] = useState(() => parentTitle)

    const categoryIds = getCategoryIds(value.category_ids)

    const showName = getBoolean(value.show_name, true)

    const columns = getColumns(value.columns, section.template === 'cards' ? 3 : 4)

    const showProductCount = getBoolean(value.show_product_count, section.template === 'cards')

    const isCarousel = section.template === 'carousel'

    const autoplay = getBoolean(value.autoplay, true)

    const autoplayDelay = getNumber(value.autoplay_delay, 5000)

    const showArrows = getBoolean(value.show_arrows, true)

    const showDots = getBoolean(value.show_dots, true)

    const effect = getCarouselEffect(value.effect)

    const updateConfig = (overrides: Partial<ProductCategoriesConfig>) => {
        const nextConfig: SectionConfig = {
            title: overrides.title ?? title,

            category_ids: overrides.category_ids ?? categoryIds,

            show_name: overrides.show_name ?? showName,

            columns: overrides.columns ?? columns,

            show_product_count: overrides.show_product_count ?? showProductCount,
        }

        if (isCarousel) {
            nextConfig.autoplay = overrides.autoplay ?? autoplay

            nextConfig.autoplay_delay = overrides.autoplay_delay ?? autoplayDelay

            nextConfig.show_arrows = overrides.show_arrows ?? showArrows

            nextConfig.show_dots = overrides.show_dots ?? showDots

            nextConfig.effect = overrides.effect ?? effect
        }

        onChange(nextConfig)
    }

    const handleTitleChange = (nextTitle: string) => {
        setTitle(nextTitle)

        updateConfig({
            title: nextTitle,
        })
    }

    const toggleCategory = (categoryId: number) => {
        const selected = categoryIds.includes(categoryId)

        updateConfig({
            category_ids: selected
                ? categoryIds.filter((id) => id !== categoryId)
                : [...categoryIds, categoryId],
        })
    }

    const selectAllCategories = () => {
        updateConfig({
            category_ids: categoryOptions.map((category) => category.id),
        })
    }

    const clearCategories = () => {
        updateConfig({
            category_ids: [],
        })
    }

    return (
        <div className="space-y-6">
            <div>
                <label
                    htmlFor="product-categories-title"
                    className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                >
                    Section title
                </label>

                <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                    The heading displayed above the product categories.
                </p>

                <input
                    id="product-categories-title"
                    type="text"
                    value={title}
                    maxLength={MAX_TITLE_LENGTH}
                    required
                    autoComplete="on"
                    onChange={(event) => handleTitleChange(event.target.value)}
                    className="mt-2 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:border-neutral-500 dark:focus:ring-neutral-800"
                />

                <div className="mt-1 flex items-center justify-between gap-3 text-xs text-neutral-400">
                    <span>Maximum {MAX_TITLE_LENGTH} characters.</span>

                    <span>
                        {title.length}/{MAX_TITLE_LENGTH}
                    </span>
                </div>
            </div>

            <div className="border-t border-neutral-200 pt-6 dark:border-neutral-800">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 className="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                            Categories
                        </h3>

                        <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                            Choose the product categories to display in this section.
                        </p>
                    </div>

                    <div className="flex shrink-0 gap-2">
                        <button
                            type="button"
                            onClick={selectAllCategories}
                            className="rounded-md border border-neutral-200 px-3 py-1.5 text-xs font-medium text-neutral-700 transition hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-900"
                        >
                            Select all
                        </button>

                        <button
                            type="button"
                            onClick={clearCategories}
                            className="rounded-md border border-neutral-200 px-3 py-1.5 text-xs font-medium text-neutral-700 transition hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-900"
                        >
                            Clear
                        </button>
                    </div>
                </div>

                <div className="mt-4">
                    {categoryOptions.length === 0 ? (
                        <div className="rounded-lg border border-dashed border-neutral-300 px-4 py-5 text-sm text-neutral-500 dark:border-neutral-700 dark:text-neutral-400">
                            No product categories are available.
                        </div>
                    ) : (
                        <div className="grid gap-2 sm:grid-cols-2">
                            {categoryOptions.map((category) => {
                                const checked = categoryIds.includes(category.id)

                                return (
                                    <label
                                        key={category.id}
                                        className={[
                                            'flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition',
                                            checked
                                                ? 'border-neutral-900 bg-neutral-50 dark:border-neutral-100 dark:bg-neutral-900'
                                                : 'border-neutral-200 bg-white hover:border-neutral-300 dark:border-neutral-800 dark:bg-neutral-950 dark:hover:border-neutral-700',
                                        ].join(' ')}
                                    >
                                        <input
                                            type="checkbox"
                                            checked={checked}
                                            onChange={() => toggleCategory(category.id)}
                                            className="mt-0.5 h-4 w-4 rounded border-neutral-300"
                                        />

                                        <span className="min-w-0">
                                            <span className="block truncate text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                                {category.name}
                                            </span>

                                            <span className="mt-0.5 block truncate text-xs text-neutral-500 dark:text-neutral-400">
                                                {category.slug}
                                            </span>
                                        </span>
                                    </label>
                                )
                            })}
                        </div>
                    )}
                </div>

                <p className="mt-2 text-xs text-neutral-400">{categoryIds.length} selected</p>
            </div>

            <div className="border-t border-neutral-200 pt-6 dark:border-neutral-800">
                <label
                    htmlFor="product-categories-columns"
                    className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                >
                    Columns
                </label>

                <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                    Choose how many category items are displayed per row on larger screens.
                </p>

                <select
                    id="product-categories-columns"
                    value={columns}
                    onChange={(event) =>
                        updateConfig({
                            columns: Number(event.target.value),
                        })
                    }
                    className="mt-2 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:border-neutral-500 dark:focus:ring-neutral-800 sm:max-w-48"
                >
                    {COLUMN_OPTIONS.map((column) => (
                        <option key={column} value={column}>
                            {column} columns
                        </option>
                    ))}
                </select>
            </div>

            <div className="border-t border-neutral-200 pt-6 dark:border-neutral-800">
                <h3 className="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                    Display options
                </h3>

                <div className="mt-3 space-y-3">
                    <ToggleOption
                        label="Show category name"
                        description="Display the category name on each category item."
                        checked={showName}
                        onChange={(checked) =>
                            updateConfig({
                                show_name: checked,
                            })
                        }
                    />

                    <ToggleOption
                        label="Show product count"
                        description="Display the number of products available in each category."
                        checked={showProductCount}
                        onChange={(checked) =>
                            updateConfig({
                                show_product_count: checked,
                            })
                        }
                    />
                </div>
            </div>

            {isCarousel && (
                <div className="border-t border-neutral-200 pt-6 dark:border-neutral-800">
                    <h3 className="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                        Carousel settings
                    </h3>

                    <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                        Configure automatic movement and navigation controls for the category
                        carousel.
                    </p>

                    <div className="mt-4 space-y-4">
                        <ToggleOption
                            label="Autoplay"
                            description="Automatically advance through categories."
                            checked={autoplay}
                            onChange={(checked) =>
                                updateConfig({
                                    autoplay: checked,
                                })
                            }
                        />

                        <div>
                            <label
                                htmlFor="product-categories-carousel-effect"
                                className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                            >
                                Transition effect
                            </label>

                            <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                                Choose how category pages transition when the carousel moves.
                            </p>

                            <select
                                id="product-categories-carousel-effect"
                                value={effect}
                                onChange={(event) =>
                                    updateConfig({
                                        effect: event.target.value as CarouselEffect,
                                    })
                                }
                                className="mt-2 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:border-neutral-500 dark:focus:ring-neutral-800 sm:max-w-64"
                            >
                                {CAROUSEL_EFFECT_OPTIONS.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label
                                htmlFor="product-categories-autoplay-delay"
                                className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                            >
                                Autoplay delay
                            </label>

                            <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                                Time between automatic carousel movements in milliseconds.
                            </p>

                            <input
                                id="product-categories-autoplay-delay"
                                type="number"
                                value={autoplayDelay}
                                min={MIN_AUTOPLAY_DELAY}
                                max={MAX_AUTOPLAY_DELAY}
                                step={500}
                                disabled={!autoplay}
                                onChange={(event) => {
                                    const nextValue = Number(event.target.value)

                                    if (!Number.isInteger(nextValue)) {
                                        return
                                    }

                                    updateConfig({
                                        autoplay_delay: nextValue,
                                    })
                                }}
                                className="mt-2 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:border-neutral-500 dark:focus:ring-neutral-800 sm:max-w-48"
                            />

                            <p className="mt-1 text-xs text-neutral-400">
                                {MIN_AUTOPLAY_DELAY}–{MAX_AUTOPLAY_DELAY} ms
                            </p>
                        </div>

                        <ToggleOption
                            label="Show arrows"
                            description="Display previous and next navigation buttons."
                            checked={showArrows}
                            onChange={(checked) =>
                                updateConfig({
                                    show_arrows: checked,
                                })
                            }
                        />

                        <ToggleOption
                            label="Show dots"
                            description="Display carousel position indicators."
                            checked={showDots}
                            onChange={(checked) =>
                                updateConfig({
                                    show_dots: checked,
                                })
                            }
                        />
                    </div>
                </div>
            )}
        </div>
    )
}

interface ToggleOptionProps {
    label: string
    description: string
    checked: boolean
    onChange: (checked: boolean) => void
}

function ToggleOption({ label, description, checked, onChange }: ToggleOptionProps) {
    return (
        <label className="flex cursor-pointer items-start gap-3 rounded-lg border border-neutral-200 p-4 dark:border-neutral-800">
            <input
                type="checkbox"
                checked={checked}
                onChange={(event) => onChange(event.target.checked)}
                className="mt-0.5 h-4 w-4 rounded border-neutral-300"
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

interface ProductCategoriesConfig {
    title: string
    category_ids: number[]
    show_name: boolean
    columns: number
    show_product_count: boolean
    autoplay: boolean
    autoplay_delay: number
    show_arrows: boolean
    show_dots: boolean
    effect: CarouselEffect
}

function getString(value: SectionConfig[string]): string {
    return typeof value === 'string' ? value : ''
}

function getBoolean(value: SectionConfig[string], fallback: boolean): boolean {
    return typeof value === 'boolean' ? value : fallback
}

function getNumber(value: SectionConfig[string], fallback: number): number {
    return typeof value === 'number' && Number.isFinite(value) ? value : fallback
}

function getColumns(value: SectionConfig[string], fallback: number): number {
    const columns = getNumber(value, fallback)

    return COLUMN_OPTIONS.some((option) => option === columns) ? columns : fallback
}

function getCategoryIds(value: SectionConfig[string]): number[] {
    if (!Array.isArray(value)) {
        return []
    }

    return value.filter(
        (item, index, items): item is number =>
            typeof item === 'number' &&
            Number.isInteger(item) &&
            item > 0 &&
            items.indexOf(item) === index,
    )
}

function getCarouselEffect(value: SectionConfig[string]): CarouselEffect {
    if (
        typeof value === 'string' &&
        CAROUSEL_EFFECT_OPTIONS.some((option) => option.value === value)
    ) {
        return value as CarouselEffect
    }

    return 'fade'
}
