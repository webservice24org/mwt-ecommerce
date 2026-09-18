import type { StorefrontProductSort } from '@/types/storefront'

interface StorefrontSortSelectProps {
    value: StorefrontProductSort
    onChange: (value: StorefrontProductSort) => void
}

export default function StorefrontSortSelect({ value, onChange }: StorefrontSortSelectProps) {
    return (
        <label className="flex items-center gap-2">
            <span className="shrink-0 text-sm font-medium text-neutral-700">Sort by</span>

            <select
                value={value}
                onChange={(event) => onChange(event.target.value as StorefrontProductSort)}
                className="h-10 min-w-44 rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200"
                aria-label="Sort products"
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
