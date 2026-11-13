export const STOREFRONT_WISHLIST_CHANGED =
    'storefront:wishlist-changed'

export type StorefrontWishlistChange =
    | {
          type: 'product'
          productId: number
          active: boolean
          count: number
      }
    | {
          type: 'reset'
          productIds: number[]
          count: number
      }

export type StorefrontWishlistChangedDetail =
    StorefrontWishlistChange

export function dispatchWishlistProductChanged(
    productId: number,
    active: boolean,
    count: number,
): void {
    window.dispatchEvent(
        new CustomEvent<StorefrontWishlistChangedDetail>(
            STOREFRONT_WISHLIST_CHANGED,
            {
                detail: {
                    type: 'product',
                    productId,
                    active,
                    count,
                },
            },
        ),
    )
}

export function dispatchWishlistReset(
    productIds: number[],
    count: number,
): void {
    window.dispatchEvent(
        new CustomEvent<StorefrontWishlistChangedDetail>(
            STOREFRONT_WISHLIST_CHANGED,
            {
                detail: {
                    type: 'reset',
                    productIds,
                    count,
                },
            },
        ),
    )
}