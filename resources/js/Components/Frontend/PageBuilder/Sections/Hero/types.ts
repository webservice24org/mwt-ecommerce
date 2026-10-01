export type HeroTemplate = 'content_slider' | 'image_slider' | 'static'

export type HeroEffect = 'fade' | 'slide_left' | 'slide_right' | 'slide_up' | 'slide_down'

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
