import type { CSSProperties } from 'react'

export const brandingImageKeys = ['login_logo', 'sidebar_logo', 'background_image'] as const
export type BrandingImageKey = typeof brandingImageKeys[number]
export type BackgroundMode = 'default' | 'color' | 'image'
export type AdminBranding = Partial<Record<BrandingImageKey | 'background_color', string> & { background_mode: BackgroundMode }>

export function normalizeAdminBranding(value: unknown): AdminBranding {
    if (!value || typeof value !== 'object' || Array.isArray(value)) return {}
    const source = value as Record<string, unknown>
    const result: AdminBranding = {}
    if (source.background_mode === 'default' || source.background_mode === 'color' || source.background_mode === 'image') result.background_mode = source.background_mode
    if (typeof source.background_color === 'string' && /^#[0-9a-f]{6}$/i.test(source.background_color) && source.background_color.length === 7) result.background_color = source.background_color.toLowerCase()
    for (const key of brandingImageKeys) {
        const url = source[key]
        if (typeof url === 'string' && new RegExp(`^/storage/branding/admin/${key}/[A-Za-z0-9]{40}\\.(?:png|jpe?g|webp)$`).test(url) && !/\s/.test(url)) result[key] = url
    }
    return result
}

export function adminLoginBackground(value: unknown): CSSProperties {
    const branding = normalizeAdminBranding(value)
    if (branding.background_mode === 'color' && branding.background_color) return { backgroundColor: branding.background_color }
    if (branding.background_mode === 'image' && branding.background_image) return {
        backgroundColor: branding.background_color ?? 'var(--mwt-canvas)',
        backgroundImage: `url("${branding.background_image}")`,
        backgroundPosition: 'center',
        backgroundSize: 'cover',
        backgroundRepeat: 'no-repeat',
    }
    return {}
}
