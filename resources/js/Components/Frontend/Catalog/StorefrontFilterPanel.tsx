import type { StorefrontFilterOptions, StorefrontProductFilters } from '@/types/storefront'
import type { ReactNode } from 'react'

interface StorefrontFilterPanelProps {
    filters: StorefrontProductFilters
    options: StorefrontFilterOptions
    showCategories?: boolean
    onBrandChange: (slug: string | null) => void
    onCategoryChange: (slug: string | null) => void
    onPriceChange: (minPrice: number | null, maxPrice: number | null) => void
    onAttributeChange: (attributeSlug: string, valueSlug: string | null) => void
}

const selectClassName =
    'min-h-11 w-full min-w-0 rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 transition focus-visible:border-neutral-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2'

export default function StorefrontFilterPanel({
    filters,
    options,
    showCategories = true,
    onBrandChange,
    onCategoryChange,
    onPriceChange,
    onAttributeChange,
}: StorefrontFilterPanelProps) {
    return (
        <div className="min-w-0 space-y-7">
            {options.brands.length > 0 && (
                <FilterSection title="Brand">
                    <select
                        value={filters.brand ?? ''}
                        onChange={(event) => onBrandChange(event.target.value || null)}
                        className={selectClassName}
                        aria-label="Filter by brand"
                    >
                        <option value="">All brands</option>

                        {options.brands.map((brand) => (
                            <option key={brand.id} value={brand.slug}>
                                {brand.name}
                            </option>
                        ))}
                    </select>
                </FilterSection>
            )}

            {showCategories && options.categories.length > 0 && (
                <FilterSection title="Category">
                    <select
                        value={filters.category ?? ''}
                        onChange={(event) => onCategoryChange(event.target.value || null)}
                        className={selectClassName}
                        aria-label="Filter by category"
                    >
                        <option value="">All categories</option>

                        {options.categories.map((category) => (
                            <option key={category.id} value={category.slug}>
                                {category.name}
                            </option>
                        ))}
                    </select>
                </FilterSection>
            )}

            {(options.min_price !== null || options.max_price !== null) && (
                <fieldset className="min-w-0">
                    <legend className="mb-3 break-words text-sm font-semibold text-neutral-900">
                        Price
                    </legend>

                    <PriceFilter
                        minPrice={filters.min_price}
                        maxPrice={filters.max_price}
                        availableMin={options.min_price}
                        availableMax={options.max_price}
                        onChange={onPriceChange}
                    />
                </fieldset>
            )}

            {options.attributes.map((attribute) => (
                <fieldset key={attribute.id} className="min-w-0">
                    <legend className="mb-3 max-w-full break-words text-sm font-semibold text-neutral-900">
                        {attribute.name}
                    </legend>

                    <div className="min-w-0 space-y-1">
                        <label className="flex min-h-11 min-w-0 cursor-pointer items-center gap-3 rounded-md px-2 text-sm text-neutral-800 transition hover:bg-neutral-50 focus-within:bg-neutral-50 focus-within:outline-none focus-within:ring-2 focus-within:ring-neutral-900 focus-within:ring-offset-2">
                            <input
                                type="radio"
                                name={`attribute-${attribute.slug}`}
                                checked={!filters.attributes[attribute.slug]}
                                onChange={() => onAttributeChange(attribute.slug, null)}
                                className="size-4 shrink-0 accent-neutral-950 focus-visible:outline-none"
                            />

                            <span className="min-w-0 break-words">All</span>
                        </label>

                        {attribute.values.map((value) => (
                            <label
                                key={value.id}
                                className="flex min-h-11 min-w-0 cursor-pointer items-center gap-3 rounded-md px-2 text-sm text-neutral-800 transition hover:bg-neutral-50 focus-within:bg-neutral-50 focus-within:outline-none focus-within:ring-2 focus-within:ring-neutral-900 focus-within:ring-offset-2"
                            >
                                <input
                                    type="radio"
                                    name={`attribute-${attribute.slug}`}
                                    value={value.slug}
                                    checked={filters.attributes[attribute.slug] === value.slug}
                                    onChange={() => onAttributeChange(attribute.slug, value.slug)}
                                    className="size-4 shrink-0 accent-neutral-950 focus-visible:outline-none"
                                />

                                <span className="min-w-0 break-words">{value.name}</span>
                            </label>
                        ))}
                    </div>
                </fieldset>
            ))}
        </div>
    )
}

interface FilterSectionProps {
    title: string
    children: ReactNode
}

function FilterSection({ title, children }: FilterSectionProps) {
    return (
        <section className="min-w-0">
            <h3 className="mb-3 break-words text-sm font-semibold text-neutral-900">{title}</h3>

            {children}
        </section>
    )
}

interface PriceFilterProps {
    minPrice: number | null
    maxPrice: number | null
    availableMin: number | null
    availableMax: number | null
    onChange: (minPrice: number | null, maxPrice: number | null) => void
}

function PriceFilter({
    minPrice,
    maxPrice,
    availableMin,
    availableMax,
    onChange,
}: PriceFilterProps) {
    return (
        <div className="grid min-w-0 grid-cols-1 gap-2 min-[340px]:grid-cols-2">
            <label className="min-w-0">
                <span className="sr-only">Minimum price</span>

                <input
                    type="number"
                    min={0}
                    inputMode="numeric"
                    value={minPrice ?? ''}
                    placeholder={availableMin !== null ? String(availableMin) : 'Min'}
                    onChange={(event) => {
                        const value = event.target.value

                        onChange(value === '' ? null : Number(value), maxPrice)
                    }}
                    className="min-h-11 w-full min-w-0 rounded-lg border border-neutral-300 px-3 py-2 text-sm transition focus-visible:border-neutral-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2"
                />
            </label>

            <label className="min-w-0">
                <span className="sr-only">Maximum price</span>

                <input
                    type="number"
                    min={0}
                    inputMode="numeric"
                    value={maxPrice ?? ''}
                    placeholder={availableMax !== null ? String(availableMax) : 'Max'}
                    onChange={(event) => {
                        const value = event.target.value

                        onChange(minPrice, value === '' ? null : Number(value))
                    }}
                    className="min-h-11 w-full min-w-0 rounded-lg border border-neutral-300 px-3 py-2 text-sm transition focus-visible:border-neutral-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2"
                />
            </label>
        </div>
    )
}
