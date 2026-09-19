import ProductVideoViewer from '@/Components/Frontend/Catalog/ProductDetails/ProductVideoViewer'
import type { StorefrontMedia } from '@/types/storefront-media'
import { useState } from 'react'

interface ProductGalleryViewerProps {
    media: StorefrontMedia
    productName: string
}

export default function ProductGalleryViewer({ media, productName }: ProductGalleryViewerProps) {
    if (media.kind === 'video') {
        return <ProductVideoViewer video={media.video} productName={productName} />
    }

    return (
        <GalleryImage
            key={media.image.id}
            src={media.image.url}
            alt={media.image.alt || productName}
        />
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
            <div className="flex aspect-square items-center justify-center rounded-2xl bg-neutral-100 p-6 text-center text-sm text-neutral-500">
                Image unavailable
            </div>
        )
    }

    return (
        <div className="overflow-hidden rounded-2xl bg-neutral-100">
            <img
                src={src}
                alt={alt}
                onError={() => setFailed(true)}
                decoding="async"
                className="aspect-square h-full w-full object-contain"
            />
        </div>
    )
}
