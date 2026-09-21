import type { StorefrontFilterOptions, StorefrontProductFilters } from '@/types/storefront'
import { X } from 'lucide-react'

interface StorefrontActiveFiltersProps {
    filters: StorefrontProductFilters
    options: StorefrontFilterOptions
    onBrandClear: () => void
    onCategoryClear: () => void
    onPriceClear: () => void
    onAttributeClear: (attributeSlug: string) => void
    onClearAll: () => void
}

interface ActiveFilter {
    key: string
    label: string
    onClear: () => void
}

export default function StorefrontActiveFilters({
    filters,
    options,
    onBrandClear,
    onCategoryClear,
    onPriceClear,
    onAttributeClear,
    onClearAll,
}: StorefrontActiveFiltersProps) {
    const activeFilters: ActiveFilter[] = []

    if (filters.brand) {
        const brand = options.brands.find((item) => item.slug === filters.brand)

        activeFilters.push({
            key: 'brand',
            label: `Brand: ${brand?.name ?? filters.brand}`,
            onClear: onBrandClear,
        })
    }

    if (filters.category) {
        const category = options.categories.find((item) => item.slug === filters.category)

        activeFilters.push({
            key: 'category',
            label: `Category: ${category?.name ?? filters.category}`,
            onClear: onCategoryClear,
        })
    }

    if (filters.min_price !== null || filters.max_price !== null) {
        let label = 'Price'

        if (filters.min_price !== null && filters.max_price !== null) {
            label = `Price: ${filters.min_price} – ${filters.max_price}`
        } else if (filters.min_price !== null) {
            label = `Price: from ${filters.min_price}`
        } else if (filters.max_price !== null) {
            label = `Price: up to ${filters.max_price}`
        }

        activeFilters.push({
            key: 'price',
            label,
            onClear: onPriceClear,
        })
    }

    Object.entries(filters.attributes).forEach(([attributeSlug, valueSlug]) => {
        if (!valueSlug) {
            return
        }

        const attribute = options.attributes.find((item) => item.slug === attributeSlug)
        const value = attribute?.values.find((item) => item.slug === valueSlug)

        activeFilters.push({
            key: `attribute-${attributeSlug}`,
            label: `${attribute?.name ?? attributeSlug}: ${value?.name ?? valueSlug}`,
            onClear: () => onAttributeClear(attributeSlug),
        })
    })

    if (activeFilters.length === 0) {
        return null
    }

    return (
        <div
            className="flex min-w-0 flex-wrap items-center gap-2"
            aria-label="Active product filters"
        >
            {activeFilters.map((filter) => (
                <button
                    key={filter.key}
                    type="button"
                    onClick={filter.onClear}
                    className="inline-flex min-h-11 max-w-full min-w-0 items-center gap-2 rounded-full border border-neutral-300 bg-white px-3 py-1.5 text-left text-sm text-neutral-700 transition hover:border-neutral-400 hover:bg-neutral-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2"
                    aria-label={`Remove ${filter.label} filter`}
                >
                    <span className="min-w-0 break-words">{filter.label}</span>

                    <X className="size-4 shrink-0" aria-hidden="true" />
                </button>
            ))}

            {activeFilters.length > 1 && (
                <button
                    type="button"
                    onClick={onClearAll}
                    className="inline-flex min-h-11 shrink-0 items-center rounded-md px-2 text-sm font-medium text-neutral-600 underline-offset-4 transition hover:text-neutral-950 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2"
                >
                    Clear all
                </button>
            )}
        </div>
    )
}
