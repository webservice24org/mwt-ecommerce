const MAX_EYEBROW_LENGTH = 120
const MAX_HEADING_LENGTH = 180
const MAX_DESCRIPTION_LENGTH = 1000

const MAX_ITEMS = 16

const MAX_QUESTION_LENGTH = 240
const MAX_ANSWER_LENGTH = 3000

export type FaqAlignment = 'left' | 'center'

export type FaqTextTheme = 'light' | 'dark'

export interface StorefrontFaqItem {
    question: string
    answer: string
}

export interface FaqSectionConfig {
    eyebrow: string | null
    heading: string | null
    description: string

    items: StorefrontFaqItem[]

    alignment: FaqAlignment

    background_color: string
    text_theme: FaqTextTheme

    open_first: boolean
    allow_multiple_open: boolean
}

export function readFaqSectionConfig(value: unknown): FaqSectionConfig {
    if (!isRecord(value)) {
        return defaultConfig()
    }

    return {
        eyebrow: readNullableLimitedString(value.eyebrow, MAX_EYEBROW_LENGTH),

        heading: readNullableLimitedString(value.heading, MAX_HEADING_LENGTH),

        description: readLimitedString(value.description, MAX_DESCRIPTION_LENGTH) ?? '',

        items: readFaqItems(value.items),

        alignment: readAlignment(value.alignment),

        background_color: readHexColor(value.background_color),

        text_theme: readTextTheme(value.text_theme),

        open_first: readBoolean(value.open_first, true),

        allow_multiple_open: readBoolean(value.allow_multiple_open, false),
    }
}

export function readFaqItems(value: unknown): StorefrontFaqItem[] {
    if (!Array.isArray(value)) {
        return []
    }

    const items: StorefrontFaqItem[] = []

    for (const item of value.slice(0, MAX_ITEMS)) {
        if (!isRecord(item)) {
            continue
        }

        const question = readLimitedString(item.question, MAX_QUESTION_LENGTH)

        const answer = readLimitedString(item.answer, MAX_ANSWER_LENGTH)

        if (question === null || answer === null) {
            continue
        }

        if (question === '' || answer === '') {
            continue
        }

        items.push({
            question,
            answer,
        })
    }

    return items
}

function defaultConfig(): FaqSectionConfig {
    return {
        eyebrow: null,

        heading: null,

        description: '',

        items: [],

        alignment: 'left',

        background_color: '#ffffff',

        text_theme: 'dark',

        open_first: true,

        allow_multiple_open: false,
    }
}

function readLimitedString(value: unknown, maxLength: number): string | null {
    if (typeof value !== 'string') {
        return null
    }

    return value.trim().slice(0, maxLength)
}

function readNullableLimitedString(value: unknown, maxLength: number): string | null {
    const normalized = readLimitedString(value, maxLength)

    if (normalized === null || normalized === '') {
        return null
    }

    return normalized
}

function readBoolean(value: unknown, fallback: boolean): boolean {
    return typeof value === 'boolean' ? value : fallback
}

function readAlignment(value: unknown): FaqAlignment {
    return value === 'center' ? 'center' : 'left'
}

function readTextTheme(value: unknown): FaqTextTheme {
    return value === 'light' ? 'light' : 'dark'
}

function readHexColor(value: unknown): string {
    if (typeof value !== 'string') {
        return '#ffffff'
    }

    const normalized = value.trim().toLowerCase()

    if (!/^#[0-9a-f]{6}$/.test(normalized)) {
        return '#ffffff'
    }

    return normalized
}

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value)
}
