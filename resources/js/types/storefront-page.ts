import type { StorefrontProductCard } from '@/types/storefront'

export type StorefrontPageContentMode = 'classic' | 'builder'

export type StorefrontPageLayout = 'full_width' | 'left_sidebar' | 'right_sidebar'

export type StorefrontSectionWidth = 'container' | 'full'

export interface StorefrontSectionLayout {
    width: StorefrontSectionWidth
}

export interface StorefrontPageSeo {
    meta_title: string | null
    meta_description: string | null
}

export interface StorefrontResolvedSection {
    id: number
    type: string
    template: string
    config: Record<string, unknown>
    layout: StorefrontSectionLayout
    data: Record<string, unknown>
}

export interface StorefrontBuilderHomepage {
    id: number
    title: string
    slug: string
    meta_title: string | null
    meta_description: string | null
    sections: StorefrontResolvedSection[]
}

export interface StorefrontFeaturedProductsSectionData extends Record<string, unknown> {
    products: StorefrontProductCard[]
}

export type StorefrontProductCategoriesTemplate = 'grid' | 'cards' | 'carousel'

export interface StorefrontPageBuilderCategory {
    id: number
    name: string
    slug: string
    description: string | null
    imageUrl: string | null
    productCount: number | null
}

export interface StorefrontProductCategoriesSectionData extends Record<string, unknown> {
    categories: StorefrontPageBuilderCategory[]
}

export interface StorefrontProductCategoriesBaseConfig extends Record<string, unknown> {
    title: string
    category_ids: number[]
    show_name: boolean
    columns: number
    show_product_count: boolean
}

export interface StorefrontProductCategoriesCarouselConfig extends StorefrontProductCategoriesBaseConfig {
    autoplay: boolean
    autoplay_delay: number
    show_arrows: boolean
    show_dots: boolean
}

export interface StorefrontPage {
    id: number
    type: string
    layout: StorefrontPageLayout
    show_breadcrumbs: boolean
    title: string
    slug: string
    content_mode: StorefrontPageContentMode
    content: string | null
    featured_image: string | null
    seo: StorefrontPageSeo
    sections: StorefrontResolvedSection[]
}
