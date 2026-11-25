import { storefrontAbsoluteUrl } from '@/lib/storefrontUrl'

import type {
    PageProps,
} from '@/types'

import type {
    ResolvedSeo,
} from '@/types/seo'

import type {
    StorefrontSocialImageMetadata,
    StorefrontSocialSeoSettings,
} from '@/types/social-seo'

import type {
    StructuredDataDocument,
} from '@/types/structured-data'

import {
    Head,
    usePage,
} from '@inertiajs/react'

interface SharedStorefrontSeoProps {
    type?: 'website' | 'product'
    jsonLd?: StructuredDataDocument | null
    imageMetadata?:
        StorefrontSocialImageMetadata
        | null
}

interface StorefrontRobots {
    index: boolean
    follow: boolean
}

interface ResolvedStorefrontSeoProps
    extends SharedStorefrontSeoProps {
    seo: ResolvedSeo

    canonicalPath?: never
    title?: never
    description?: never
    image?: never
    robots?: never
}

interface LegacyStorefrontSeoProps
    extends SharedStorefrontSeoProps {
    seo?: undefined

    title: string
    description?: string | null
    canonicalPath: string
    image?: string | null
    robots?: StorefrontRobots
}

type StorefrontSeoProps =
    | ResolvedStorefrontSeoProps
    | LegacyStorefrontSeoProps

type StorefrontSeoPageProps =
    PageProps<{
        storefrontSocialSeo?:
            StorefrontSocialSeoSettings | null
    }>

function positiveDimension(
    value: number | null | undefined,
): number | null {
    return typeof value === 'number'
        && Number.isInteger(value)
        && value > 0
        ? value
        : null
}

