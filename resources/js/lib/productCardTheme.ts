import type { CSSProperties } from 'react'

export const cardColorKeys = [
    'background_color', 'title_color', 'title_hover_color', 'text_color', 'regular_price_color', 'offer_price_color',
    'add_background_color', 'add_text_color', 'add_border_color', 'add_hover_background_color', 'add_hover_text_color',
    'order_background_color', 'order_text_color', 'order_border_color', 'order_hover_background_color', 'order_hover_text_color',
] as const

export const cardRadiusKeys = ['add_border_radius', 'order_border_radius'] as const
export type CardColorKey = typeof cardColorKeys[number]
export type CardRadiusKey = typeof cardRadiusKeys[number]
export type TitleHoverDecoration = 'underline' | 'none'
export type ProductCardTheme = Partial<Record<CardColorKey, string> & Record<CardRadiusKey, number> & { title_hover_decoration: TitleHoverDecoration }>

// An empty PHP array serializes as []; normalize it to an object before editing.
export function normalizeProductCardTheme(value: unknown): ProductCardTheme {
    if (!value || typeof value !== 'object' || Array.isArray(value)) return {}
    const input = value as Record<string, unknown>
    const result: ProductCardTheme = {}
    for (const key of cardColorKeys) {
        const color = input[key]
        if (typeof color === 'string' && color.length === 7 && /^#[0-9a-f]{6}$/i.test(color)) result[key] = color.toLowerCase()
    }
    for (const key of cardRadiusKeys) {
        const radius = input[key]
        if (typeof radius === 'number' && Number.isInteger(radius) && radius >= 0 && radius <= 32) result[key] = radius
    }
    if (input.title_hover_decoration === 'underline' || input.title_hover_decoration === 'none') {
        result.title_hover_decoration = input.title_hover_decoration
    }
    return result
}

export function productCardThemeProps(value: unknown): { className: string; style: CSSProperties } {
    const config = normalizeProductCardTheme(value)
    const style: Record<string, string> = {}
    const classes = ['mwt-product-card']
    if (cardColorKeys.some(key => config[key] !== undefined)) classes.push('mwt-theme-fixed-card')
    for (const [key, field] of Object.entries(config)) {
        const name = key.replace(/_/g, '-')
        classes.push(`mwt-card-${name}`)
        style[`--mwt-card-${name}`] = typeof field === 'number' ? `${field}px` : field
    }
    return { className: classes.join(' '), style: style as CSSProperties }
}
