import { ArrowDown, ArrowUp, Check, Loader2, Search, X } from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'
import type { ReactNode } from 'react'

import type { BrandSourceConfig, PageBuilderBrandOption } from './brand-editor-types'

const SEARCH_DELAY = 250
const MAX_BRANDS = 24

interface Props {
    pageId: number
    source: BrandSourceConfig

    onChange: (source: BrandSourceConfig) => void
}

export default function BrandSourceField({ pageId, source, onChange }: Props) {
    const manual = source.type === 'manual'

    const brandIdsKey = manual ? source.brand_ids.join(',') : ''

    const brandIds = useMemo(() => {
        if (brandIdsKey === '') {
            return []
        }

        return brandIdsKey.split(',').map(Number)
    }, [brandIdsKey])

    const [search, setSearch] = useState('')

    const [searchResults, setSearchResults] = useState<PageBuilderBrandOption[]>([])

    const [selectedOptions, setSelectedOptions] = useState<PageBuilderBrandOption[]>([])

    const [searching, setSearching] = useState(false)

    const [loadingSelected, setLoadingSelected] = useState(false)

    const [searchError, setSearchError] = useState<string | null>(null)

    useEffect(() => {
        if (!manual || brandIds.length === 0) {
            return
        }

        const controller = new AbortController()

        const loadSelected = async () => {
            setLoadingSelected(true)

            try {
                const url = new URL(
                    route('admin.pages.builder.brands', pageId),
                    window.location.origin,
                )

                url.searchParams.set('ids', brandIds.join(','))

                const response = await fetch(url.toString(), {
                    method: 'GET',

                    headers: {
                        Accept: 'application/json',

                        'X-Requested-With': 'XMLHttpRequest',
                    },

                    credentials: 'same-origin',

                    signal: controller.signal,
                })

                if (!response.ok) {
                    throw new Error('Unable to load selected brands.')
                }

                const payload = (await response.json()) as {
                    brands?: PageBuilderBrandOption[]
                }

                if (!controller.signal.aborted) {
                    setSelectedOptions(Array.isArray(payload.brands) ? payload.brands : [])
                }
            } catch (error) {
                if (controller.signal.aborted) {
                    return
                }

                setSelectedOptions([])

                setSearchError(
                    error instanceof Error ? error.message : 'Unable to load selected brands.',
                )
            } finally {
                if (!controller.signal.aborted) {
                    setLoadingSelected(false)
                }
            }
        }

        void loadSelected()

        return () => {
            controller.abort()
        }
    }, [brandIds, manual, pageId])

    useEffect(() => {
        if (!manual) {
            return
        }

        const controller = new AbortController()

        const timer = window.setTimeout(async () => {
            setSearching(true)

            setSearchError(null)

            try {
                const url = new URL(
                    route('admin.pages.builder.brands', pageId),
                    window.location.origin,
                )

                const term = search.trim()

                if (term !== '') {
                    url.searchParams.set('search', term)
                }

                const response = await fetch(url.toString(), {
                    method: 'GET',

                    headers: {
                        Accept: 'application/json',

                        'X-Requested-With': 'XMLHttpRequest',
                    },

                    credentials: 'same-origin',

                    signal: controller.signal,
                })

                if (!response.ok) {
                    throw new Error('Brand search failed.')
                }

                const payload = (await response.json()) as {
                    brands?: PageBuilderBrandOption[]
                }

                if (!controller.signal.aborted) {
                    setSearchResults(Array.isArray(payload.brands) ? payload.brands : [])
                }
            } catch (error) {
                if (controller.signal.aborted) {
                    return
                }

                setSearchResults([])

                setSearchError(error instanceof Error ? error.message : 'Brand search failed.')
            } finally {
                if (!controller.signal.aborted) {
                    setSearching(false)
                }
            }
        }, SEARCH_DELAY)

        return () => {
            window.clearTimeout(timer)

            controller.abort()
        }
    }, [manual, pageId, search])

    const optionMap = useMemo(() => {
        const map = new Map<number, PageBuilderBrandOption>()

        for (const option of selectedOptions) {
            map.set(option.id, option)
        }

        for (const option of searchResults) {
            map.set(option.id, option)
        }

        return map
    }, [searchResults, selectedOptions])

    const selectedIdSet = useMemo(() => new Set(brandIds), [brandIds])

    const availableResults = searchResults.filter((brand) => !selectedIdSet.has(brand.id))

    const selectAll = () => {
        setSearch('')

        setSearchResults([])

        setSelectedOptions([])

        setSearchError(null)

        setSearching(false)

        setLoadingSelected(false)

        onChange({
            type: 'all',
        })
    }

    const selectManual = () => {
        setSearch('')

        setSearchResults([])

        setSearchError(null)

        setSearching(false)

        onChange({
            type: 'manual',
            brand_ids: brandIds,
        })
    }

    const addBrand = (brand: PageBuilderBrandOption) => {
        if (!manual || brandIds.includes(brand.id) || brandIds.length >= MAX_BRANDS) {
            return
        }

        onChange({
            type: 'manual',

            brand_ids: [...brandIds, brand.id],
        })

        setSelectedOptions((current) => {
            if (current.some((item) => item.id === brand.id)) {
                return current
            }

            return [...current, brand]
        })
    }

    const removeBrand = (brandId: number) => {
        if (!manual) {
            return
        }

        const nextIds = brandIds.filter((id) => id !== brandId)

        setSelectedOptions((current) => current.filter((brand) => brand.id !== brandId))

        if (nextIds.length === 0) {
            setLoadingSelected(false)
        }

        onChange({
            type: 'manual',

            brand_ids: nextIds,
        })
    }

    const moveBrand = (index: number, direction: 'up' | 'down') => {
        if (!manual) {
            return
        }

        const target = direction === 'up' ? index - 1 : index + 1

        if (target < 0 || target >= brandIds.length) {
            return
        }

        const next = [...brandIds]

        ;[next[index], next[target]] = [next[target], next[index]]

        onChange({
            type: 'manual',
            brand_ids: next,
        })
    }

    return (
        <section className="rounded-xl border border-neutral-200 p-4 dark:border-neutral-800 sm:p-5">
            <div>
                <h3 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                    Brand source
                </h3>

                <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                    Use all active catalog brands, or choose and order specific brands manually.
                </p>
            </div>

            <fieldset className="mt-5">
                <legend className="sr-only">Brand source</legend>

                <div className="grid gap-3 sm:grid-cols-2">
                    <SourceOption
                        title="All active brands"
                        description="Automatically use active brands from the catalog."
                        checked={!manual}
                        onChange={selectAll}
                    />

                    <SourceOption
                        title="Manual selection"
                        description="Choose specific brands and control their display order."
                        checked={manual}
                        onChange={selectManual}
                    />
                </div>
            </fieldset>

            {manual && (
                <div className="mt-6 space-y-6">
                    <div>
                        <div className="flex items-center justify-between gap-3">
                            <h4 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                Selected brands
                            </h4>

                            <span className="text-xs text-neutral-500 dark:text-neutral-400">
                                {brandIds.length}/{MAX_BRANDS}
                            </span>
                        </div>

                        {brandIds.length === 0 ? (
                            <div className="mt-3 rounded-lg border border-dashed border-neutral-300 p-4 text-sm text-neutral-500 dark:border-neutral-700 dark:text-neutral-400">
                                Select at least one brand before saving.
                            </div>
                        ) : (
                            <div className="mt-3 space-y-2">
                                {brandIds.map((brandId, index) => (
                                    <SelectedBrandRow
                                        key={brandId}
                                        brand={optionMap.get(brandId) ?? null}
                                        brandId={brandId}
                                        index={index}
                                        total={brandIds.length}
                                        loading={loadingSelected}
                                        onMove={moveBrand}
                                        onRemove={removeBrand}
                                    />
                                ))}
                            </div>
                        )}
                    </div>

                    <div>
                        <label
                            htmlFor="brand-source-search"
                            className="block text-sm font-semibold text-neutral-900 dark:text-neutral-100"
                        >
                            Add brands
                        </label>

                        <div className="relative mt-2">
                            <Search
                                aria-hidden="true"
                                className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400"
                            />

                            <input
                                id="brand-source-search"
                                type="search"
                                value={search}
                                onChange={(event) => {
                                    setSearch(event.target.value)

                                    setSearchResults([])

                                    setSearchError(null)
                                }}
                                placeholder="Search brands..."
                                className="w-full rounded-lg border border-neutral-300 bg-white py-2 pl-9 pr-10 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800"
                            />

                            {searching && (
                                <Loader2
                                    aria-label="Searching brands"
                                    className="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin text-neutral-400"
                                />
                            )}
                        </div>

                        <p className="mt-1.5 text-xs text-neutral-500 dark:text-neutral-400">
                            The first active brands are shown when the search box is empty.
                        </p>

                        {searchError && (
                            <p role="alert" className="mt-3 text-sm text-red-600 dark:text-red-400">
                                {searchError}
                            </p>
                        )}

                        {!searching && availableResults.length > 0 && (
                            <div className="mt-3 max-h-72 space-y-2 overflow-y-auto rounded-xl border border-neutral-200 p-2 dark:border-neutral-800">
                                {availableResults.map((brand) => (
                                    <BrandSearchResult
                                        key={brand.id}
                                        brand={brand}
                                        disabled={brandIds.length >= MAX_BRANDS}
                                        onAdd={() => addBrand(brand)}
                                    />
                                ))}
                            </div>
                        )}

                        {!searching && searchError === null && availableResults.length === 0 && (
                            <p className="mt-3 text-sm text-neutral-500 dark:text-neutral-400">
                                No additional active brands found.
                            </p>
                        )}
                    </div>
                </div>
            )}
        </section>
    )
}

