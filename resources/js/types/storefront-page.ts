import type { StorefrontProductCard } from '@/types/storefront'

export type StorefrontPageContentMode = 'classic' | 'builder'

export interface StorefrontPageSeo {
    meta_title: string | null
    meta_description: string | null
}

export interface StorefrontResolvedSection {
    id: number
    type: string
    template: string
    config: Record<string, unknown>
    data: Record<string, unknown>
}

export interface StorefrontFeaturedProductsSectionData extends Record<string, unknown> {
    products: StorefrontProductCard[]
}

export interface StorefrontPage {
    id: number
    type: string
    title: string
    slug: string
    content_mode: StorefrontPageContentMode
    content: string | null
    featured_image: string | null
    seo: StorefrontPageSeo
    sections: StorefrontResolvedSection[]
}
