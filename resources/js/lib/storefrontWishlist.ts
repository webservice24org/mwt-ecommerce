export const STOREFRONT_WISHLIST_CHANGED = 'storefront:wishlist-changed'

export interface StorefrontWishlistChangedDetail {
    count: number
}

export function dispatchWishlistChanged(count: number): void {
    window.dispatchEvent(
        new CustomEvent<StorefrontWishlistChangedDetail>(STOREFRONT_WISHLIST_CHANGED, {
            detail: {
                count,
            },
        }),
    )
}
