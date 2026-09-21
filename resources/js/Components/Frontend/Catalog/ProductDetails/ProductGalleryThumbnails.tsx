import type { StorefrontMedia } from '@/types/storefront-media'
import { Play } from 'lucide-react'

interface ProductGalleryThumbnailsProps {
    media: StorefrontMedia[]
    selectedKey: string
    productName: string
    onSelect: (key: string) => void
}

export default function ProductGalleryThumbnails({
    media,
    selectedKey,
    productName,
    onSelect,
}: ProductGalleryThumbnailsProps) {
    return (
        <div
            className="flex w-full min-w-0 max-w-full gap-3 overflow-x-auto overscroll-x-contain pb-2"
            role="group"
            aria-label="Product media thumbnails"
        >
            {media.map((item, index) => {
                const selected = item.key === selectedKey

                return (
                    <button
                        key={item.key}
                        type="button"
                        onClick={() => onSelect(item.key)}
                        aria-pressed={selected}
                        aria-label={
                            item.kind === 'image'
                                ? `View ${productName} image ${index + 1}`
                                : `Play ${productName} video`
                        }
                        className={[
                            'relative size-20 shrink-0 overflow-hidden rounded-xl border bg-neutral-100 transition',
                            'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 focus-visible:ring-offset-2',
                            selected
                                ? 'border-neutral-950 ring-1 ring-neutral-950'
                                : 'border-neutral-200 hover:border-neutral-400',
                        ].join(' ')}
                    >
                        {item.kind === 'image' ? (
                            <img
                                src={item.image.url}
                                alt=""
                                loading="lazy"
                                decoding="async"
                                className="h-full w-full object-cover"
                            />
                        ) : (
                            <span className="flex h-full w-full items-center justify-center">
                                <Play className="size-6" aria-hidden="true" />
                            </span>
                        )}
                    </button>
                )
            })}
        </div>
    )
}
