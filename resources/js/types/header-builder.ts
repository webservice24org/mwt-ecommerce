export type HeaderTemplateKey = 'mega_menu'

export interface HeaderTemplateOption {
    key: HeaderTemplateKey
    label: string
}

export interface HeaderAbilities {
    update: boolean
}

export interface HeaderLink {
    label: string
    url: string
}

export interface HeaderStyledLink extends HeaderLink {
    style: 'default' | 'highlight'
}

export interface HeaderMobileLink extends HeaderLink {
    style: 'default' | 'primary' | 'highlight'
}

export interface HeaderAnnouncementConfig {
    enabled: boolean
    badge: string | null
    message: string
    promo_code: string | null
    promo_suffix: string | null
    links: HeaderLink[]
    currency_label: string | null
}

export interface HeaderBrandConfig {
    name: string
    accent: string | null
    home_url: string
    logo_url: string | null
    fallback_mark: string | null
}

export interface HeaderSearchConfig {
    enabled: boolean
    placeholder: string
    suggestions_enabled: boolean
    suggestion_heading: string
    trending_searches: string[]
}

export interface HeaderSimpleActionConfig {
    enabled: boolean
    url: string
}

export interface HeaderAccountConfig {
    enabled: boolean
    guest_login_url: string
    guest_register_url: string
    menu_links: HeaderLink[]
}

export interface HeaderActionsConfig {
    wishlist: HeaderSimpleActionConfig
    cart: HeaderSimpleActionConfig
    account: HeaderAccountConfig
}

export interface HeaderNavigationConfig {
    enabled: boolean
    mega_menu_label: string
    links: HeaderStyledLink[]
}

export interface HeaderMegaMenuGroup {
    heading: string
    links: HeaderLink[]
}

export interface HeaderMegaMenuPromotion {
    enabled: boolean
    eyebrow: string | null
    title: string
    button_label: string
    url: string
}

export interface HeaderMegaMenuConfig {
    enabled: boolean
    groups: HeaderMegaMenuGroup[]
    promotion: HeaderMegaMenuPromotion
}

export interface HeaderMobileConfig {
    search_enabled: boolean
    search_placeholder: string
    menu_links: HeaderMobileLink[]
}

export interface HeaderConfig {
    announcement: HeaderAnnouncementConfig
    brand: HeaderBrandConfig
    search: HeaderSearchConfig
    actions: HeaderActionsConfig
    navigation: HeaderNavigationConfig
    mega_menu: HeaderMegaMenuConfig
    mobile: HeaderMobileConfig
}

export interface HeaderData {
    template: HeaderTemplateKey
    config: HeaderConfig
    is_enabled: boolean
}

export type HeaderPreviewDevice = 'desktop' | 'tablet' | 'mobile'

export interface StorefrontHeaderData {
    template: HeaderTemplateKey
    config: HeaderConfig
    is_enabled: boolean
}