export default function StorefrontSeo(
    props: StorefrontSeoProps,
) {
    const {
        type = 'website',
        jsonLd = null,
        imageMetadata = null,
    } = props

    const {
        storefrontSocialSeo,
    } =
        usePage<StorefrontSeoPageProps>()
            .props

    const resolvedSeo =
        props.seo !== undefined
            ? props.seo
            : null

    const title =
        props.seo !== undefined
            ? props.seo.title
            : props.title

    const description =
        props.seo !== undefined
            ? props.seo.description
            : (
                  props.description
                  ?? null
              )

    const ogTitle =
        props.seo !== undefined
            ? props.seo.og_title
            : title

    const ogDescription =
        props.seo !== undefined
            ? props.seo.og_description
            : description

    const ogImage =
        props.seo !== undefined
            ? props.seo.og_image
            : (
                  props.image
                  ?? storefrontSocialSeo
                      ?.default_og_image
                  ?? null
              )

    const twitterTitle =
        props.seo !== undefined
            ? props.seo.twitter_title
            : title

    const twitterDescription =
        props.seo !== undefined
            ? props.seo.twitter_description
            : description

    const twitterImage =
        props.seo !== undefined
            ? props.seo.twitter_image
            : ogImage

    const canonicalUrl =
        props.seo !== undefined
            ? props.seo.canonical_url
            : storefrontAbsoluteUrl(
                  props.canonicalPath,
              )

    const ogImageUrl =
        ogImage
            ? storefrontAbsoluteUrl(
                  ogImage,
              )
            : null

    const twitterImageUrl =
        twitterImage
            ? storefrontAbsoluteUrl(
                  twitterImage,
              )
            : null

    const metadataImageUrl =
        imageMetadata?.url
            ? storefrontAbsoluteUrl(
                  imageMetadata.url,
              )
            : null

    const ogImageMatchesMetadata =
        ogImageUrl !== null
        && metadataImageUrl !== null
        && ogImageUrl === metadataImageUrl

    const twitterImageMatchesMetadata =
        twitterImageUrl !== null
        && metadataImageUrl !== null
        && twitterImageUrl === metadataImageUrl

    const trustedImageAlt =
        imageMetadata
            ?.alt
            ?.trim()
        || null

    const trustedImageWidth =
        positiveDimension(
            imageMetadata?.width,
        )

    const trustedImageHeight =
        positiveDimension(
            imageMetadata?.height,
        )

    const ogImageAlt =
        ogImageMatchesMetadata
            ? trustedImageAlt
            : null

    const twitterImageAlt =
        twitterImageMatchesMetadata
            ? trustedImageAlt
            : null

    const ogImageWidth =
        ogImageMatchesMetadata
            ? trustedImageWidth
            : null

    const ogImageHeight =
        ogImageMatchesMetadata
            ? trustedImageHeight
            : null

    const openGraphEnabled =
        storefrontSocialSeo
            ?.enable_open_graph
        ?? true

    const twitterCardsEnabled =
        storefrontSocialSeo
            ?.enable_twitter_cards
        ?? true

    const siteName =
        storefrontSocialSeo
            ?.site_name
            ?.trim()
        || null

    const configuredTwitterCard =
        storefrontSocialSeo
            ?.twitter_card
        ?? 'summary_large_image'

    const twitterCard =
        twitterImageUrl
            ? configuredTwitterCard
            : 'summary'

    const legacyRobots =
        props.seo === undefined
            ? (
                  props.robots
                  ?? null
              )
            : null

    const robotsIndex =
        resolvedSeo
            ? resolvedSeo.robots_index
            : legacyRobots?.index

    const robotsFollow =
        resolvedSeo
            ? resolvedSeo.robots_follow
            : legacyRobots?.follow

    const robotsContent =
        robotsIndex !== undefined
        && robotsFollow !== undefined
            ? [
                  robotsIndex
                      ? 'index'
                      : 'noindex',

                  robotsFollow
                      ? 'follow'
                      : 'nofollow',
              ].join(', ')
            : null

    /*
     * IMPORTANT:
     *
     * Keep every Head child flat.
     *
     * Some Inertia React versions do not safely process
     * nested React fragments inside <Head>. Conditional
     * meta nodes are therefore rendered individually.
     */
    return (
        <Head title={title}>
            {description && (
                <meta
                    head-key="description"
                    name="description"
                    content={
                        description
                    }
                />
            )}

            {robotsContent && (
                <meta
                    head-key="robots"
                    name="robots"
                    content={
                        robotsContent
                    }
                />
            )}

            <link
                head-key="canonical"
                rel="canonical"
                href={
                    canonicalUrl
                }
            />

            {openGraphEnabled && (
                <meta
                    head-key="og:title"
                    property="og:title"
                    content={
                        ogTitle
                    }
                />
            )}

            {openGraphEnabled
            && ogDescription && (
                <meta
                    head-key="og:description"
                    property="og:description"
                    content={
                        ogDescription
                    }
                />
            )}

            {openGraphEnabled && (
                <meta
                    head-key="og:type"
                    property="og:type"
                    content={
                        type
                    }
                />
            )}

            {openGraphEnabled && (
                <meta
                    head-key="og:url"
                    property="og:url"
                    content={
                        canonicalUrl
                    }
                />
            )}

            {openGraphEnabled
            && siteName && (
                <meta
                    head-key="og:site_name"
                    property="og:site_name"
                    content={
                        siteName
                    }
                />
            )}

            {openGraphEnabled
            && ogImageUrl && (
                <meta
                    head-key="og:image"
                    property="og:image"
                    content={
                        ogImageUrl
                    }
                />
            )}

            {openGraphEnabled
            && ogImageUrl
            && ogImageUrl.startsWith(
                'https://',
            ) && (
                <meta
                    head-key="og:image:secure_url"
                    property="og:image:secure_url"
                    content={
                        ogImageUrl
                    }
                />
            )}

            {openGraphEnabled
            && ogImageUrl
            && ogImageAlt && (
                <meta
                    head-key="og:image:alt"
                    property="og:image:alt"
                    content={
                        ogImageAlt
                    }
                />
            )}

            {openGraphEnabled
            && ogImageUrl
            && ogImageWidth !== null && (
                <meta
                    head-key="og:image:width"
                    property="og:image:width"
                    content={
                        String(
                            ogImageWidth,
                        )
                    }
                />
            )}

            {openGraphEnabled
            && ogImageUrl
            && ogImageHeight !== null && (
                <meta
                    head-key="og:image:height"
                    property="og:image:height"
                    content={
                        String(
                            ogImageHeight,
                        )
                    }
                />
            )}

            {twitterCardsEnabled && (
                <meta
                    head-key="twitter:card"
                    name="twitter:card"
                    content={
                        twitterCard
                    }
                />
            )}

            {twitterCardsEnabled && (
                <meta
                    head-key="twitter:title"
                    name="twitter:title"
                    content={
                        twitterTitle
                    }
                />
            )}

            {twitterCardsEnabled
            && twitterDescription && (
                <meta
                    head-key="twitter:description"
                    name="twitter:description"
                    content={
                        twitterDescription
                    }
                />
            )}

            {twitterCardsEnabled
            && twitterImageUrl && (
                <meta
                    head-key="twitter:image"
                    name="twitter:image"
                    content={
                        twitterImageUrl
                    }
                />
            )}

            {twitterCardsEnabled
            && twitterImageUrl
            && twitterImageAlt && (
                <meta
                    head-key="twitter:image:alt"
                    name="twitter:image:alt"
                    content={
                        twitterImageAlt
                    }
                />
            )}

            {jsonLd && (
                <script
                    head-key="structured-data"
                    type="application/ld+json"
                >
                    {JSON.stringify(
                        jsonLd,
                    )}
                </script>
            )}
        </Head>
    )
}
