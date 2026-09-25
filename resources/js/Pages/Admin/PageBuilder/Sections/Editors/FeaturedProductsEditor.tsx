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
const PRODUCT_SEARCH_DELAY = 300

export default function FeaturedProductsEditor({
    pageId,
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

    /*
     * The edit dialog is keyed by section ID, so this editor gets a fresh
     * session whenever another section is opened.
     *
     * Keeping the title local prevents input focus/caret issues while the
     * parent config is updated.
     */
    const [title, setTitle] = useState(() => parentTitle)

    const [search, setSearch] = useState('')
    const [searchResults, setSearchResults] = useState<CatalogProductOption[]>([])
    const [searching, setSearching] = useState(false)
    const [searchError, setSearchError] = useState<string | null>(null)

    const productIds = getProductIds(source.product_ids)

    /*
     * At most 24 configured products and 20 search results are involved,
     * so deriving this directly is simpler than manual memoization.
     */
    const productsById = new Map(selectedProductOptions.map((product) => [product.id, product]))

    for (const product of searchResults) {
        productsById.set(product.id, product)
    }

    const selectedProducts = productIds.map((id) => ({
        id,
        product: productsById.get(id) ?? null,
    }))

    /*
     * This effect only synchronizes with the external product-search
     * endpoint. Local state resets happen in event handlers instead.
     */
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

    const updateConfig = (
        key: keyof Pick<FeaturedProductsConfig, 'title' | 'limit'>,
        nextValue: string | number,
    ) => {
        onChange({
            ...value,
            [key]: nextValue,
        })
    }

    const updateSource = (nextSource: Record<string, JsonValue>) => {
        onChange({
            ...value,
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
            case 'featured':
                updateSource({
                    type: 'featured',
                })
                break

            case 'manual':
                updateSource({
                    type: 'manual',
                    product_ids: [],
                })
                break

            case 'category':
                updateSource({
                    type: 'category',
                    category_id: null,
                })
                break
        }
    }

    const handleTitleChange = (nextTitle: string) => {
        setTitle(nextTitle)
        updateConfig('title', nextTitle)
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

        /*
         * Preserve the resolved product locally so it can immediately be
         * rendered in the selected-products list.
         */
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
                    htmlFor="featured-products-title"
                    className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                >
                    Section title
                </label>

                <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                    The heading displayed above the featured products.
                </p>

                <input
                    id="featured-products-title"
                    type="text"
                    value={title}
                    maxLength={120}
                    required
                    autoComplete="on"
                    onChange={(event) => handleTitleChange(event.target.value)}
                    className="mt-2 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:border-neutral-500 dark:focus:ring-neutral-800"
                />

                <div className="mt-1 flex items-center justify-between gap-3 text-xs text-neutral-400">
                    <span>Maximum 120 characters.</span>

                    <span>{title.length}/120</span>
                </div>
            </div>

            <div>
                <label
                    htmlFor="featured-products-limit"
                    className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                >
                    Product limit
                </label>

                <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                    Choose the maximum number of products this section can display.
                </p>

                <input
                    id="featured-products-limit"
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

                        updateConfig('limit', nextLimit)
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
                        Choose how products are selected for this section.
                    </p>

                    <div className="mt-3 grid gap-3 sm:grid-cols-3">
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
                                            name="featured-products-source"
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
                        htmlFor="featured-products-category"
                        className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                    >
                        Category
                    </label>

                    <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                        Products will be selected from this category.
                    </p>

                    <select
                        id="featured-products-category"
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
                            htmlFor="featured-products-search"
                            className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                        >
                            Select products
                        </label>

                        <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                            Search by product name, SKU, or slug. You can select up to {MAX_LIMIT}{' '}
                            products.
                        </p>

                        <input
                            id="featured-products-search"
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
        </div>
    )
}

interface FeaturedProductsConfig {
    title: string
    limit: number
}

function getString(value: SectionConfig[string]): string {
    return typeof value === 'string' ? value : ''
}

function getNumber(value: SectionConfig[string], fallback: number): number {
    return typeof value === 'number' && Number.isFinite(value) ? value : fallback
}

function getSource(value: SectionConfig[string]): Record<string, JsonValue> {
    if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
        return value
    }

    /*
     * Backward compatibility for Featured Products sections created
     * before source configuration was introduced.
     */
    return {
        type: 'featured',
    }
}

function getSourceType(value: JsonValue | undefined): CatalogSourceType {
    if (value === 'featured' || value === 'manual' || value === 'category') {
        return value
    }

    return 'featured'
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
