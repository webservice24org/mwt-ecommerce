import ProductVideoViewer from '@/Components/Frontend/Catalog/ProductDetails/ProductVideoViewer'
import type { StorefrontMedia } from '@/types/storefront-media'
import { useState } from 'react'

interface ProductGalleryViewerProps {
    media: StorefrontMedia
    productName: string
    imagePosition?: number
}

export default function ProductGalleryViewer({
    media,
    productName,
    imagePosition,
}: ProductGalleryViewerProps) {
    if (media.kind === 'video') {
        return <ProductVideoViewer video={media.video} productName={productName} />
    }

    const customAlt = media.image.alt?.trim()

    const fallbackAlt =
        imagePosition !== undefined ? `${productName} — image ${imagePosition}` : productName

    return (
        <GalleryImage key={media.image.id} src={media.image.url} alt={customAlt || fallbackAlt} />
    )
}

interface GalleryImageProps {
    src: string
    alt: string
}

function GalleryImage({ src, alt }: GalleryImageProps) {
    const [failed, setFailed] = useState(false)

    if (failed) {
        return (
            <div
                className="flex aspect-square w-full min-w-0 items-center justify-center overflow-hidden rounded-2xl bg-neutral-100 p-6 text-center text-sm text-neutral-500"
                role="img"
                aria-label={`${alt} — image unavailable`}
            >
                <span className="max-w-full break-words" aria-hidden="true">
                    Image unavailable
                </span>
            </div>
        )
    }

    return (
        <div className="aspect-square w-full min-w-0 overflow-hidden rounded-2xl bg-neutral-100">
            <img
                src={src}
                alt={alt}
                onError={() => setFailed(true)}
                decoding="async"
                fetchPriority="high"
                className="h-full w-full object-contain"
            />
        </div>
    )
}
