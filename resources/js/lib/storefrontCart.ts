import type { StorefrontCart } from '@/types/storefront'

export const STOREFRONT_CART_CHANGED = 'storefront:cart-changed'

export interface StorefrontCartChangedDetail {
    cart: StorefrontCart
}

export function dispatchCartChanged(cart: StorefrontCart): void {
    window.dispatchEvent(
        new CustomEvent<StorefrontCartChangedDetail>(STOREFRONT_CART_CHANGED, {
            detail: {
                cart,
            },
        }),
    )
}
