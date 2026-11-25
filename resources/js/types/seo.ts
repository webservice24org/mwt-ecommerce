export type SeoBooleanOverride = boolean | null

export interface EntitySeoFormData {
    canonical_url: string
    robots_index: SeoBooleanOverride
    robots_follow: SeoBooleanOverride

    og_title: string
    og_description: string
    og_image: string

    twitter_title: string
    twitter_description: string
    twitter_image: string
}

export interface EntitySeoData {
    canonical_url: string | null
    robots_index: boolean | null
    robots_follow: boolean | null

    og_title: string | null
    og_description: string | null
    og_image: string | null

    twitter_title: string | null
    twitter_description: string | null
    twitter_image: string | null
}

export function emptyEntitySeoFormData(): EntitySeoFormData {
    return {
        canonical_url: '',
        robots_index: null,
        robots_follow: null,

        og_title: '',
        og_description: '',
        og_image: '',

        twitter_title: '',
        twitter_description: '',
        twitter_image: '',
    }
}

export function entitySeoDataToFormData(
    seo?: EntitySeoData | null,
): EntitySeoFormData {
    return {
        canonical_url: seo?.canonical_url ?? '',

        robots_index:
            seo?.robots_index ?? null,

        robots_follow:
            seo?.robots_follow ?? null,

        og_title:
            seo?.og_title ?? '',

        og_description:
            seo?.og_description ?? '',

        og_image:
            seo?.og_image ?? '',

        twitter_title:
            seo?.twitter_title ?? '',

        twitter_description:
            seo?.twitter_description ?? '',

        twitter_image:
            seo?.twitter_image ?? '',
    }
}

export interface ResolvedSeo {
    title: string
    description: string | null

    canonical_override: string | null
    canonical_url: string

    robots_index: boolean
    robots_follow: boolean

    og_title: string
    og_description: string | null
    og_image: string | null

    twitter_title: string
    twitter_description: string | null
    twitter_image: string | null
}