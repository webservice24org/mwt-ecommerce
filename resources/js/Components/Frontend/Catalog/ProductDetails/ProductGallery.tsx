import ProductGalleryThumbnails from '@/Components/Frontend/Catalog/ProductDetails/ProductGalleryThumbnails'
import ProductGalleryViewer from '@/Components/Frontend/Catalog/ProductDetails/ProductGalleryViewer'
import { buildStorefrontMedia } from '@/lib/storefrontMedia'
import type { StorefrontImage, StorefrontVideo } from '@/types/storefront'
import { useMemo, useState } from 'react'

interface ProductGalleryProps {
    images: StorefrontImage[]
    video: StorefrontVideo | null
    productName: string
}

export default function ProductGallery({ images, video, productName }: ProductGalleryProps) {
    const media = useMemo(() => buildStorefrontMedia(images, video), [images, video])

    const [selectedKey, setSelectedKey] = useState<string | null>(() => media[0]?.key ?? null)

    const selectedMedia = media.find((item) => item.key === selectedKey) ?? media[0] ?? null

    const selectedImagePosition =
        selectedMedia.kind === 'image'
            ? media
                  .filter((item) => item.kind === 'image')
                  .findIndex((item) => item.key === selectedMedia.key) + 1
            : undefined

    if (selectedMedia === null) {
        return (
            <div className="flex aspect-square min-w-0 items-center justify-center overflow-hidden rounded-2xl bg-neutral-100 p-6 text-center text-sm text-neutral-500">
                <span className="max-w-full break-words">No product media available</span>
            </div>
        )
    }

    return (
        <section className="min-w-0 space-y-4" aria-label={`${productName} media gallery`}>
            <ProductGalleryViewer
                media={selectedMedia}
                productName={productName}
                imagePosition={selectedImagePosition}
            />

            {media.length > 1 && (
                <ProductGalleryThumbnails
                    media={media}
                    selectedKey={selectedMedia.key}
                    productName={productName}
                    onSelect={setSelectedKey}
                />
            )}
        </section>
    )
}
