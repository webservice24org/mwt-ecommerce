export type PromotionalBannerAlignment = 'left' | 'center' | 'right'

export interface PromotionalBannerConfig {
    heading: string
    description: string
    image: string | null
    cta_label: string
    cta_url: string
    alignment: PromotionalBannerAlignment
}

function readString(value: unknown): string {
    return typeof value === 'string' ? value.trim() : ''
}

function hasControlCharacters(value: string): boolean {
    for (const character of value) {
        const code = character.charCodeAt(0)

        if (code <= 31 || code === 127) {
            return true
        }
    }

    return false
}

function readSafeHref(value: unknown): string {
    const href = readString(value)

    if (!href) {
        return ''
    }

    if (hasControlCharacters(href)) {
        return ''
    }

    if (
        href.startsWith('/') ||
        href.startsWith('./') ||
        href.startsWith('../') ||
        href.startsWith('#') ||
        href.startsWith('?')
    ) {
        return href
    }

    const schemeMatch = href.match(/^([a-z][a-z0-9+.-]*):/i)

    if (!schemeMatch) {
        return href
    }

    const scheme = schemeMatch[1]?.toLowerCase()

    return scheme === 'http' || scheme === 'https' || scheme === 'mailto' || scheme === 'tel'
        ? href
        : ''
}

export function readPromotionalBannerConfig(
    config: Record<string, unknown>,
): PromotionalBannerConfig {
    const image = readString(config.image)
    const alignment = config.alignment

    return {
        heading: readString(config.heading),
        description: readString(config.description),
        image: image || null,
        cta_label: readString(config.cta_label),
        cta_url: readSafeHref(config.cta_url),
        alignment:
            alignment === 'center' || alignment === 'right' || alignment === 'left'
                ? alignment
                : 'left',
    }
}

export const promotionalBannerAlignmentClasses: Record<PromotionalBannerAlignment, string> = {
    left: 'items-start text-left',
    center: 'items-center text-center',
    right: 'items-end text-right',
}
