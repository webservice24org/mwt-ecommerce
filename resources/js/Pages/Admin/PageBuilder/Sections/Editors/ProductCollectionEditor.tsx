import { useEffect, useState } from 'react'

import type {
    CatalogProductOption,
    CatalogSourceType,
    JsonValue,
    SectionConfig,
} from '@/types/page-builder'

import type { SectionEditorProps } from '../types'

const MIN_LIMIT = 1
const MAX_LIMIT = 24
const MAX_TITLE_LENGTH = 120
const PRODUCT_SEARCH_DELAY = 300

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

export default function ProductCollectionEditor({
    pageId,
    section,
    value,
    catalogSources,
    categoryOptions,
    selectedProductOptions,
    onChange,
}: SectionEditorProps) {
    const parentTitle = getString(value.title)
    const limit = getNumber(value.limit, 8)

    const source = getSource(value.source)
    const sourceType = getSourceType(source.type)

    const columns = getColumns(value.columns, section.template === 'cards' ? 3 : 4)

    const showPrice = getBoolean(value.show_price, true)
    const showRating = getBoolean(value.show_rating, true)
    const showBadges = getBoolean(value.show_badges, true)

    const isCarousel = section.template === 'carousel'

    const autoplay = getBoolean(value.autoplay, true)
    const autoplayDelay = getNumber(value.autoplay_delay, 5000)
    const showArrows = getBoolean(value.show_arrows, true)
    const showDots = getBoolean(value.show_dots, true)
    const effect = getCarouselEffect(value.effect)

    const [title, setTitle] = useState(() => parentTitle)

    const [search, setSearch] = useState('')
    const [searchResults, setSearchResults] = useState<CatalogProductOption[]>([])
    const [searching, setSearching] = useState(false)
    const [searchError, setSearchError] = useState<string | null>(null)

    const productIds = getProductIds(source.product_ids)

    const productsById = new Map(selectedProductOptions.map((product) => [product.id, product]))

    for (const product of searchResults) {
        productsById.set(product.id, product)
    }

    const selectedProducts = productIds.map((id) => ({
        id,
        product: productsById.get(id) ?? null,
    }))

    useEffect(() => {
        const term = search.trim()

        if (sourceType !== 'manual' || term.length < 2) {
            return
        }

        const controller = new AbortController()

        const timer = window.setTimeout(() => {
            setSearching(true)

            const url = new URL(
                route('admin.pages.builder.products', pageId),
                window.location.origin,
            )

            url.searchParams.set('search', term)

            void fetch(url.toString(), {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                signal: controller.signal,
            })
                .then(async (response) => {
                    if (!response.ok) {
                        throw new Error('Product search failed.')
                    }

                    return (await response.json()) as {
                        products?: CatalogProductOption[]
                    }
                })
                .then((payload) => {
                    if (controller.signal.aborted) {
                        return
                    }

                    setSearchResults(Array.isArray(payload.products) ? payload.products : [])

                    setSearchError(null)
                })
                .catch((error: unknown) => {
                    if (controller.signal.aborted) {
                        return
                    }

                    setSearchResults([])

                    setSearchError(
                        error instanceof Error ? error.message : 'Product search failed.',
                    )
                })
                .finally(() => {
                    if (!controller.signal.aborted) {
                        setSearching(false)
                    }
                })
        }, PRODUCT_SEARCH_DELAY)

        return () => {
            window.clearTimeout(timer)
            controller.abort()
        }
    }, [pageId, search, sourceType])

    const updateConfig = (overrides: Partial<ProductCollectionConfig>) => {
        const nextConfig: SectionConfig = {
            title: overrides.title ?? title,
            limit: overrides.limit ?? limit,
            source: overrides.source ?? source,
            show_price: overrides.show_price ?? showPrice,
            show_rating: overrides.show_rating ?? showRating,
            show_badges: overrides.show_badges ?? showBadges,
            columns: overrides.columns ?? columns,
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

    const updateSource = (nextSource: Record<string, JsonValue>) => {
        updateConfig({
            source: nextSource,
        })
    }

    const resetSearch = () => {
        setSearch('')
        setSearchResults([])
        setSearchError(null)
        setSearching(false)
    }

    const changeSourceType = (nextType: CatalogSourceType) => {
        resetSearch()

        switch (nextType) {
            case 'latest':
                updateSource({
                    type: 'latest',
                })
                break

            case 'featured':
                updateSource({
                    type: 'featured',
                })
                break

            case 'category':
                updateSource({
                    type: 'category',
                    category_id: null,
                })
                break

            case 'manual':
                updateSource({
                    type: 'manual',
                    product_ids: [],
                })
                break
        }
    }

    const handleTitleChange = (nextTitle: string) => {
        setTitle(nextTitle)

        updateConfig({
            title: nextTitle,
        })
    }

    const handleSearchChange = (nextSearch: string) => {
        setSearch(nextSearch)

        if (nextSearch.trim().length < 2) {
            setSearchResults([])
            setSearchError(null)
            setSearching(false)
        }
    }

    const selectProduct = (product: CatalogProductOption) => {
        if (productIds.includes(product.id) || productIds.length >= MAX_LIMIT) {
            return
        }

        updateSource({
            type: 'manual',
            product_ids: [...productIds, product.id],
        })

        setSearchResults((current) => {
            const exists = current.some((item) => item.id === product.id)

            return exists ? current : [...current, product]
        })
    }

    const removeProduct = (productId: number) => {
        updateSource({
            type: 'manual',
            product_ids: productIds.filter((id) => id !== productId),
        })
    }

    const availableSearchResults = searchResults.filter(
        (product) => !productIds.includes(product.id),
    )

    return (
        <div className="space-y-6">
            <div>
                <label
                    htmlFor="product-collection-title"
                    className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                >
                    Section title
                </label>

                <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                    The heading displayed above the product collection.
                </p>

                <input
                    id="product-collection-title"
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

            <div>
                <label
                    htmlFor="product-collection-limit"
                    className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                >
                    Product limit
                </label>

                <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                    Choose the maximum number of products this section can display.
                </p>

                <input
                    id="product-collection-limit"
                    type="number"
                    value={limit}
                    min={MIN_LIMIT}
                    max={MAX_LIMIT}
                    step={1}
                    required
                    onChange={(event) => {
                        const nextLimit = Number(event.target.value)

                        if (!Number.isInteger(nextLimit)) {
                            return
                        }

                        updateConfig({
                            limit: nextLimit,
                        })
                    }}
                    className="mt-2 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:border-neutral-500 dark:focus:ring-neutral-800 sm:max-w-48"
                />

                <p className="mt-1 text-xs text-neutral-400">
                    Allowed range: {MIN_LIMIT}–{MAX_LIMIT} products.
                </p>
            </div>

            <div className="border-t border-neutral-200 pt-6 dark:border-neutral-800">
                <fieldset>
                    <legend className="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                        Product source
                    </legend>

                    <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                        Choose how products are selected for this collection.
                    </p>

                    <div className="mt-3 grid gap-3 sm:grid-cols-2">
                        {catalogSources.map((sourceDefinition) => {
                            const checked = sourceType === sourceDefinition.type

                            return (
                                <label
                                    key={sourceDefinition.type}
                                    className={[
                                        'cursor-pointer rounded-lg border p-4 transition',
                                        checked
                                            ? 'border-neutral-900 bg-neutral-50 dark:border-neutral-100 dark:bg-neutral-900'
                                            : 'border-neutral-200 bg-white hover:border-neutral-300 dark:border-neutral-800 dark:bg-neutral-950 dark:hover:border-neutral-700',
                                    ].join(' ')}
                                >
                                    <div className="flex items-start gap-3">
                                        <input
                                            type="radio"
                                            name="product-collection-source"
                                            value={sourceDefinition.type}
                                            checked={checked}
                                            onChange={() => changeSourceType(sourceDefinition.type)}
                                            className="mt-1"
                                        />

                                        <span className="min-w-0">
                                            <span className="block text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                                {sourceDefinition.label}
                                            </span>

                                            <span className="mt-1 block text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                                                {sourceDefinition.description}
                                            </span>
                                        </span>
                                    </div>
                                </label>
                            )
                        })}
                    </div>
                </fieldset>
            </div>

            {sourceType === 'category' && (
                <div>
                    <label
                        htmlFor="product-collection-category"
                        className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                    >
                        Category
                    </label>

                    <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                        Products will be selected from this category.
                    </p>

                    <select
                        id="product-collection-category"
                        value={getPositiveInteger(source.category_id) ?? ''}
                        onChange={(event) => {
                            const categoryId = Number(event.target.value)

                            updateSource({
                                type: 'category',
                                category_id:
                                    Number.isInteger(categoryId) && categoryId > 0
                                        ? categoryId
                                        : null,
                            })
                        }}
                        className="mt-2 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:border-neutral-500 dark:focus:ring-neutral-800"
                    >
                        <option value="">Select a category</option>

                        {categoryOptions.map((category) => (
                            <option key={category.id} value={category.id}>
                                {category.name}
                            </option>
                        ))}
                    </select>
                </div>
            )}

            {sourceType === 'manual' && (
                <div className="space-y-4">
                    <div>
                        <label
                            htmlFor="product-collection-search"
                            className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                        >
                            Select products
                        </label>

                        <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                            Search by product name, SKU, or slug. You can select up to {MAX_LIMIT}{' '}
                            products.
                        </p>

                        <input
                            id="product-collection-search"
                            type="search"
                            value={search}
                            autoComplete="off"
                            placeholder="Search products..."
                            onChange={(event) => handleSearchChange(event.target.value)}
                            className="mt-2 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:border-neutral-500 dark:focus:ring-neutral-800"
                        />

                        {searching && (
                            <p
                                role="status"
                                className="mt-2 text-xs text-neutral-500 dark:text-neutral-400"
                            >
                                Searching...
                            </p>
                        )}

                        {searchError !== null && (
                            <p role="alert" className="mt-2 text-xs text-red-600 dark:text-red-400">
                                {searchError}
                            </p>
                        )}

                        {!searching && search.trim().length >= 2 && searchError === null && (
                            <div className="mt-2 overflow-hidden rounded-lg border border-neutral-200 dark:border-neutral-800">
                                {availableSearchResults.length > 0 ? (
                                    <div className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                        {availableSearchResults.map((product) => (
                                            <button
                                                key={product.id}
                                                type="button"
                                                disabled={productIds.length >= MAX_LIMIT}
                                                onClick={() => selectProduct(product)}
                                                className="flex w-full items-center justify-between gap-4 px-3 py-3 text-left transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-50 dark:hover:bg-neutral-900"
                                            >
                                                <span className="min-w-0">
                                                    <span className="block truncate text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                                        {product.name}
                                                    </span>

                                                    <span className="mt-0.5 block truncate text-xs text-neutral-500 dark:text-neutral-400">
                                                        {product.sku
                                                            ? `SKU: ${product.sku}`
                                                            : product.slug}
                                                    </span>
                                                </span>

                                                <span className="shrink-0 text-xs font-semibold text-neutral-600 dark:text-neutral-300">
                                                    Add
                                                </span>
                                            </button>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="px-3 py-4 text-sm text-neutral-500 dark:text-neutral-400">
                                        No matching products found.
                                    </p>
                                )}
                            </div>
                        )}
                    </div>

                    <div>
                        <div className="flex items-center justify-between gap-3">
                            <h3 className="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                Selected products
                            </h3>

                            <span className="text-xs text-neutral-400">
                                {productIds.length}/{MAX_LIMIT}
                            </span>
                        </div>

                        {selectedProducts.length === 0 ? (
                            <div className="mt-2 rounded-lg border border-dashed border-neutral-300 px-4 py-5 text-sm text-neutral-500 dark:border-neutral-700 dark:text-neutral-400">
                                No products selected yet.
                            </div>
                        ) : (
                            <div className="mt-2 divide-y divide-neutral-200 overflow-hidden rounded-lg border border-neutral-200 dark:divide-neutral-800 dark:border-neutral-800">
                                {selectedProducts.map(({ id, product }, index) => (
                                    <div key={id} className="flex items-center gap-3 px-3 py-3">
                                        <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-neutral-100 text-xs font-semibold text-neutral-500 dark:bg-neutral-900 dark:text-neutral-400">
                                            {index + 1}
                                        </span>

                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                                {product?.name ?? `Product #${id}`}
                                            </p>

                                            <p className="mt-0.5 truncate text-xs text-neutral-500 dark:text-neutral-400">
                                                {product?.sku
                                                    ? `SKU: ${product.sku}`
                                                    : (product?.slug ??
                                                      'Product details unavailable')}
                                            </p>
                                        </div>

                                        <button
                                            type="button"
                                            onClick={() => removeProduct(id)}
                                            className="shrink-0 rounded-md px-2 py-1 text-xs font-semibold text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/30"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            )}

            <div className="border-t border-neutral-200 pt-6 dark:border-neutral-800">
                <label
                    htmlFor="product-collection-columns"
                    className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                >
                    Columns
                </label>

                <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                    Choose how many products are displayed per row on larger screens.
                </p>

                <select
                    id="product-collection-columns"
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
                        label="Show price"
                        description="Display the product price on each product item."
                        checked={showPrice}
                        onChange={(checked) =>
                            updateConfig({
                                show_price: checked,
                            })
                        }
                    />

                    <ToggleOption
                        label="Show rating"
                        description="Display product rating information when available."
                        checked={showRating}
                        onChange={(checked) =>
                            updateConfig({
                                show_rating: checked,
                            })
                        }
                    />

                    <ToggleOption
                        label="Show badges"
                        description="Display product badges when available."
                        checked={showBadges}
                        onChange={(checked) =>
                            updateConfig({
                                show_badges: checked,
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
                        Configure automatic movement, transition behavior, and navigation controls.
                    </p>

                    <div className="mt-4 space-y-4">
                        <ToggleOption
                            label="Autoplay"
                            description="Automatically advance through products."
                            checked={autoplay}
                            onChange={(checked) =>
                                updateConfig({
                                    autoplay: checked,
                                })
                            }
                        />

                        <div>
                            <label
                                htmlFor="product-collection-carousel-effect"
                                className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                            >
                                Transition effect
                            </label>

                            <select
                                id="product-collection-carousel-effect"
                                value={effect}
                                onChange={(event) =>
                                    updateConfig({
                                        effect: event.target.value as CarouselEffect,
                                    })
                                }
                                className="mt-2 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 sm:max-w-64"
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
                                htmlFor="product-collection-autoplay-delay"
                                className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                            >
                                Autoplay delay
                            </label>

                            <input
                                id="product-collection-autoplay-delay"
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
                                className="mt-2 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm outline-none transition disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 sm:max-w-48"
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

interface ProductCollectionConfig {
    title: string
    limit: number
    source: Record<string, JsonValue>
    show_price: boolean
    show_rating: boolean
    show_badges: boolean
    columns: number
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

function getSource(value: SectionConfig[string]): Record<string, JsonValue> {
    if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
        return value
    }

    return {
        type: 'latest',
    }
}

function getSourceType(value: JsonValue | undefined): CatalogSourceType {
    if (value === 'latest' || value === 'featured' || value === 'category' || value === 'manual') {
        return value
    }

    return 'latest'
}

function getProductIds(value: JsonValue | undefined): number[] {
    if (!Array.isArray(value)) {
        return []
    }

    return value.filter(
        (item): item is number => typeof item === 'number' && Number.isInteger(item) && item > 0,
    )
}

function getPositiveInteger(value: JsonValue | undefined): number | null {
    return typeof value === 'number' && Number.isInteger(value) && value > 0 ? value : null
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
