export type StorefrontTwitterCard =
    | 'summary'
    | 'summary_large_image'

export interface StorefrontSocialSeoSettings {
    site_name: string | null
    default_og_image: string | null
    enable_open_graph: boolean
    enable_twitter_cards: boolean
    twitter_card: StorefrontTwitterCard
}

export interface StorefrontSocialImageMetadata {
    url: string
    alt: string | null
    width: number | null
    height: number | null
}
