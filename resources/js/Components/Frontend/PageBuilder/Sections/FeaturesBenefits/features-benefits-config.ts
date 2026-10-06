import { isFeatureBenefitIconName } from '@/PageBuilder/feature-benefit-icon-options'
import {
    getSafeFeatureImageUrl,
    getSafeFeatureLinkUrl,
} from '@/PageBuilder/features-benefits-security'

export type FeaturesAlignment = 'left' | 'center'

export type FeaturesTextTheme = 'light' | 'dark'

export type FeaturesColumns = 2 | 3 | 4

export interface FeatureBenefitItem {
    title: string
    description: string

    icon: string | null

    image: string | null
    image_alt: string | null

    link_label: string | null
    link_url: string | null
}

export interface FeaturesBenefitsConfig {
    eyebrow: string | null
    heading: string
    description: string

    items: FeatureBenefitItem[]

    columns: FeaturesColumns
    alignment: FeaturesAlignment

    background_color: string
    text_theme: FeaturesTextTheme
}

export function readFeaturesBenefitsConfig(value: unknown): FeaturesBenefitsConfig {
    const source = isObject(value) ? value : {}

    return {
        eyebrow: readNullableString(source.eyebrow),

        heading: readString(source.heading),

        description: readString(source.description),

        items: readItems(source.items),

        columns: readColumns(source.columns),

        alignment: readAlignment(source.alignment),

        background_color: readBackgroundColor(source.background_color),

        text_theme: readTextTheme(source.text_theme),
    }
}

function readItems(value: unknown): FeatureBenefitItem[] {
    if (!Array.isArray(value)) {
        return []
    }

    return value
        .filter(isObject)
        .map((item): FeatureBenefitItem => {
            const icon = readNullableString(item.icon)

            const image = readNullableString(item.image)

            const linkUrl = readNullableString(item.link_url)

            return {
                title: readString(item.title),

                description: readString(item.description),

                icon: isFeatureBenefitIconName(icon) ? icon : null,

                image: getSafeFeatureImageUrl(image),

                image_alt: readNullableString(item.image_alt),

                link_label: readNullableString(item.link_label),

                link_url: getSafeFeatureLinkUrl(linkUrl),
            }
        })
        .filter((item) => item.title !== '')
}

function readColumns(value: unknown): FeaturesColumns {
    if (value === 2 || value === 3 || value === 4) {
        return value
    }

    return 3
}

function readAlignment(value: unknown): FeaturesAlignment {
    return value === 'left' ? 'left' : 'center'
}

function readTextTheme(value: unknown): FeaturesTextTheme {
    return value === 'light' ? 'light' : 'dark'
}

function readBackgroundColor(value: unknown): string {
    if (typeof value === 'string' && /^#[0-9a-fA-F]{6}$/.test(value.trim())) {
        return value.trim()
    }

    return '#ffffff'
}

function readString(value: unknown): string {
    return typeof value === 'string' ? value.trim() : ''
}

function readNullableString(value: unknown): string | null {
    if (typeof value !== 'string') {
        return null
    }

    const normalized = value.trim()

    return normalized === '' ? null : normalized
}

function isObject(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value)
}
