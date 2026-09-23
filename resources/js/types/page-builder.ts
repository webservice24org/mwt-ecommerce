export type PageType = 'standard' | 'home'

export type PageStatus = 'draft' | 'published' | 'archived'

export type PageContentMode = 'classic' | 'builder'

export interface PageFormOption {
    value: string
    label: string
}

export interface PageFormOptions {
    types: PageFormOption[]
    statuses: PageFormOption[]
    content_modes: PageFormOption[]
}

export interface PageIndexItem {
    id: number
    title: string
    slug: string
    type: PageType
    status: PageStatus
    sections_count: number
    published_at: string | null
}

export interface PageSection {
    id: number
    type: string
    template: string
    config: Record<string, unknown>
    position: number
    is_enabled: boolean
}

export interface PageSeoData {
    meta_title: string | null
    meta_description: string | null
}

export interface PageData {
    id: number
    type: PageType
    title: string
    slug: string
    status: PageStatus
    content_mode: PageContentMode
    content: string | null
    featured_image: string | null
    published_at: string | null
    seo: PageSeoData
    sections: PageSection[]
}

export interface PageFormValues {
    type: PageType
    title: string
    slug: string
    status: PageStatus
    content_mode: PageContentMode
    content: string
    meta_title: string
    meta_description: string
    published_at: string
}

export interface PageFilters {
    search: string
    status: PageStatus | null
    type: PageType | null
}

export interface PageAbilities {
    create: boolean
    delete: boolean
}

export interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

export interface PaginatedPages {
    data: PageIndexItem[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
    links: PaginationLink[]
}
