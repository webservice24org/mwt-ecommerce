import { Link, router } from '@inertiajs/react'
import { ShoppingCart } from 'lucide-react'
import { useRef, useState } from 'react'

import { addToCart } from '@/lib/addToCart'

interface Props {
    productId: number
    variantId?: number | null
    available: boolean
}

export const purchaseButtonClass = 'inline-flex min-h-11 min-w-0 flex-1 basis-[6.5rem] items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-center text-xs font-semibold leading-5 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 sm:text-sm'
export const addButtonClass = `mwt-card-add-button ${purchaseButtonClass} border border-indigo-600 text-indigo-700 hover:bg-indigo-50`
export const orderButtonClass = `mwt-card-order-button ${purchaseButtonClass} bg-indigo-600 text-white hover:bg-indigo-700`

export default function ProductPurchaseActions({ productId, variantId = null, available }: Props) {
    const lock = useRef(false)
    const [pending, setPending] = useState<'add' | 'order' | null>(null)
    const [error, setError] = useState<string | null>(null)
    const [added, setAdded] = useState(false)

    const purchase = async (intent: 'add' | 'order') => {
        if (!available || lock.current) return

        lock.current = true
        setPending(intent)
        setError(null)
        setAdded(false)

        try {
            const cart = await addToCart({ productId, variantId, quantity: 1, openDrawer: intent === 'add' })
            if (!cart.items.some(item => item.product_id === productId && item.variant_id === variantId)) {
                throw new Error('This product is no longer available. Please refresh and try again.')
            }
            setAdded(true)
            if (intent === 'order') {
                router.visit('/cart', {
                    onFinish: () => {
                        lock.current = false
                        setPending(null)
                    },
                })
                return
            }
        } catch (cause: unknown) {
            setError(cause instanceof Error ? cause.message : 'Unable to add product to cart.')
        }
        lock.current = false
        setPending(null)
    }

    return (
        <div className="mt-4 min-w-0">
            <div className="flex min-w-0 flex-wrap gap-2">
                <button type="button" className={addButtonClass} disabled={!available || pending !== null} onClick={() => void purchase('add')}>
                    <ShoppingCart className="size-4 shrink-0" aria-hidden="true" />
                    {pending === 'add' ? 'Adding…' : 'Add to Cart'}
                </button>
                <button type="button" className={orderButtonClass} disabled={!available || pending !== null} onClick={() => void purchase('order')}>
                    {pending === 'order' ? 'Opening cart…' : 'Order Now'}
                </button>
            </div>
            {error && <p role="alert" className="mt-2 break-words text-xs text-red-700">{error}</p>}
            {added && !error && <p role="status" className="mt-2 text-xs text-emerald-700">Added to cart. <Link href="/cart" className="underline">View cart</Link></p>}
        </div>
    )
}
