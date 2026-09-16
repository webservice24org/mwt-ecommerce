export type CategoryParent = {
    id: number
    name: string
}

export type Category = {
    id: number
    parent_id?: number | null
    name: string
    slug: string
    description?: string | null
    image_path?: string | null
    image_url?: string | null
    position: number
    is_active: boolean
    meta_title?: string | null
    meta_description?: string | null
    parent?: CategoryParent | null
    created_at?: string | null
}

export type CategoryParentOption = CategoryParent

export type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

export type Paginated<T> = {
    data: T[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
    links: PaginationLink[]
}

export type Brand = {
    id: number
    name: string
    slug: string
    description?: string | null
    logo_path?: string | null
    logo_url?: string | null
    position: number
    is_active: boolean
    meta_title?: string | null
    meta_description?: string | null
}

export type BrandListItem = {
    id: number
    name: string
    slug: string
    logo_url: string | null
    position: number
    is_active: boolean
}

export type ProductStatus = 'draft' | 'published' | 'archived'

export type ProductStatusOption = {
    value: ProductStatus
    label: string
}

export type ProductBrandOption = {
    id: number
    name: string
}

export type ProductCategoryOption = {
    id: number
    name: string
    parent_id: number | null
}

export type ProductListItem = {
    id: number
    name: string
    slug: string
    status: ProductStatus
    is_featured: boolean
    position: number
    published_at: string | null
    brand: {
        id: number
        name: string
    } | null
    categories_count: number
    variants_count: number
}

export type ProductFormProduct = {
    id: number
    brand_id: number | null
    name: string
    slug: string
    short_description: string | null
    description: string | null
    status: ProductStatus
    is_featured: boolean
    position: number
    published_at: string | null
    meta_title: string | null
    meta_description: string | null
    category_ids: number[]
}

export type AttributeListItem = {
    id: number
    name: string
    slug: string
    position: number
    is_active: boolean
    values_count: number
}

export type AttributeValue = {
    id: number
    name: string
    slug: string
    position: number
    is_active: boolean
}

export type ProductAttribute = {
    id: number
    name: string
    slug: string
    position: number
    is_active: boolean
    values: AttributeValue[]
}

export type ProductVariantAttributeValue = {
    id: number
    name: string
    attribute_id: number
    attribute_name: string
}

export type ProductVariant = {
    id: number
    sku: string
    name: string | null
    price: number
    compare_at_price: number | null
    cost_price: number | null
    barcode: string | null
    position: number
    is_active: boolean
    is_default: boolean
    weight: string | null
    attribute_values: ProductVariantAttributeValue[]
}

export type VariantAttributeValueOption = {
    id: number
    name: string
    is_active: boolean
}

export type VariantAttributeOption = {
    id: number
    name: string
    is_active: boolean
    values: VariantAttributeValueOption[]
}

export type ProductImage = {
    id: number
    path: string
    url: string
    original_name: string | null
    mime_type: string | null
    file_size: number | null
    width: number | null
    height: number | null
    alt_text: string | null
    position: number
    is_primary: boolean
}

export type ProductVideoType = 'upload' | 'youtube' | 'vimeo'

export type ProductVideo = {
    id: number
    type: ProductVideoType
    path: string | null
    url: string | null
    file_url: string | null
    title: string | null
    original_name: string | null
    mime_type: string | null
    file_size: number | null
}
