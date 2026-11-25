import { jsonRequestHeaders } from '@/lib/csrf'
import { dispatchCartChanged } from '@/lib/storefrontCart'
import type { StorefrontCart } from '@/types/storefront'

interface AddToCartInput {
    productId: number
    variantId?: number | null
    quantity?: number
    openDrawer?: boolean
}

export async function addToCart({
    productId,
    variantId = null,
    quantity = 1,
    openDrawer = false,
}: AddToCartInput): Promise<StorefrontCart> {
    const response = await fetch('/cart/items', {
        method: 'POST',

        credentials: 'same-origin',

        headers: jsonRequestHeaders(),

        body: JSON.stringify({
            product_id: productId,
            variant_id: variantId,
            quantity,
        }),
    })

    if (!response.ok) {
        const message = await responseMessage(response)

        throw new Error(message || 'Unable to add product to cart.')
    }

    const cart = (await response.json()) as StorefrontCart

    const itemAdded = cart.items.some(item => item.product_id === productId && item.variant_id === variantId)
    dispatchCartChanged(cart, openDrawer && itemAdded)

    return cart
}

async function responseMessage(response: Response): Promise<string | null> {
    try {
        const payload = (await response.json()) as {
            message?: string
        }

        return payload.message ?? null
    } catch {
        return null
    }
}
