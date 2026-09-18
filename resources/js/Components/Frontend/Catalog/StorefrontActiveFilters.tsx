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

export default function StorefrontActiveFilters({
    filters,
    options,
    onBrandClear,
    onCategoryClear,
    onPriceClear,
    onAttributeClear,
    onClearAll,
}: StorefrontActiveFiltersProps) {
    const chips: React.ReactNode[] = []

    if (filters.brand) {
        const brand = options.brands.find((item) => item.slug === filters.brand)

        chips.push(
            <FilterChip
                key="brand"
                label={`Brand: ${brand?.name ?? filters.brand}`}
                onRemove={onBrandClear}
            />,
        )
    }

    if (filters.category) {
        const category = options.categories.find((item) => item.slug === filters.category)

        chips.push(
            <FilterChip
                key="category"
                label={`Category: ${category?.name ?? filters.category}`}
                onRemove={onCategoryClear}
            />,
        )
    }

    if (filters.min_price !== null || filters.max_price !== null) {
        const label =
            filters.min_price !== null && filters.max_price !== null
                ? `Price: ${filters.min_price} – ${filters.max_price}`
                : filters.min_price !== null
                  ? `Price from ${filters.min_price}`
                  : `Price up to ${filters.max_price}`

        chips.push(<FilterChip key="price" label={label} onRemove={onPriceClear} />)
    }

    Object.entries(filters.attributes).forEach(([attributeSlug, valueSlug]) => {
        const attribute = options.attributes.find((item) => item.slug === attributeSlug)

        const value = attribute?.values.find((item) => item.slug === valueSlug)

        chips.push(
            <FilterChip
                key={`attribute-${attributeSlug}`}
                label={`${attribute?.name ?? attributeSlug}: ${value?.name ?? valueSlug}`}
                onRemove={() => onAttributeClear(attributeSlug)}
            />,
        )
    })

    if (chips.length === 0) {
        return null
    }

    return (
        <div className="flex flex-wrap items-center gap-2">
            {chips}

            <button
                type="button"
                onClick={onClearAll}
                className="px-2 py-1 text-sm font-medium text-neutral-600 underline-offset-4 hover:text-neutral-950 hover:underline"
            >
                Clear all
            </button>
        </div>
    )
}

interface FilterChipProps {
    label: string
    onRemove: () => void
}

function FilterChip({ label, onRemove }: FilterChipProps) {
    return (
        <button
            type="button"
            onClick={onRemove}
            className="inline-flex items-center gap-1 rounded-full border border-neutral-300 bg-white px-3 py-1.5 text-xs font-medium text-neutral-700 transition hover:border-neutral-400 hover:text-neutral-950"
            aria-label={`Remove ${label} filter`}
        >
            {label}

            <X className="h-3.5 w-3.5" aria-hidden="true" />
        </button>
    )
}
