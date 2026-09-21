import { isStorefrontProductSort, type StorefrontProductSort } from '@/types/storefront'

interface StorefrontSortSelectProps {
    value: StorefrontProductSort
    onChange: (value: StorefrontProductSort) => void
}

export default function StorefrontSortSelect({ value, onChange }: StorefrontSortSelectProps) {
    return (
        <label className="flex min-w-0 items-center gap-2">
            <span className="shrink-0 text-sm font-medium text-neutral-700">Sort by</span>

            <select
                value={value}
                onChange={(event) => {
                    const value = event.target.value

                    if (isStorefrontProductSort(value)) {
                        onChange(value)
                    }
                }}
                className="min-h-11 min-w-0 flex-1 rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 transition focus-visible:border-neutral-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2 sm:min-w-44 sm:flex-none"
            >
                <option value="newest">Newest</option>
                <option value="price_asc">Price: Low to High</option>
                <option value="price_desc">Price: High to Low</option>
                <option value="name_asc">Name: A to Z</option>
                <option value="name_desc">Name: Z to A</option>
            </select>
        </label>
    )
}
