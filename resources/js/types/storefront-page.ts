import type {
    StorefrontProductCard,
} from '@/types/storefront'

export type StorefrontPageContentMode =
    | 'classic'
    | 'builder'

export type StorefrontPageLayout =
    | 'full_width'
    | 'left_sidebar'
    | 'right_sidebar'

export type StorefrontSectionWidth =
    | 'container'
    | 'full'

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

export interface StorefrontFeaturedProductsSectionData
    extends Record<string, unknown> {
    products: StorefrontProductCard[]
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