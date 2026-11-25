import { usePage } from '@inertiajs/react'
import type { PageProps } from '@/types'
import { productCardThemeProps } from '@/lib/productCardTheme'

export function useProductCardTheme() {
    const theme = usePage<PageProps<{ storefrontTheme?: { product_cards?: unknown } | null }>>().props.storefrontTheme
    return productCardThemeProps(theme?.product_cards)
}
