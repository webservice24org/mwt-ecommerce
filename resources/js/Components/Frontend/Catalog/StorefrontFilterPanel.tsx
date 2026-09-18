import type { StorefrontFilterOptions, StorefrontProductFilters } from '@/types/storefront'

interface StorefrontFilterPanelProps {
    filters: StorefrontProductFilters
    options: StorefrontFilterOptions
    showCategories?: boolean
    onBrandChange: (slug: string | null) => void
    onCategoryChange: (slug: string | null) => void
    onPriceChange: (minPrice: number | null, maxPrice: number | null) => void
    onAttributeChange: (attributeSlug: string, valueSlug: string | null) => void
}

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
        <div className="space-y-7">
            {options.brands.length > 0 && (
                <FilterSection title="Brand">
                    <select
                        value={filters.brand ?? ''}
                        onChange={(event) => onBrandChange(event.target.value || null)}
                        className="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm outline-none focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200"
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
                        className="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm outline-none focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200"
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
                <FilterSection title="Price">
                    <PriceFilter
                        minPrice={filters.min_price}
                        maxPrice={filters.max_price}
                        availableMin={options.min_price}
                        availableMax={options.max_price}
                        onChange={onPriceChange}
                    />
                </FilterSection>
            )}

            {options.attributes.map((attribute) => (
                <FilterSection key={attribute.id} title={attribute.name}>
                    <div className="space-y-2">
                        <label className="flex cursor-pointer items-center gap-2 text-sm">
                            <input
                                type="radio"
                                name={`attribute-${attribute.slug}`}
                                checked={!filters.attributes[attribute.slug]}
                                onChange={() => onAttributeChange(attribute.slug, null)}
                            />

                            <span>All</span>
                        </label>

                        {attribute.values.map((value) => (
                            <label
                                key={value.id}
                                className="flex cursor-pointer items-center gap-2 text-sm"
                            >
                                <input
                                    type="radio"
                                    name={`attribute-${attribute.slug}`}
                                    value={value.slug}
                                    checked={filters.attributes[attribute.slug] === value.slug}
                                    onChange={() => onAttributeChange(attribute.slug, value.slug)}
                                />

                                <span>{value.name}</span>
                            </label>
                        ))}
                    </div>
                </FilterSection>
            ))}
        </div>
    )
}

interface FilterSectionProps {
    title: string
    children: React.ReactNode
}

function FilterSection({ title, children }: FilterSectionProps) {
    return (
        <section>
            <h3 className="mb-3 text-sm font-semibold text-neutral-900">{title}</h3>

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
        <div className="grid grid-cols-2 gap-2">
            <label>
                <span className="sr-only">Minimum price</span>

                <input
                    type="number"
                    min={0}
                    value={minPrice ?? ''}
                    placeholder={availableMin !== null ? String(availableMin) : 'Min'}
                    onChange={(event) => {
                        const value = event.target.value

                        onChange(value === '' ? null : Number(value), maxPrice)
                    }}
                    className="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200"
                />
            </label>

            <label>
                <span className="sr-only">Maximum price</span>

                <input
                    type="number"
                    min={0}
                    value={maxPrice ?? ''}
                    placeholder={availableMax !== null ? String(availableMax) : 'Max'}
                    onChange={(event) => {
                        const value = event.target.value

                        onChange(minPrice, value === '' ? null : Number(value))
                    }}
                    className="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200"
                />
            </label>
        </div>
    )
}