interface SourceOptionProps {
    title: string
    description: string
    checked: boolean
    onChange: () => void
}

function SourceOption({ title, description, checked, onChange }: SourceOptionProps) {
    return (
        <label
            className={[
                'flex cursor-pointer gap-3 rounded-xl border p-4 transition',
                checked
                    ? 'border-indigo-400 bg-indigo-50 dark:border-indigo-500 dark:bg-indigo-950/30'
                    : 'border-neutral-200 hover:border-neutral-300 dark:border-neutral-800 dark:hover:border-neutral-700',
            ].join(' ')}
        >
            <input
                type="radio"
                name="brand-source"
                checked={checked}
                onChange={onChange}
                className="mt-0.5 h-4 w-4 shrink-0"
            />

            <span>
                <span className="block text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                    {title}
                </span>

                <span className="mt-1 block text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                    {description}
                </span>
            </span>
        </label>
    )
}

interface SelectedBrandRowProps {
    brand: PageBuilderBrandOption | null

    brandId: number
    index: number
    total: number
    loading: boolean

    onMove: (index: number, direction: 'up' | 'down') => void

    onRemove: (brandId: number) => void
}

function SelectedBrandRow({
    brand,
    brandId,
    index,
    total,
    loading,
    onMove,
    onRemove,
}: SelectedBrandRowProps) {
    return (
        <div className="flex min-w-0 items-center gap-3 rounded-xl border border-neutral-200 bg-white p-3 dark:border-neutral-800 dark:bg-neutral-950">
            <BrandLogo brand={brand} />

            <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                    {brand?.name ?? (loading ? 'Loading…' : `Brand #${brandId}`)}
                </p>

                {brand && !brand.is_active && (
                    <p className="mt-0.5 text-xs text-amber-600 dark:text-amber-400">
                        This brand is currently inactive.
                    </p>
                )}
            </div>

            <div className="flex shrink-0 items-center gap-1">
                <IconButton
                    label="Move brand up"
                    disabled={index === 0}
                    onClick={() => onMove(index, 'up')}
                >
                    <ArrowUp className="h-4 w-4" />
                </IconButton>

                <IconButton
                    label="Move brand down"
                    disabled={index === total - 1}
                    onClick={() => onMove(index, 'down')}
                >
                    <ArrowDown className="h-4 w-4" />
                </IconButton>

                <IconButton label="Remove brand" onClick={() => onRemove(brandId)}>
                    <X className="h-4 w-4" />
                </IconButton>
            </div>
        </div>
    )
}

