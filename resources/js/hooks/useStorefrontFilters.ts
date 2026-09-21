import type { StorefrontProductFilters, StorefrontProductSort } from '@/types/storefront'
import { router } from '@inertiajs/react'

interface UseStorefrontFiltersOptions {
    url: string
    filters: StorefrontProductFilters
}

type StorefrontFilterQueryData = Partial<{
    sort: StorefrontProductSort
    brand: string
    category: string
    min_price: number
    max_price: number
    attributes: Record<string, string>
}>

export function useStorefrontFilters({ url, filters }: UseStorefrontFiltersOptions) {
    const visit = (next: StorefrontProductFilters) => {
        const data: StorefrontFilterQueryData = {}

        if (next.sort !== 'newest') {
            data.sort = next.sort
        }

        if (next.brand) {
            data.brand = next.brand
        }

        if (next.category) {
            data.category = next.category
        }

        if (next.min_price !== null) {
            data.min_price = next.min_price
        }

        if (next.max_price !== null) {
            data.max_price = next.max_price
        }

        if (Object.keys(next.attributes).length > 0) {
            data.attributes = next.attributes
        }

        router.get(url, data, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        })
    }

    const setSort = (sort: StorefrontProductSort) => {
        visit({
            ...filters,
            sort,
        })
    }

    const setBrand = (brand: string | null) => {
        visit({
            ...filters,
            brand,
        })
    }

    const setCategory = (category: string | null) => {
        visit({
            ...filters,
            category,
        })
    }

    const setPrice = (minPrice: number | null, maxPrice: number | null) => {
        visit({
            ...filters,
            min_price: minPrice,
            max_price: maxPrice,
        })
    }

    const setAttribute = (attributeSlug: string, valueSlug: string | null) => {
        const attributes = {
            ...filters.attributes,
        }

        if (valueSlug === null) {
            delete attributes[attributeSlug]
        } else {
            attributes[attributeSlug] = valueSlug
        }

        visit({
            ...filters,
            attributes,
        })
    }

    const clearAll = () => {
        visit({
            sort: 'newest',
            brand: null,
            category: null,
            min_price: null,
            max_price: null,
            attributes: {},
        })
    }

    return {
        setSort,
        setBrand,
        setCategory,
        setPrice,
        setAttribute,
        clearAll,
    }
}
