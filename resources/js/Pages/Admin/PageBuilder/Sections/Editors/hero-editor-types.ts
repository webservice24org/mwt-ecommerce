import type { JsonValue, SectionConfig } from '@/types/page-builder'

export type HeroEffect =
    'slide_left' | 'slide_right' | 'slide_up' | 'slide_down' | 'fade' | 'fade_scale' | 'zoom'

export type HeroAlignment = 'left' | 'center' | 'right'

export interface HeroButton {
    label: string
    url: string
}

export interface HeroSlide {
    background_color?: string | null
    background_image?: string | null

    image?: string | null
    alt?: string | null
    url?: string | null

    top_title?: string | null
    title?: string | null
    description?: string | null

    alignment: HeroAlignment

    primary_button: HeroButton | null
    secondary_button: HeroButton | null
}

export interface HeroConfig {
    autoplay: boolean
    autoplay_delay: number
    effect: HeroEffect
    show_arrows: boolean
    show_dots: boolean
    slides: HeroSlide[]
}

export function isHeroEffect(value: JsonValue | undefined): value is HeroEffect {
    return (
        value === 'slide_left' ||
        value === 'slide_right' ||
        value === 'slide_up' ||
        value === 'slide_down' ||
        value === 'fade' ||
        value === 'fade_scale' ||
        value === 'zoom'
    )
}

function isRecord(value: JsonValue): value is Record<string, JsonValue> {
    return typeof value === 'object' && value !== null && !Array.isArray(value)
}

function optionalString(value: JsonValue | undefined): string | null | undefined {
    if (value === null) {
        return null
    }

    return typeof value === 'string' ? value : undefined
}

function normalizeAlignment(value: JsonValue | undefined): HeroAlignment {
    if (value === 'center' || value === 'right') {
        return value
    }

    return 'left'
}

function normalizeButton(value: JsonValue | undefined): HeroButton | null {
    if (value === null || value === undefined || !isRecord(value)) {
        return null
    }

    return {
        label: typeof value.label === 'string' ? value.label : '',

        url: typeof value.url === 'string' ? value.url : '',
    }
}

export function normalizeHeroSlide(value: JsonValue): HeroSlide {
    if (!isRecord(value)) {
        return createContentHeroSlide()
    }

    const slide: HeroSlide = {
        alignment: normalizeAlignment(value.alignment),

        primary_button: normalizeButton(value.primary_button),

        secondary_button: normalizeButton(value.secondary_button),
    }

    const backgroundColor = optionalString(value.background_color)

    const backgroundImage = optionalString(value.background_image)

    const image = optionalString(value.image)

    const alt = optionalString(value.alt)

    const url = optionalString(value.url)

    const topTitle = optionalString(value.top_title)

    const title = optionalString(value.title)

    const description = optionalString(value.description)

    if (backgroundColor !== undefined) {
        slide.background_color = backgroundColor
    }

    if (backgroundImage !== undefined) {
        slide.background_image = backgroundImage
    }

    if (image !== undefined) {
        slide.image = image
    }

    if (alt !== undefined) {
        slide.alt = alt
    }

    if (url !== undefined) {
        slide.url = url
    }

    if (topTitle !== undefined) {
        slide.top_title = topTitle
    }

    if (title !== undefined) {
        slide.title = title
    }

    if (description !== undefined) {
        slide.description = description
    }

    return slide
}

export function normalizeHeroConfig(value: SectionConfig): HeroConfig {
    const slides = Array.isArray(value.slides) ? value.slides.map(normalizeHeroSlide) : []

    return {
        autoplay: typeof value.autoplay === 'boolean' ? value.autoplay : true,

        autoplay_delay: typeof value.autoplay_delay === 'number' ? value.autoplay_delay : 5000,

        effect: isHeroEffect(value.effect) ? value.effect : 'slide_left',

        show_arrows: typeof value.show_arrows === 'boolean' ? value.show_arrows : true,

        show_dots: typeof value.show_dots === 'boolean' ? value.show_dots : true,

        slides: slides.length > 0 ? slides : [createContentHeroSlide()],
    }
}

export function createContentHeroSlide(): HeroSlide {
    return {
        background_color: '#111827',
        background_image: null,

        top_title: '',
        title: '',
        description: '',

        alignment: 'left',

        primary_button: null,
        secondary_button: null,
    }
}

export function createImageHeroSlide(): HeroSlide {
    return {
        image: null,
        alt: '',
        url: '',

        alignment: 'left',

        primary_button: null,
        secondary_button: null,
    }
}

export function createHeroSlideForTemplate(template: string): HeroSlide {
    if (template === 'image_slider') {
        return createImageHeroSlide()
    }

    return createContentHeroSlide()
}

function heroButtonToJson(button: HeroButton | null): JsonValue {
    if (button === null) {
        return null
    }

    return {
        label: button.label,
        url: button.url,
    }
}

function heroSlideToJson(slide: HeroSlide): JsonValue {
    const json: Record<string, JsonValue> = {
        alignment: slide.alignment,

        primary_button: heroButtonToJson(slide.primary_button),

        secondary_button: heroButtonToJson(slide.secondary_button),
    }

    if (slide.background_color !== undefined) {
        json.background_color = slide.background_color
    }

    if (slide.background_image !== undefined) {
        json.background_image = slide.background_image
    }

    if (slide.image !== undefined) {
        json.image = slide.image
    }

    if (slide.alt !== undefined) {
        json.alt = slide.alt
    }

    if (slide.url !== undefined) {
        json.url = slide.url
    }

    if (slide.top_title !== undefined) {
        json.top_title = slide.top_title
    }

    if (slide.title !== undefined) {
        json.title = slide.title
    }

    if (slide.description !== undefined) {
        json.description = slide.description
    }

    return json
}

export function heroConfigToSectionConfig(config: HeroConfig): SectionConfig {
    return {
        autoplay: config.autoplay,
        autoplay_delay: config.autoplay_delay,
        effect: config.effect,
        show_arrows: config.show_arrows,
        show_dots: config.show_dots,
        slides: config.slides.map(heroSlideToJson),
    }
}
