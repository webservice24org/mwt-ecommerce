import type { StorefrontImage, StorefrontVideo } from '@/types/storefront'
import type { StorefrontMedia } from '@/types/storefront-media'

export function buildStorefrontMedia(
    images: StorefrontImage[],
    video: StorefrontVideo | null,
): StorefrontMedia[] {
    const media: StorefrontMedia[] = images.map((image) => ({
        kind: 'image',
        key: `image-${image.id}`,
        image,
    }))

    if (video?.url) {
        media.push({
            kind: 'video',
            key: 'product-video',
            video,
        })
    }

    return media
}
