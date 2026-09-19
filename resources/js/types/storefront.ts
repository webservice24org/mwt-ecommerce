export type StorefrontProductType = 'simple' | 'variable'

export interface StorefrontPricing {
    price: number | null
    compare_at_price: number | null
    on_sale: boolean
}

export interface StorefrontProductListingPricing {
    min_price: number | null
    max_price: number | null
    compare_at_price: number | null
    on_sale: boolean
    varies: boolean
}

export interface StorefrontImage {
    id: number
    url: string
    alt: string | null
    width: number | null
    height: number | null
}

export interface StorefrontBrand {
    id: number
    name: string
    slug: string
}

export interface StorefrontCategory {
    id: number
    name: string
    slug: string
}

export interface StorefrontAttribute {
    id: number
    name: string
    slug: string
}

export interface StorefrontAttributeValue {
    id: number
    name: string
    slug: string
    attribute: StorefrontAttribute
}

export interface StorefrontVariant {
    id: number
    sku: string
    name: string | null
    is_default: boolean
    pricing: StorefrontPricing
    attribute_values: StorefrontAttributeValue[]
}

export interface StorefrontProductCard {
    id: number
    name: string
    slug: string
    type: StorefrontProductType
    short_description: string | null
    is_featured: boolean
    pricing: StorefrontProductListingPricing
    image: StorefrontImage | null
    brand: StorefrontBrand | null
}

export interface StorefrontProductDetail {
    id: number
    name: string
    slug: string
    type: StorefrontProductType
    sku: string | null
    short_description: string | null
    description: string | null
    pricing: StorefrontPricing
    brand: StorefrontBrand | null
    categories: StorefrontCategory[]
    images: StorefrontImage[]
    video: StorefrontVideo | null
    variants: StorefrontVariant[]
    meta_title: string | null
    meta_description: string | null
}

export interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

export interface PaginatedData<T> {
    current_page: number
    data: T[]
    first_page_url: string
    from: number | null
    last_page: number
    last_page_url: string
    links: PaginationLink[]
    next_page_url: string | null
    path: string
    per_page: number
    prev_page_url: string | null
    to: number | null
    total: number
}

export interface StorefrontCategoryDetail {
    id: number
    name: string
    slug: string
    description: string | null
    image_url: string | null
    meta_title: string | null
    meta_description: string | null
}

export interface StorefrontFilterOption {
    id: number
    name: string
    slug: string
}

export interface StorefrontAttributeFilterOption {
    id: number
    name: string
    slug: string
    values: StorefrontFilterOption[]
}

export interface StorefrontFilterOptions {
    brands: StorefrontFilterOption[]
    categories: StorefrontFilterOption[]
    attributes: StorefrontAttributeFilterOption[]
    min_price: number | null
    max_price: number | null
}

export type StorefrontProductSort = 'newest' | 'price_asc' | 'price_desc' | 'name_asc' | 'name_desc'

export interface StorefrontProductFilters {
    sort: StorefrontProductSort
    brand: string | null
    category: string | null
    min_price: number | null
    max_price: number | null
    attributes: Record<string, string>
}

export type StorefrontVideoType = 'upload' | 'youtube' | 'vimeo'

export interface StorefrontVideo {
    type: StorefrontVideoType
    url: string | null
}

export interface StorefrontBreadcrumbItem {
    label: string
    href?: string
}

export interface StorefrontHome {
    featured_products: StorefrontProductCard[]
    new_arrivals: StorefrontProductCard[]
    categories: StorefrontCategory[]
}

export type PaginatedStorefrontProducts = PaginatedData<StorefrontProductCard>
