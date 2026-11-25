import type { CSSProperties } from 'react'

export type BuilderStyleSection = 'announcement' | 'main' | 'navigation' | 'top' | 'bottom'

export interface BuilderSectionStyle {
    text_color?: string
    background_color?: string
    text_size?: number
}

export type BuilderStyle = Partial<Record<BuilderStyleSection, BuilderSectionStyle>>

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value)
}

function isColor(value: unknown): value is string {
    return typeof value === 'string' && /^#[0-9a-fA-F]{6}$/.test(value) && value.length === 7
}

export function readBuilderStyle(value: unknown, sections: readonly BuilderStyleSection[]): BuilderStyle {
    const result: BuilderStyle = {}
    if (!isRecord(value)) return result

    for (const section of sections) {
        const raw = value[section]
        if (!isRecord(raw)) continue

        const style: BuilderSectionStyle = {}
        if (isColor(raw.text_color)) style.text_color = raw.text_color.toLowerCase()
        if (isColor(raw.background_color)) style.background_color = raw.background_color.toLowerCase()
        if (typeof raw.text_size === 'number' && Number.isInteger(raw.text_size) && raw.text_size >= 10 && raw.text_size <= 24) {
            style.text_size = raw.text_size
        }
        if (Object.keys(style).length > 0) result[section] = style
    }
    return result
}

type RegionStyle = CSSProperties & {
    '--mwt-builder-text-color'?: string
    '--mwt-builder-text-size'?: string
}

// The same renderer is used by the live previews and storefront. Validate again
// before CSS is emitted, including old header records read directly from storage.
export function builderStyleProps(value: unknown, section: BuilderStyleSection) {
    const region = readBuilderStyle(value, [section])[section]
    if (!region) return {}

    const style: RegionStyle = {}
    if (region.background_color) style.background = region.background_color
    if (region.text_color) style['--mwt-builder-text-color'] = region.text_color
    if (region.text_size !== undefined) style['--mwt-builder-text-size'] = `${region.text_size}px`

    return {
        'data-mwt-style-region': section,
        'data-mwt-theme-fixed': region.background_color || region.text_color ? 'true' : undefined,
        'data-mwt-style-color': region.text_color ? 'true' : undefined,
        'data-mwt-style-size': region.text_size !== undefined ? 'true' : undefined,
        style,
    }
}
