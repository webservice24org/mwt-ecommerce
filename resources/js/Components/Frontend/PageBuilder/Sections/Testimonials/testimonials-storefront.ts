import type { SlideEffect } from '@/Components/Frontend/PageBuilder/Shared/SlideTransition'

export type TestimonialsAlignment = 'left' | 'center'

export type TestimonialsTextTheme = 'light' | 'dark'

export interface StorefrontTestimonial {
    quote: string
    name: string

    role: string | null
    rating: number | null

    image: string | null
    image_alt: string | null

    badge: string | null
}

export interface TestimonialsSectionConfig {
    eyebrow: string | null
    heading: string | null
    description: string

    items: StorefrontTestimonial[]

    alignment: TestimonialsAlignment

    background_color: string
    text_theme: TestimonialsTextTheme

    show_rating: boolean

    autoplay: boolean
    autoplay_interval: number

    pause_on_hover: boolean
    loop: boolean

    show_arrows: boolean
    show_dots: boolean

    slide_effect: SlideEffect
}

export function readTestimonialsSectionConfig(value: unknown): TestimonialsSectionConfig {
    if (!isRecord(value)) {
        return defaultConfig()
    }

    return {
        eyebrow: readNullableString(value.eyebrow),

        heading: readNullableString(value.heading),

        description: readString(value.description) ?? '',

        items: readTestimonials(value.items),

        alignment: value.alignment === 'center' ? 'center' : 'left',

        background_color: readHexColor(value.background_color),

        text_theme: value.text_theme === 'light' ? 'light' : 'dark',

        show_rating: readBoolean(value.show_rating, true),

        autoplay: readBoolean(value.autoplay, true),

        autoplay_interval: readBoundedInteger(value.autoplay_interval, 5000, 2000, 20000),

        pause_on_hover: readBoolean(value.pause_on_hover, true),

        loop: readBoolean(value.loop, true),

        show_arrows: readBoolean(value.show_arrows, true),

        show_dots: readBoolean(value.show_dots, true),

        slide_effect: readSlideEffect(value.slide_effect),
    }
}

export function readTestimonials(value: unknown): StorefrontTestimonial[] {
    if (!Array.isArray(value)) {
        return []
    }

    const testimonials: StorefrontTestimonial[] = []

    for (const item of value.slice(0, 12)) {
        if (!isRecord(item)) {
            continue
        }

        const quote = readString(item.quote)

        const name = readString(item.name)

        if (quote === null || quote.trim() === '' || name === null || name.trim() === '') {
            continue
        }

        testimonials.push({
            quote: quote.trim(),

            name: name.trim(),

            role: readNullableString(item.role),

            rating: readRating(item.rating),

            image: safeTestimonialMediaUrl(readNullableString(item.image)),

            image_alt: readNullableString(item.image_alt),

            badge: readNullableString(item.badge),
        })
    }

    return testimonials
}

function defaultConfig(): TestimonialsSectionConfig {
    return {
        eyebrow: null,

        heading: null,

        description: '',

        items: [],

        alignment: 'left',

        background_color: '#ffffff',

        text_theme: 'dark',

        show_rating: true,

        autoplay: true,

        autoplay_interval: 5000,

        pause_on_hover: true,

        loop: true,

        show_arrows: true,

        show_dots: true,

        slide_effect: 'slide_left',
    }
}

function readString(value: unknown): string | null {
    return typeof value === 'string' ? value : null
}

function readNullableString(value: unknown): string | null {
    if (typeof value !== 'string') {
        return null
    }

    const normalized = value.trim()

    return normalized === '' ? null : normalized
}

function readBoolean(value: unknown, fallback: boolean): boolean {
    return typeof value === 'boolean' ? value : fallback
}

function readBoundedInteger(value: unknown, fallback: number, min: number, max: number): number {
    if (typeof value !== 'number' || !Number.isInteger(value)) {
        return fallback
    }

    return Math.max(min, Math.min(max, value))
}

function readRating(value: unknown): number | null {
    if (typeof value !== 'number' || !Number.isInteger(value) || value < 1 || value > 5) {
        return null
    }

    return value
}

function readHexColor(value: unknown): string {
    if (typeof value === 'string' && /^#[0-9a-fA-F]{6}$/.test(value)) {
        return value
    }

    return '#ffffff'
}

function readSlideEffect(value: unknown): SlideEffect {
    switch (value) {
        case 'none':
        case 'fade':
        case 'slide_left':
        case 'slide_right':
        case 'slide_up':
        case 'slide_down':
            return value

        default:
            return 'slide_left'
    }
}

export function safeTestimonialMediaUrl(value: string | null): string | null {
    if (!value) {
        return null
    }

    const trimmed = value.trim()

    if (trimmed === '' || hasAsciiControlCharacter(trimmed)) {
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

    if (trimmed.includes('\\')) {
        return null
    }

    try {
        const url = new URL(trimmed)

        if (url.protocol !== 'http:' && url.protocol !== 'https:') {
            return null
        }

        if (url.hostname === '' || url.username !== '' || url.password !== '') {
            return null
        }

        return trimmed
    } catch {
        return null
    }
}

function hasAsciiControlCharacter(value: string): boolean {
    for (let index = 0; index < value.length; index += 1) {
        const code = value.charCodeAt(index)

        if (code <= 31 || code === 127) {
            return true
        }
    }

    return false
}

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value)
}
