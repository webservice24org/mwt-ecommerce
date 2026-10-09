export type FooterTemplateKey = 'luxe_newsletter' | 'minimal_localized' | 'marketplace_trust'

export type FooterConfig = Record<string, unknown>

export interface FooterTemplateOption {
    key: FooterTemplateKey
    label: string
    description: string
}

export type FooterPreviewDevice = 'desktop' | 'tablet' | 'mobile'

export interface FooterLocalizationOption {
    code: string
    label: string
}

export interface FooterLocalizationConfig {
    enabled: boolean
    languages: FooterLocalizationOption[]
    currencies: FooterLocalizationOption[]
}

export interface FooterData {
    id: number
    template: FooterTemplateKey
    config: FooterConfig
    is_enabled: boolean
}

export interface FooterAbilities {
    update: boolean
}

export interface FooterEditorSection {
    key: string
    title: string
    description: string
}

export interface FooterBrandConfig {
    name: string
    description: string
}

export interface FooterCopyrightConfig {
    name: string
    suffix: string
}

export interface FooterLink {
    label: string
    url: string
}

export interface FooterLinkGroup {
    heading: string
    links: FooterLink[]
}

export type FooterSocialPlatform =
    'facebook' | 'instagram' | 'x' | 'tiktok' | 'youtube' | 'pinterest' | 'linkedin'

export interface FooterSocialLink {
    platform: FooterSocialPlatform
    url: string
}

export interface FooterDeveloperConfig {
    prefix: string
    name: string
    url: string
}

export type FooterValuePropIcon =
    | 'package'
    | 'truck'
    | 'shield-check'
    | 'refresh-ccw'
    | 'headphones'
    | 'credit-card'
    | 'lock'
    | 'badge-check'
    | 'award'

export interface FooterValueProp {
    icon: FooterValuePropIcon
    title: string
    description: string
}

export interface FooterNewsletterConfig {
    enabled: boolean
    description: string
    placeholder: string
    button_label: string
}

export interface FooterPromotionConfig {
    enabled: boolean
    badge: string
    message: string
    code: string
    button_label: string
    button_url: string
}

export type FooterPopularLink = FooterLink

export const footerValuePropIcons: {
    value: FooterValuePropIcon
    label: string
}[] = [
    {
        value: 'package',
        label: 'Package',
    },
    {
        value: 'truck',
        label: 'Truck',
    },
    {
        value: 'shield-check',
        label: 'Shield Check',
    },
    {
        value: 'refresh-ccw',
        label: 'Returns / Refresh',
    },
    {
        value: 'headphones',
        label: 'Support',
    },
    {
        value: 'credit-card',
        label: 'Credit Card',
    },
    {
        value: 'lock',
        label: 'Lock',
    },
    {
        value: 'badge-check',
        label: 'Verified Badge',
    },
    {
        value: 'award',
        label: 'Award',
    },
]

export const footerSocialPlatforms: {
    value: FooterSocialPlatform
    label: string
}[] = [
    {
        value: 'facebook',
        label: 'Facebook',
    },
    {
        value: 'instagram',
        label: 'Instagram',
    },
    {
        value: 'x',
        label: 'X',
    },
    {
        value: 'tiktok',
        label: 'TikTok',
    },
    {
        value: 'youtube',
        label: 'YouTube',
    },
    {
        value: 'pinterest',
        label: 'Pinterest',
    },
    {
        value: 'linkedin',
        label: 'LinkedIn',
    },
]

export function isFooterTemplateKey(value: unknown): value is FooterTemplateKey {
    return (
        value === 'luxe_newsletter' ||
        value === 'minimal_localized' ||
        value === 'marketplace_trust'
    )
}

export function isFooterSocialPlatform(value: unknown): value is FooterSocialPlatform {
    return (
        value === 'facebook' ||
        value === 'instagram' ||
        value === 'x' ||
        value === 'tiktok' ||
        value === 'youtube' ||
        value === 'pinterest' ||
        value === 'linkedin'
    )
}

export function readFooterBrand(config: FooterConfig): FooterBrandConfig {
    const value = isRecord(config.brand) ? config.brand : {}

    return {
        name: readString(value.name),

        description: readString(value.description),
    }
}

export function readFooterCopyright(config: FooterConfig): FooterCopyrightConfig {
    const value = isRecord(config.copyright) ? config.copyright : {}

    return {
        name: readString(value.name),

        suffix: readString(value.suffix),
    }
}

