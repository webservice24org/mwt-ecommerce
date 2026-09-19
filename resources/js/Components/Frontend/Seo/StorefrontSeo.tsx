import { storefrontAbsoluteUrl } from '@/lib/storefrontUrl'
import { Head } from '@inertiajs/react'

type JsonPrimitive = string | number | boolean | null

type JsonValue = JsonPrimitive | JsonValue[] | { [key: string]: JsonValue }

interface StorefrontSeoProps {
    title: string
    description?: string | null
    canonicalPath: string
    image?: string | null
    type?: 'website' | 'product'
    jsonLd?: JsonValue
}

export default function StorefrontSeo({
    title,
    description = null,
    canonicalPath,
    image = null,
    type = 'website',
    jsonLd = null,
}: StorefrontSeoProps) {
    const canonicalUrl = storefrontAbsoluteUrl(canonicalPath)

    const imageUrl = image ? storefrontAbsoluteUrl(image) : null

    return (
        <Head title={title}>
            {description && (
                <meta head-key="description" name="description" content={description} />
            )}

            <link head-key="canonical" rel="canonical" href={canonicalUrl} />

            <meta head-key="og:title" property="og:title" content={title} />

            {description && (
                <meta head-key="og:description" property="og:description" content={description} />
            )}

            <meta head-key="og:type" property="og:type" content={type} />

            <meta head-key="og:url" property="og:url" content={canonicalUrl} />

            {imageUrl && <meta head-key="og:image" property="og:image" content={imageUrl} />}

            <meta
                head-key="twitter:card"
                name="twitter:card"
                content={imageUrl ? 'summary_large_image' : 'summary'}
            />

            <meta head-key="twitter:title" name="twitter:title" content={title} />

            {description && (
                <meta
                    head-key="twitter:description"
                    name="twitter:description"
                    content={description}
                />
            )}

            {imageUrl && <meta head-key="twitter:image" name="twitter:image" content={imageUrl} />}

            {jsonLd && (
                <script head-key="structured-data" type="application/ld+json">
                    {JSON.stringify(jsonLd)}
                </script>
            )}
        </Head>
    )
}
