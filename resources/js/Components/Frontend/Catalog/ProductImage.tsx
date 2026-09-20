import type { StorefrontImage } from '@/types/storefront'
import { useState } from 'react'

interface ProductImageProps {
    image: StorefrontImage | null
    alt: string
    className?: string
    sizes?: string
    priority?: boolean
}

export default function ProductImage({
    image,
    alt,
    className = '',
    sizes = '(min-width: 1024px) 25vw, (min-width: 768px) 33vw, 50vw',
    priority = false,
}: ProductImageProps) {
    const [failedUrl, setFailedUrl] = useState<string | null>(null)

    const hasValidImage = image !== null && image.url.trim() !== '' && failedUrl !== image.url

    if (!hasValidImage) {
        return (
            <div
                className={`flex aspect-square items-center justify-center bg-neutral-100 ${className}`}
                role="img"
                aria-label={alt}
            >
                <span className="px-4 text-center text-xs text-neutral-400">Image unavailable</span>
            </div>
        )
    }

    return (
        <img
            src={image.url}
            alt={image.alt?.trim() || alt}
            width={image.width ?? undefined}
            height={image.height ?? undefined}
            sizes={sizes}
            loading={priority ? 'eager' : 'lazy'}
            decoding={priority ? 'sync' : 'async'}
            fetchPriority={priority ? 'high' : 'auto'}
            className={`aspect-square object-cover ${className}`}
            onError={() => setFailedUrl(image.url)}
        />
    )
}
