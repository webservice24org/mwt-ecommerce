import type { StorefrontImage, StorefrontVideo } from '@/types/storefront'

export interface StorefrontImageMedia {
    kind: 'image'
    key: string
    image: StorefrontImage
}

export interface StorefrontVideoMedia {
    kind: 'video'
    key: string
    video: StorefrontVideo
}

export type StorefrontMedia = StorefrontImageMedia | StorefrontVideoMedia
