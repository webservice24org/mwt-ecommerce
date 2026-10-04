export type ContentAlignment = 'left' | 'center' | 'right'

export interface ContentSectionConfig {
    heading: string
    body: string
    image: string | null
    image_alt: string | null
    alignment: ContentAlignment
}

function readString(value: unknown): string {
    return typeof value === 'string' ? value.trim() : ''
}

function readNullableString(value: unknown): string | null {
    const normalized = readString(value)

    return normalized === '' ? null : normalized
}

function readAlignment(value: unknown, fallback: ContentAlignment): ContentAlignment {
    return value === 'left' || value === 'center' || value === 'right' ? value : fallback
}

export function readContentSectionConfig(
    config: Record<string, unknown>,
    fallbackAlignment: ContentAlignment = 'left',
): ContentSectionConfig {
    return {
        heading: readString(config.heading),
        body: readString(config.body),
        image: readNullableString(config.image),
        image_alt: readNullableString(config.image_alt),
        alignment: readAlignment(config.alignment, fallbackAlignment),
    }
}

export const contentAlignmentClasses: Record<ContentAlignment, string> = {
    left: 'items-start text-left',
    center: 'items-center text-center',
    right: 'items-end text-right',
}

export const contentHeadingClasses =
    'break-words text-2xl font-semibold tracking-tight text-neutral-950 [overflow-wrap:anywhere] sm:text-3xl dark:text-neutral-50'

export const contentBodyClasses =
    'whitespace-pre-line break-words text-base leading-7 text-neutral-700 [overflow-wrap:anywhere] dark:text-neutral-300'
