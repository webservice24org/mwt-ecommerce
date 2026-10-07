export type SpacerDividerLineStyle = 'solid' | 'dashed' | 'dotted'

export type SpacerDividerWidth = 'full' | 'three_quarter' | 'half'

export type SpacerDividerAlignment = 'left' | 'center' | 'right'

export type SpacerDividerLabelStyle = 'plain' | 'pill'

export interface SpacerDividerConfig {
    mobile_height: number
    tablet_height: number
    desktop_height: number

    line_style: SpacerDividerLineStyle

    line_color: string
    line_thickness: number

    width: SpacerDividerWidth

    alignment: SpacerDividerAlignment

    label: string | null

    label_style: SpacerDividerLabelStyle

    text_color: string

    gradient_from: string
    gradient_via: string
    gradient_to: string
}

export function readSpacerDividerConfig(value: unknown): SpacerDividerConfig {
    if (!isRecord(value)) {
        return defaultConfig()
    }

    return {
        mobile_height: readBoundedInteger(value.mobile_height, 32, 0, 320),

        tablet_height: readBoundedInteger(value.tablet_height, 64, 0, 320),

        desktop_height: readBoundedInteger(value.desktop_height, 96, 0, 320),

        line_style: readLineStyle(value.line_style),

        line_color: readHexColor(value.line_color, '#e2e8f0'),

        line_thickness: readBoundedInteger(value.line_thickness, 1, 1, 4),

        width: readWidth(value.width),

        alignment: readAlignment(value.alignment),

        label: readNullableString(value.label, 120),

        label_style: readLabelStyle(value.label_style),

        text_color: readHexColor(value.text_color, '#64748b'),

        gradient_from: readHexColor(value.gradient_from, '#6366f1'),

        gradient_via: readHexColor(value.gradient_via, '#8b5cf6'),

        gradient_to: readHexColor(value.gradient_to, '#ec4899'),
    }
}

function defaultConfig(): SpacerDividerConfig {
    return {
        mobile_height: 32,

        tablet_height: 64,

        desktop_height: 96,

        line_style: 'solid',

        line_color: '#e2e8f0',

        line_thickness: 1,

        width: 'full',

        alignment: 'center',

        label: null,

        label_style: 'plain',

        text_color: '#64748b',

        gradient_from: '#6366f1',

        gradient_via: '#8b5cf6',

        gradient_to: '#ec4899',
    }
}

function readBoundedInteger(value: unknown, fallback: number, min: number, max: number): number {
    if (typeof value !== 'number' || !Number.isInteger(value)) {
        return fallback
    }

    return Math.min(max, Math.max(min, value))
}

function readLineStyle(value: unknown): SpacerDividerLineStyle {
    if (value === 'dashed' || value === 'dotted') {
        return value
    }

    return 'solid'
}

function readWidth(value: unknown): SpacerDividerWidth {
    if (value === 'three_quarter' || value === 'half') {
        return value
    }

    return 'full'
}

function readAlignment(value: unknown): SpacerDividerAlignment {
    if (value === 'left' || value === 'right') {
        return value
    }

    return 'center'
}

function readLabelStyle(value: unknown): SpacerDividerLabelStyle {
    return value === 'pill' ? 'pill' : 'plain'
}

function readNullableString(value: unknown, maxLength: number): string | null {
    if (typeof value !== 'string') {
        return null
    }

    const normalized = value.trim()

    if (normalized === '') {
        return null
    }

    return Array.from(normalized).slice(0, maxLength).join('')
}

function readHexColor(value: unknown, fallback: string): string {
    if (typeof value !== 'string') {
        return fallback
    }

    const normalized = value.trim().toLowerCase()

    return /^#[0-9a-f]{6}$/.test(normalized) ? normalized : fallback
}

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value)
}
