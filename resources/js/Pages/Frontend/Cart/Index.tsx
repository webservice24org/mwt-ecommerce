import { Head, Link, router, usePage } from '@inertiajs/react'
import { ArrowLeft, ArrowRight, Minus, Plus, ShoppingBag, Trash2 } from 'lucide-react'
import { type FormEvent, useEffect, useRef, useState } from 'react'

import FrontendLayout from '@/Layouts/Frontend/FrontendLayout'
import { jsonRequestHeaders } from '@/lib/csrf'
import { formatMoney } from '@/lib/money'
import { dispatchCartChanged, STOREFRONT_CART_CHANGED, type StorefrontCartChangedDetail } from '@/lib/storefrontCart'
import type { PageProps } from '@/types'
import type { StorefrontCart, StorefrontCartItem } from '@/types/storefront'

type CartPageProps = PageProps<{
    storefrontCart: StorefrontCart
    flash?: { success?: string | null; error?: string | null }
}>

const controlClass = 'inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg border border-neutral-200 text-neutral-700 transition hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50'
const primaryClass = 'inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50'

export default function Index() {
    const { storefrontCart, flash } = usePage<CartPageProps>().props
    const [cart, setCart] = useState(storefrontCart)
    const [busy, setBusy] = useState(false)
    const lock = useRef(false)
    const [code, setCode] = useState('')
    const [error, setError] = useState<string | null>(null)
    const [status, setStatus] = useState<string | null>(null)

    useEffect(() => {
        setCart(storefrontCart)
    }, [storefrontCart])

    useEffect(() => {
        const handleCartChanged = (event: Event) => {
            setCart((event as CustomEvent<StorefrontCartChangedDetail>).detail.cart)
        }
        window.addEventListener(STOREFRONT_CART_CHANGED, handleCartChanged)
        return () => window.removeEventListener(STOREFRONT_CART_CHANGED, handleCartChanged)
    }, [])

    const start = () => {
        if (lock.current) return false
        lock.current = true
        setBusy(true)
        setError(null)
        setStatus(null)
        return true
    }

    const finish = () => {
        lock.current = false
        setBusy(false)
    }

    const updateItem = async (item: StorefrontCartItem, quantity: number | null) => {
        if (!start()) return
        try {
            const response = await fetch(`/cart/items/${encodeURIComponent(item.key)}`, {
                method: quantity === null ? 'DELETE' : 'PATCH',
                credentials: 'same-origin',
                headers: jsonRequestHeaders(),
                ...(quantity === null ? {} : { body: JSON.stringify({ quantity }) }),
            })
            if (!response.ok) {
                const body = await response.json().catch(() => null) as { message?: string } | null
                throw new Error(body?.message ?? 'Unable to update your cart.')
            }
            const next = await response.json() as StorefrontCart
            setCart(next)
            dispatchCartChanged(next)
            setStatus(quantity === null ? 'Item removed.' : 'Quantity updated.')
        } catch (cause: unknown) {
            setError(cause instanceof Error ? cause.message : 'Unable to update your cart.')
        } finally {
            finish()
        }
    }

    const changePromo = (remove: boolean) => {
        if (!remove && code.trim() === '') {
            setError('Enter a promo code.')
            return
        }
        if (!start()) return
        const options = {
            preserveScroll: true,
            preserveState: true,
            onError: (errors: Record<string, string>) => setError(errors.promo_code ?? errors.code ?? 'Unable to update promo code.'),
            onSuccess: (page: { props: Record<string, unknown> }) => {
                const next = page.props.storefrontCart as StorefrontCart
                if (next) {
                    setCart(next)
                    dispatchCartChanged(next)
                    setCode('')
                    setStatus(remove ? 'Promo code removed.' : 'Promo code applied.')
                }
            },
            onFinish: finish,
        }
        if (remove) {
            router.delete('/cart/promo-code', options)
        } else {
            router.post('/cart/promo-code', { code: code.trim() }, options)
        }
    }

    const applyPromo = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()
        changePromo(false)
    }

    return (
        <FrontendLayout>
            <Head title="Shopping cart"><meta head-key="robots" name="robots" content="noindex, nofollow" /></Head>
            <main className="mx-auto w-full min-w-0 max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
                <Link href="/products" className="inline-flex items-center gap-2 rounded text-sm font-medium text-indigo-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                    <ArrowLeft className="size-4" aria-hidden="true" /> Continue shopping
                </Link>
                <div className="mb-8 mt-4 flex flex-wrap items-end justify-between gap-2">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight text-neutral-950 sm:text-4xl">Shopping cart</h1>
                        <p className="mt-2 text-sm text-neutral-600">Review your items before checkout.</p>
                    </div>
                    <p className="text-sm font-semibold text-neutral-600" aria-live="polite">{cart.item_count} {cart.item_count === 1 ? 'item' : 'items'}</p>
                </div>
                {(error || flash?.error) && <p role="alert" className="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">{error || flash?.error}</p>}
                {status && <p role="status" className="mb-4 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800">{status}</p>}
                {cart.items.length === 0 ? (
                    <section className="rounded-2xl border border-dashed border-neutral-300 px-6 py-16 text-center">
                        <ShoppingBag className="mx-auto size-12 text-neutral-400" aria-hidden="true" />
                        <h2 className="mt-5 text-xl font-bold">Your cart is empty</h2>
                        <p className="mt-2 text-sm text-neutral-600">Find something you love and add it to your cart.</p>
                        <Link href="/products" className={`${primaryClass} mt-6`}>Browse products <ArrowRight className="size-4" aria-hidden="true" /></Link>
                    </section>
                ) : (
                    <div className="grid min-w-0 gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start xl:grid-cols-[minmax(0,1fr)_25rem]">
                        <section aria-label="Cart items" className="min-w-0 rounded-2xl border border-neutral-200 bg-white">
                            <div className="border-b border-neutral-200 px-4 py-4 sm:px-6"><h2 className="font-semibold">Your items</h2></div>
                            <ul className="divide-y divide-neutral-200">
                                {cart.items.map(item => (
                                    <li key={item.key} className="flex min-w-0 flex-wrap gap-4 p-4 sm:flex-nowrap sm:p-6">
                                        <Link href={`/products/${item.slug}`} className="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-neutral-100 bg-neutral-50 sm:size-24" aria-label={`View ${item.name}`}>
                                            {item.image_url ? <img src={item.image_url} alt="" className="h-full w-full object-contain" loading="lazy" /> : <ShoppingBag className="size-6 text-neutral-400" aria-hidden="true" />}
                                        </Link>
                                        <div className="min-w-0 flex-1">
                                            <Link href={`/products/${item.slug}`} className="break-words text-sm font-semibold hover:underline sm:text-base">{item.name}</Link>
                                            {item.variant_name && <p className="mt-1 break-words text-xs text-neutral-500">{item.variant_name}</p>}
                                            <p className="mt-2 text-sm text-neutral-600">{formatMoney(item.unit_price)} each</p>
                                            <div className="mt-3 flex flex-wrap items-center gap-2">
                                                <button type="button" aria-label={`Decrease quantity of ${item.name}`} className={controlClass} disabled={busy || item.quantity <= 1} onClick={() => void updateItem(item, item.quantity - 1)}><Minus className="size-4" aria-hidden="true" /></button>
                                                <span className="min-w-8 text-center text-sm font-semibold tabular-nums" aria-label="Quantity">{item.quantity}</span>
                                                <button type="button" aria-label={`Increase quantity of ${item.name}`} className={controlClass} disabled={busy || item.quantity >= 99} onClick={() => void updateItem(item, item.quantity + 1)}><Plus className="size-4" aria-hidden="true" /></button>
                                                <button type="button" aria-label={`Remove ${item.name}`} disabled={busy} className="inline-flex min-h-11 items-center gap-1 rounded px-2 text-xs font-medium text-red-700 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 disabled:opacity-50" onClick={() => void updateItem(item, null)}><Trash2 className="size-4" aria-hidden="true" /> Remove</button>
                                            </div>
                                        </div>
                                        <p className="ml-auto self-start break-words text-sm font-bold tabular-nums sm:text-base">{formatMoney(item.line_total)}</p>
                                    </li>
                                ))}
                            </ul>
                        </section>
                        <aside className="min-w-0 space-y-4 lg:sticky lg:top-6" aria-label="Order summary">
                            <section className="rounded-2xl border border-neutral-200 bg-white p-5">
                                <h2 className="font-semibold">Promo code</h2>
                                {cart.promo ? (
                                    <div className="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">
                                        <span className="min-w-0 break-all font-medium">{cart.promo.code}</span>
                                        <button type="button" disabled={busy} onClick={() => changePromo(true)} className="min-h-11 rounded px-2 font-semibold underline disabled:opacity-50">Remove</button>
                                    </div>
                                ) : (
                                    <form onSubmit={applyPromo} className="mt-3 flex flex-wrap gap-2">
                                        <label htmlFor="cart-page-promo" className="sr-only">Promo code</label>
                                        <input id="cart-page-promo" value={code} onChange={event => setCode(event.target.value)} maxLength={100} autoComplete="off" disabled={busy} placeholder="Enter promo code" className="min-h-11 min-w-0 flex-1 rounded-lg border border-neutral-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
                                        <button type="submit" disabled={busy || !code.trim()} className="min-h-11 rounded-lg bg-neutral-900 px-4 text-sm font-semibold text-white disabled:opacity-50">Apply</button>
                                    </form>
                                )}
                            </section>
                            <section className="rounded-2xl border border-neutral-200 bg-neutral-50 p-5">
                                <h2 className="text-lg font-bold">Order summary</h2>
                                <dl className="mt-5 space-y-3 text-sm">
                                    <div className="flex justify-between gap-3"><dt className="text-neutral-600">Subtotal</dt><dd className="font-semibold tabular-nums">{formatMoney(cart.subtotal)}</dd></div>
                                    {cart.discount_total > 0 && <div className="flex justify-between gap-3 text-emerald-700"><dt>Discount</dt><dd className="font-semibold tabular-nums">−{formatMoney(cart.discount_total)}</dd></div>}
                                    <div className="flex justify-between gap-3 border-t border-neutral-200 pt-4 text-lg font-bold"><dt>Cart total</dt><dd className="tabular-nums">{formatMoney(cart.grand_total)}</dd></div>
                                </dl>
                                <p className="mt-4 text-xs leading-5 text-neutral-600">Shipping and taxes are calculated during checkout. Prices and discounts are verified by the server.</p>
                                {busy ? <button disabled className={`${primaryClass} mt-5 w-full`}>Updating cart…</button> : <Link href="/checkout" className={`${primaryClass} mt-5 w-full`}>Proceed to checkout <ArrowRight className="size-4" aria-hidden="true" /></Link>}
                            </section>
                        </aside>
                    </div>
                )}
            </main>
        </FrontendLayout>
    )
}