interface BrandSearchResultProps {
    brand: PageBuilderBrandOption
    disabled: boolean
    onAdd: () => void
}

function BrandSearchResult({ brand, disabled, onAdd }: BrandSearchResultProps) {
    return (
        <button
            type="button"
            disabled={disabled}
            onClick={onAdd}
            className="flex w-full min-w-0 items-center gap-3 rounded-lg p-2 text-left transition hover:bg-neutral-100 disabled:cursor-not-allowed disabled:opacity-50 dark:hover:bg-neutral-900"
        >
            <BrandLogo brand={brand} />

            <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-medium text-neutral-900 dark:text-neutral-100">
                    {brand.name}
                </p>

                <p className="truncate text-xs text-neutral-500 dark:text-neutral-400">
                    {brand.slug}
                </p>
            </div>

            <Check aria-hidden="true" className="h-4 w-4 shrink-0 text-indigo-600" />
        </button>
    )
}

function BrandLogo({ brand }: { brand: PageBuilderBrandOption | null }) {
    if (brand?.logo_url) {
        return (
            <div className="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-neutral-200 bg-white p-1.5 dark:border-neutral-700">
                <img
                    src={brand.logo_url}
                    alt=""
                    loading="lazy"
                    decoding="async"
                    className="max-h-full max-w-full object-contain"
                />
            </div>
        )
    }

    return (
        <div
            aria-hidden="true"
            className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-neutral-100 text-xs font-bold text-neutral-500 dark:bg-neutral-800 dark:text-neutral-300"
        >
            {brand?.name.trim().charAt(0).toUpperCase() ?? '?'}
        </div>
    )
}

interface IconButtonProps {
    label: string
    disabled?: boolean
    onClick: () => void
    children: ReactNode
}

function IconButton({ label, disabled = false, onClick, children }: IconButtonProps) {
    return (
        <button
            type="button"
            aria-label={label}
            disabled={disabled}
            onClick={onClick}
            className="inline-flex h-9 w-9 items-center justify-center rounded-lg text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-500 disabled:cursor-not-allowed disabled:opacity-30 dark:hover:bg-neutral-800 dark:hover:text-neutral-100"
        >
            {children}
        </button>
    )
}
