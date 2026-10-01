import type { HeroAlignment, HeroButton, HeroConfig, HeroEffect, HeroSlide } from './types'

type UnknownRecord = Record<string, unknown>

function isRecord(value: unknown): value is UnknownRecord {
    return typeof value === 'object' && value !== null && !Array.isArray(value)
}

function readBoolean(value: unknown, fallback: boolean): boolean {
    return typeof value === 'boolean' ? value : fallback
}

function readNumber(value: unknown, fallback: number): number {
    return typeof value === 'number' && Number.isFinite(value) ? value : fallback
}

function readNullableString(value: unknown): string | null {
    return typeof value === 'string' && value.trim() !== '' ? value : null
}

function readAlignment(value: unknown): HeroAlignment {
    switch (value) {
        case 'center':
        case 'right':
            return value

        default:
            return 'left'
    }
}

function readEffect(value: unknown): HeroEffect {
    switch (value) {
        case 'slide_left':
        case 'slide_right':
        case 'slide_up':
        case 'slide_down':
            return value

        default:
            return 'fade'
    }
}

function readButton(value: unknown): HeroButton | null {
    if (!isRecord(value)) {
        return null
    }

    const label = readNullableString(value.label)

    const url = readNullableString(value.url)

    if (!label || !url) {
        return null
    }

    return {
        label,
        url,
    }
}

function readSlide(value: unknown): HeroSlide | null {
    if (!isRecord(value)) {
        return null
    }

    return {
        background_color: readNullableString(value.background_color),

        background_image: readNullableString(value.background_image),

        image: readNullableString(value.image),

        alt: readNullableString(value.alt),

        url: readNullableString(value.url),

        top_title: readNullableString(value.top_title),

        title: readNullableString(value.title),

        description: readNullableString(value.description),

        alignment: readAlignment(value.alignment),

        primary_button: readButton(value.primary_button),

        secondary_button: readButton(value.secondary_button),
    }
}

export function readHeroConfig(value: unknown): HeroConfig {
    const config = isRecord(value) ? value : {}

    const rawSlides = Array.isArray(config.slides) ? config.slides : []

    const slides = rawSlides.map(readSlide).filter((slide): slide is HeroSlide => slide !== null)

    return {
        autoplay: readBoolean(config.autoplay, false),

        autoplay_delay: readNumber(config.autoplay_delay, 5000),

        effect: readEffect(config.effect),

        show_arrows: readBoolean(config.show_arrows, true),

        show_dots: readBoolean(config.show_dots, true),

        slides,
    }
}
