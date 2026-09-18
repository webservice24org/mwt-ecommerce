import type { StorefrontFilterOptions, StorefrontProductFilters } from '@/types/storefront'
import StorefrontActiveFilters from './StorefrontActiveFilters'
import StorefrontMobileFilters from './StorefrontMobileFilters'
import StorefrontSortSelect from './StorefrontSortSelect'

interface StorefrontListingControlsProps {
    filters: StorefrontProductFilters
    options: StorefrontFilterOptions
    showCategories?: boolean
    onSortChange: (sort: StorefrontProductFilters['sort']) => void
    onBrandChange: (slug: string | null) => void
    onCategoryChange: (slug: string | null) => void
    onPriceChange: (minPrice: number | null, maxPrice: number | null) => void
    onAttributeChange: (attributeSlug: string, valueSlug: string | null) => void
    onClearAll: () => void
}

export default function StorefrontListingControls({
    filters,
    options,
    showCategories = true,
    onSortChange,
    onBrandChange,
    onCategoryChange,
    onPriceChange,
    onAttributeChange,
    onClearAll,
}: StorefrontListingControlsProps) {
    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-3 border-y border-neutral-200 py-3">
                <StorefrontMobileFilters
                    filters={filters}
                    options={options}
                    showCategories={showCategories}
                    onBrandChange={onBrandChange}
                    onCategoryChange={onCategoryChange}
                    onPriceChange={onPriceChange}
                    onAttributeChange={onAttributeChange}
                />

                <div className="ml-auto">
                    <StorefrontSortSelect value={filters.sort} onChange={onSortChange} />
                </div>
            </div>

            <StorefrontActiveFilters
                filters={filters}
                options={options}
                onBrandClear={() => onBrandChange(null)}
                onCategoryClear={() => onCategoryChange(null)}
                onPriceClear={() => onPriceChange(null, null)}
                onAttributeClear={(attributeSlug) => onAttributeChange(attributeSlug, null)}
                onClearAll={onClearAll}
            />
        </div>
    )
}
