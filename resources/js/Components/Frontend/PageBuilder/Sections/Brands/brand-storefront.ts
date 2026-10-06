export type BrandAlignment = 'left' | 'center'

export type BrandTextTheme = 'light' | 'dark'

export type BrandColumns = 2 | 3 | 4 | 5 | 6

export interface BrandSectionConfig {
    eyebrow: string | null
    heading: string
    description: string

    columns: BrandColumns
    alignment: BrandAlignment

    background_color: string
    text_theme: BrandTextTheme

    show_name: boolean
    show_description: boolean
    show_product_count: boolean

    view_all_label: string | null
    view_all_url: string | null

    marquee_duration: number
    pause_on_hover: boolean

    primary_button_label: string | null
    primary_button_url: string | null

    secondary_button_label: string | null
    secondary_button_url: string | null
}

export interface StorefrontBrand {
    id: number
    name: string
    slug: string
    description: string | null
    logo_url: string | null
    product_count: number
}

export function readBrandSectionConfig(value: unknown): BrandSectionConfig {
    if (!isRecord(value)) {
        return defaultConfig()
    }

    return {
        eyebrow: readNullableString(value.eyebrow),

        heading: readString(value.heading) ?? 'Shop by Brand',

        description: readString(value.description) ?? '',

        columns: readColumns(value.columns),

        alignment: value.alignment === 'left' ? 'left' : 'center',

        background_color: readHexColor(value.background_color),

        text_theme: value.text_theme === 'light' ? 'light' : 'dark',

        show_name: typeof value.show_name === 'boolean' ? value.show_name : false,

        show_description:
            typeof value.show_description === 'boolean' ? value.show_description : false,

        show_product_count:
            typeof value.show_product_count === 'boolean' ? value.show_product_count : false,

        view_all_label: readNullableString(value.view_all_label),

        view_all_url: safeOptionalHref(readNullableString(value.view_all_url)),

        marquee_duration: readBoundedInteger(value.marquee_duration, 25, 10, 60),

        pause_on_hover: typeof value.pause_on_hover === 'boolean' ? value.pause_on_hover : true,

        primary_button_label: readNullableString(value.primary_button_label),

        primary_button_url: safeOptionalHref(readNullableString(value.primary_button_url)),

        secondary_button_label: readNullableString(value.secondary_button_label),

        secondary_button_url: safeOptionalHref(readNullableString(value.secondary_button_url)),
    }
}

export function readBrandSectionData(value: unknown): StorefrontBrand[] {
    if (!Array.isArray(value)) {
        return []
    }

    const brands: StorefrontBrand[] = []

    const seen = new Set<number>()

    for (const item of value) {
        if (!isRecord(item)) {
            continue
        }

        const id = readPositiveInteger(item.id)

        const name = readString(item.name)

        if (id === null || name === null || name === '' || seen.has(id)) {
            continue
        }

        seen.add(id)

        brands.push({
            id,

            name,

            slug: readString(item.slug) ?? '',

            description: readNullableString(item.description),

            logo_url: readNullableString(item.logo_url),

            product_count: readNonNegativeInteger(item.product_count),
        })
    }

    return brands
}

export function safeOptionalHref(value: string | null): string | null {
    if (!value) {
        return null
    }

    const trimmed = value.trim()

    if (trimmed === '') {
        return null
    }

    /*
     * Reject ASCII control characters.
     */
    if (/[\u0000-\u001f\u007f]/.test(trimmed)) {
        return null
    }

    /*
     * Internal application path.
     */
    if (trimmed.startsWith('/')) {
        if (trimmed.startsWith('//') || trimmed.includes('\\')) {
            return null
        }

        return trimmed
    }

    /*
     * External absolute URL.
     */
    try {
        const parsed = new URL(trimmed)

        if (parsed.protocol !== 'https:' && parsed.protocol !== 'http:') {
            return null
        }

        return trimmed
    } catch {
        return null
    }
}

function defaultConfig(): BrandSectionConfig {
    return {
        eyebrow: null,

        heading: 'Shop by Brand',

        description: '',

        columns: 6,

        alignment: 'center',

        background_color: '#ffffff',

        text_theme: 'dark',

        show_name: false,

        show_description: false,

        show_product_count: false,

        view_all_label: null,

        view_all_url: null,

        marquee_duration: 25,

        pause_on_hover: true,
        primary_button_label: null,

        primary_button_url: null,

        secondary_button_label: null,

        secondary_button_url: null,
    }
}

function readColumns(value: unknown): BrandColumns {
    if (value === 2 || value === 3 || value === 4 || value === 5 || value === 6) {
        return value
    }

    return 6
}

function readPositiveInteger(value: unknown): number | null {
    if (typeof value !== 'number' || !Number.isInteger(value) || value < 1) {
        return null
    }

    return value
}

function readNonNegativeInteger(value: unknown): number {
    if (typeof value !== 'number' || !Number.isInteger(value) || value < 0) {
        return 0
    }

    return value
}

function readString(value: unknown): string | null {
    if (typeof value !== 'string') {
        return null
    }

    return value.trim()
}

function readNullableString(value: unknown): string | null {
    const stringValue = readString(value)

    if (stringValue === null || stringValue === '') {
        return null
    }

    return stringValue
}

function readHexColor(value: unknown): string {
    if (typeof value === 'string' && /^#[0-9a-fA-F]{6}$/.test(value)) {
        return value
    }

    return '#ffffff'
}

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value)
}

function readBoundedInteger(value: unknown, fallback: number, min: number, max: number): number {
    if (typeof value !== 'number' || !Number.isInteger(value)) {
        return fallback
    }

    return Math.max(min, Math.min(max, value))
}
