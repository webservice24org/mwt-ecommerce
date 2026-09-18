import type { StorefrontImage } from '@/types/storefront'
import { ImageIcon } from 'lucide-react'
import { useState } from 'react'

interface ProductImageProps {
    image: StorefrontImage | null
    productName: string
    className?: string
    eager?: boolean
    sizes?: string
}

export default function ProductImage({
    image,
    productName,
    className = '',
    eager = false,
    sizes,
}: ProductImageProps) {
    const [failedUrl, setFailedUrl] = useState<string | null>(null)

    const showImage = image !== null && image.url !== failedUrl

    return (
        <div
            className={`relative aspect-square w-full overflow-hidden bg-neutral-100 ${className}`}
        >
            {showImage ? (
                <img
                    src={image.url}
                    alt={image.alt?.trim() || productName}
                    width={image.width ?? undefined}
                    height={image.height ?? undefined}
                    loading={eager ? 'eager' : 'lazy'}
                    fetchPriority={eager ? 'high' : 'auto'}
                    decoding="async"
                    sizes={sizes}
                    onError={() => setFailedUrl(image.url)}
                    className="h-full w-full object-cover"
                />
            ) : (
                <div
                    className="flex h-full w-full flex-col items-center justify-center gap-2 p-6 text-center text-neutral-400"
                    role="img"
                    aria-label={`${productName} image unavailable`}
                >
                    <ImageIcon className="h-8 w-8" aria-hidden="true" />

                    <span className="text-xs">Image unavailable</span>
                </div>
            )}
        </div>
    )
}