export function readFooterLinkGroups(config: FooterConfig): FooterLinkGroup[] {
    const value = config.link_groups

    if (!Array.isArray(value)) {
        return []
    }

    return value.filter(isRecord).map((group): FooterLinkGroup => {
        const rawLinks = Array.isArray(group.links) ? group.links : []

        const links = rawLinks.filter(isRecord).map((link): FooterLink => ({
            label: readString(link.label),

            url: readString(link.url),
        }))

        return {
            heading: readString(group.heading),

            links,
        }
    })
}

export function readFooterSocialLinks(config: FooterConfig): FooterSocialLink[] {
    const value = config.social_links

    if (!Array.isArray(value)) {
        return []
    }

    const links: FooterSocialLink[] = []

    for (const item of value) {
        if (!isRecord(item)) {
            continue
        }

        if (!isFooterSocialPlatform(item.platform)) {
            continue
        }

        links.push({
            platform: item.platform,

            url: readString(item.url),
        })
    }

    return links
}

export function readFooterDeveloper(config: FooterConfig): FooterDeveloperConfig {
    const value = isRecord(config.developer) ? config.developer : {}

    return {
        prefix: readString(value.prefix),

        name: readString(value.name),

        url: readString(value.url),
    }
}

export function readFooterPromotion(config: FooterConfig): FooterPromotionConfig {
    const value = isRecord(config.promotion) ? config.promotion : {}

    return {
        enabled: readBoolean(value.enabled),

        badge: readString(value.badge),

        message: readString(value.message),

        code: readString(value.code),

        button_label: readString(value.button_label),

        button_url: readString(value.button_url),
    }
}

export function readFooterPopularLinks(config: FooterConfig): FooterPopularLink[] {
    const value = config.popular_links

    if (!Array.isArray(value)) {
        return []
    }

    return value.filter(isRecord).map((link): FooterPopularLink => ({
        label: readString(link.label),

        url: readString(link.url),
    }))
}

export function readFooterCertifications(config: FooterConfig): string[] {
    const value = config.certifications

    if (!Array.isArray(value)) {
        return []
    }

    return value.filter((item): item is string => typeof item === 'string')
}

export function isFooterValuePropIcon(value: unknown): value is FooterValuePropIcon {
    return (
        value === 'package' ||
        value === 'truck' ||
        value === 'shield-check' ||
        value === 'refresh-ccw' ||
        value === 'headphones' ||
        value === 'credit-card' ||
        value === 'lock' ||
        value === 'badge-check' ||
        value === 'award'
    )
}

export function readFooterValueProps(config: FooterConfig): FooterValueProp[] {
    const value = config.value_props

    if (!Array.isArray(value)) {
        return []
    }

    const result: FooterValueProp[] = []

    for (const item of value) {
        if (!isRecord(item)) {
            continue
        }

        if (!isFooterValuePropIcon(item.icon)) {
            continue
        }

        result.push({
            icon: item.icon,

            title: readString(item.title),

            description: readString(item.description),
        })
    }

    return result
}

export function readFooterNewsletter(config: FooterConfig): FooterNewsletterConfig {
    const value = isRecord(config.newsletter) ? config.newsletter : {}

    return {
        enabled: readBoolean(value.enabled),

        description: readString(value.description),

        placeholder: readString(value.placeholder),

        button_label: readString(value.button_label),
    }
}

export function readFooterPaymentMethods(config: FooterConfig): string[] {
    const value = config.payment_methods

    if (!Array.isArray(value)) {
        return []
    }

    return value.filter((item): item is string => typeof item === 'string')
}

export interface StorefrontFooterData {
    template: FooterTemplateKey
    config: FooterConfig
}

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value)
}

function readString(value: unknown): string {
    return typeof value === 'string' ? value : ''
}

function readBoolean(value: unknown): boolean {
    return typeof value === 'boolean' ? value : false
}

export function readFooterLocalization(config: FooterConfig): FooterLocalizationConfig {
    const value = isRecord(config.localization) ? config.localization : {}

    return {
        enabled: readBoolean(value.enabled),

        languages: readLocalizationOptions(value.languages),

        currencies: readLocalizationOptions(value.currencies),
    }
}

function readLocalizationOptions(value: unknown): FooterLocalizationOption[] {
    if (!Array.isArray(value)) {
        return []
    }

    return value.filter(isRecord).map((option): FooterLocalizationOption => ({
        code: readString(option.code),

        label: readString(option.label),
    }))
}
