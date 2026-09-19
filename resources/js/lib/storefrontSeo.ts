import { storefrontAbsoluteUrl } from '@/lib/storefrontUrl'
import type { StorefrontBreadcrumbItem } from '@/types/storefront'

type BreadcrumbListItem = {
    '@type': 'ListItem'
    position: number
    name: string
    item?: string
}

type BreadcrumbListSchema = {
    '@context': 'https://schema.org'
    '@type': 'BreadcrumbList'
    itemListElement: BreadcrumbListItem[]
}

export function buildBreadcrumbJsonLd(items: StorefrontBreadcrumbItem[]): BreadcrumbListSchema {
    return {
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: items.map((item, index) => ({
            '@type': 'ListItem',
            position: index + 1,
            name: item.label,
            ...(item.href
                ? {
                      item: storefrontAbsoluteUrl(item.href),
                  }
                : {}),
        })),
    }
}
