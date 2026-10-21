import { Head, Link, router, useForm } from '@inertiajs/react'
import { useState } from 'react'
import type { FormEvent } from 'react'

import AddressForm from '@/Components/Customer/AddressForm'
import FrontendLayout from '@/Layouts/Frontend/FrontendLayout'
import { formatMoney } from '@/lib/money'
import type { CustomerAddress, CustomerAddressFormData } from '@/types/customer-address'

type CheckoutPaymentMethod = 'cash_on_delivery'

interface CheckoutPayment {
    method: CheckoutPaymentMethod
    label: string
    description: string
}

interface CheckoutCartItem {
    key: string
    product_id: number
    variant_id: number | null
    quantity: number
    name: string
    slug: string
    variant_name: string | null
    image_url: string | null
    unit_price: number
    line_total: number
}

interface CheckoutCart {
    items: CheckoutCartItem[]
    item_count: number
    subtotal: number
}

interface CheckoutTotals {
    subtotal: number
    discount_total: number
    delivery_total: number
    tax_total: number
    grand_total: number
}

interface Props {
    cart: CheckoutCart
    totals: CheckoutTotals
    addresses: CustomerAddress[]
    selectedAddressId: number | null
    addressDefaults: {
        country_code: string
    }
    payment: CheckoutPayment
}

export default function Index({
    cart,
    totals,
    addresses,
    selectedAddressId,
    addressDefaults,
    payment,
}: Props) {
    const [showAddressForm, setShowAddressForm] = useState(addresses.length === 0)

    const addressForm = useForm<CustomerAddressFormData>({
        label: '',
        recipient_name: '',
        phone: '',
        address_line_1: '',
        address_line_2: '',
        city: '',
        state_or_region: '',
        postal_code: '',
        country_code: addressDefaults.country_code,
        is_default: addresses.length === 0,
    })

    /*
     * There are deliberately no checkout totals,
     * payment values or address IDs in this form.
     *
     * Laravel resolves all final checkout state
     * from trusted server-side sources.
     */
    const checkoutForm = useForm<Record<string, never>>({})

    const selectAddress = (addressId: number) => {
        if (addressId === selectedAddressId) {
            return
        }

        router.post(
            route('checkout.address.select'),
            {
                address_id: addressId,
            },
            {
                preserveScroll: true,
            },
        )
    }

    const createAddress = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        addressForm.post(route('checkout.addresses.store'), {
            preserveScroll: true,

            onSuccess: () => {
                addressForm.reset()

                setShowAddressForm(false)
            },
        })
    }

    const placeOrder = () => {
        if (selectedAddressId === null) {
            return
        }

        checkoutForm.post(route('checkout.order.store'), {
            preserveScroll: false,
        })
    }

    return (
        <FrontendLayout>
            <Head title="Checkout" />

            <main className="bg-neutral-50 py-8 sm:py-10 lg:py-12">
                <div className="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-8">
                        <p className="text-sm font-semibold uppercase tracking-wider text-neutral-500">
                            Secure checkout
                        </p>

                        <h1 className="mt-2 text-3xl font-bold tracking-tight text-neutral-950 sm:text-4xl">
                            Checkout
                        </h1>

                        <p className="mt-2 max-w-2xl text-sm leading-6 text-neutral-600">
                            Confirm your delivery address, payment method, and order before
                            continuing.
                        </p>
                    </div>

                    <div className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_380px]">
                        <div className="min-w-0 space-y-6">
                            <section className="rounded-2xl border border-neutral-200 bg-white shadow-sm">
                                <div className="flex flex-col gap-3 border-b border-neutral-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                                    <div>
                                        <h2 className="text-lg font-bold text-neutral-950">
                                            Delivery address
                                        </h2>

                                        <p className="mt-1 text-sm text-neutral-500">
                                            Choose where this order should be delivered.
                                        </p>
                                    </div>

                                    {addresses.length > 0 && (
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setShowAddressForm((current) => !current)
                                            }
                                            className="inline-flex min-h-10 items-center justify-center rounded-lg border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50"
                                        >
                                            {showAddressForm ? 'Cancel' : 'Add new address'}
                                        </button>
                                    )}
                                </div>

                                {addresses.length > 0 ? (
                                    <div className="grid gap-3 p-5 sm:grid-cols-2 sm:p-6">
                                        {addresses.map((address) => {
                                            const selected = address.id === selectedAddressId

                                            return (
                                                <button
                                                    key={address.id}
                                                    type="button"
                                                    onClick={() => selectAddress(address.id)}
                                                    className={`relative min-w-0 rounded-xl border p-4 text-left transition ${
                                                        selected
                                                            ? 'border-neutral-950 bg-neutral-50 ring-1 ring-neutral-950'
                                                            : 'border-neutral-200 bg-white hover:border-neutral-400'
                                                    }`}
                                                >
                                                    <div className="flex items-start justify-between gap-3">
                                                        <div className="min-w-0">
                                                            <div className="flex flex-wrap items-center gap-2">
                                                                <p className="font-semibold text-neutral-950">
                                                                    {address.label ??
                                                                        'Delivery address'}
                                                                </p>

                                                                {address.is_default && (
                                                                    <span className="rounded-full bg-neutral-100 px-2 py-1 text-xs font-semibold text-neutral-600">
                                                                        Default
                                                                    </span>
                                                                )}

                                                                {selected && (
                                                                    <span className="rounded-full bg-neutral-950 px-2 py-1 text-xs font-semibold text-white">
                                                                        Selected
                                                                    </span>
                                                                )}
                                                            </div>

                                                            <p className="mt-3 text-sm font-medium text-neutral-900">
                                                                {address.recipient_name}
                                                            </p>

                                                            <address className="mt-1 not-italic text-sm leading-6 text-neutral-600">
                                                                <span className="block">
                                                                    {address.address_line_1}
                                                                </span>

                                                                {address.address_line_2 && (
                                                                    <span className="block">
                                                                        {address.address_line_2}
                                                                    </span>
                                                                )}

                                                                <span className="block">
                                                                    {address.city}

                                                                    {address.state_or_region
                                                                        ? `, ${address.state_or_region}`
                                                                        : ''}
                                                                </span>

                                                                {address.postal_code && (
                                                                    <span className="block">
                                                                        {address.postal_code}
                                                                    </span>
                                                                )}

                                                                <span className="block">
                                                                    {address.phone}
                                                                </span>
                                                            </address>
                                                        </div>

                                                        <span
                                                            aria-hidden="true"
                                                            className={`mt-1 flex size-5 shrink-0 items-center justify-center rounded-full border ${
                                                                selected
                                                                    ? 'border-neutral-950 bg-neutral-950'
                                                                    : 'border-neutral-300'
                                                            }`}
                                                        >
                                                            {selected && (
                                                                <span className="size-2 rounded-full bg-white" />
                                                            )}
                                                        </span>
                                                    </div>
                                                </button>
                                            )
                                        })}
                                    </div>
                                ) : (
                                    <div className="px-5 pt-5 sm:px-6 sm:pt-6">
                                        <div className="rounded-xl bg-neutral-50 p-4 text-sm leading-6 text-neutral-600">
                                            You do not have a saved delivery address yet. Add one to
                                            continue checkout.
                                        </div>
                                    </div>
                                )}

                                {showAddressForm && (
                                    <div className="border-t border-neutral-200 p-5 sm:p-6">
                                        <form onSubmit={createAddress}>
                                            <AddressForm
                                                data={addressForm.data}
                                                errors={addressForm.errors}
                                                processing={addressForm.processing}
                                                submitLabel="Save and use this address"
                                                onChange={(field, value) => {
                                                    addressForm.setData({
                                                        ...addressForm.data,
                                                        [field]: value,
                                                    })
                                                }}
                                            />
                                        </form>
                                    </div>
                                )}

                                {checkoutForm.errors.address_id && (
                                    <div className="border-t border-red-100 bg-red-50 px-5 py-3 text-sm text-red-700 sm:px-6">
                                        {checkoutForm.errors.address_id}
                                    </div>
                                )}
                            </section>

                            <section className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
                                <div className="border-b border-neutral-200 px-5 py-4 sm:px-6">
                                    <h2 className="text-lg font-bold text-neutral-950">
                                        Payment method
                                    </h2>

                                    <p className="mt-1 text-sm text-neutral-500">
                                        Choose how you would like to pay for this order.
                                    </p>
                                </div>

                                <div className="p-5 sm:p-6">
                                    <label className="flex cursor-default items-start gap-4 rounded-xl border border-neutral-950 bg-neutral-50 p-4 ring-1 ring-neutral-950">
                                        <input
                                            type="radio"
                                            name="payment_method"
                                            value={payment.method}
                                            checked
                                            readOnly
                                            className="mt-1 h-4 w-4 border-neutral-300 text-neutral-950 focus:ring-neutral-900"
                                        />

                                        <span className="min-w-0">
                                            <span className="block font-semibold text-neutral-950">
                                                {payment.label}
                                            </span>

                                            <span className="mt-1 block text-sm leading-6 text-neutral-600">
                                                {payment.description}
                                            </span>

                                            <span className="mt-3 inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-800">
                                                Available
                                            </span>
                                        </span>
                                    </label>

                                    <p className="mt-3 text-xs leading-5 text-neutral-500">
                                        Online payment gateways can be added later without changing
                                        the checkout order contract.
                                    </p>
                                </div>
                            </section>

                            <section className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
                                <div className="border-b border-neutral-200 px-5 py-4 sm:px-6">
                                    <h2 className="text-lg font-bold text-neutral-950">
                                        Order items
                                    </h2>

                                    <p className="mt-1 text-sm text-neutral-500">
                                        {cart.item_count} {cart.item_count === 1 ? 'item' : 'items'}
                                    </p>
                                </div>

                                <div className="divide-y divide-neutral-100">
                                    {cart.items.map((item) => (
                                        <article key={item.key} className="flex gap-4 p-5 sm:p-6">
                                            <div className="h-20 w-20 shrink-0 overflow-hidden rounded-xl border border-neutral-200 bg-neutral-100">
                                                {item.image_url ? (
                                                    <img
                                                        src={item.image_url}
                                                        alt=""
                                                        className="h-full w-full object-cover"
                                                    />
                                                ) : (
                                                    <div className="flex h-full w-full items-center justify-center text-xs text-neutral-400">
                                                        No image
                                                    </div>
                                                )}
                                            </div>

                                            <div className="min-w-0 flex-1">
                                                <Link
                                                    href={route('products.show', item.slug)}
                                                    className="font-semibold text-neutral-950 hover:underline"
                                                >
                                                    {item.name}
                                                </Link>

                                                {item.variant_name && (
                                                    <p className="mt-1 text-sm text-neutral-500">
                                                        {item.variant_name}
                                                    </p>
                                                )}

                                                <div className="mt-3 flex flex-wrap items-end justify-between gap-3">
                                                    <div className="text-sm text-neutral-600">
                                                        <span>Qty: {item.quantity}</span>

                                                        <span className="mx-2 text-neutral-300">
                                                            ×
                                                        </span>

                                                        <span>{formatMoney(item.unit_price)}</span>
                                                    </div>

                                                    <p className="font-bold text-neutral-950">
                                                        {formatMoney(item.line_total)}
                                                    </p>
                                                </div>
                                            </div>
                                        </article>
                                    ))}
                                </div>
                            </section>

                            <Link
                                href={route('cart.show')}
                                className="inline-flex text-sm font-semibold text-neutral-600 hover:text-neutral-950"
                            >
                                ← Return to cart
                            </Link>
                        </div>

                        <aside className="lg:sticky lg:top-6 lg:self-start">
                            <div className="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm sm:p-6">
                                <h2 className="text-lg font-bold text-neutral-950">
                                    Order summary
                                </h2>

                                <div className="mt-5 space-y-3">
                                    <div className="flex items-center justify-between gap-4 text-sm">
                                        <span className="text-neutral-600">Subtotal</span>

                                        <span className="font-semibold text-neutral-950">
                                            {formatMoney(totals.subtotal)}
                                        </span>
                                    </div>

                                    <div className="flex items-center justify-between gap-4 text-sm">
                                        <span className="text-neutral-600">Discount</span>

                                        <span className="font-semibold text-neutral-950">
                                            {formatMoney(totals.discount_total)}
                                        </span>
                                    </div>

                                    <div className="flex items-center justify-between gap-4 text-sm">
                                        <span className="text-neutral-600">Delivery</span>

                                        <span className="font-semibold text-neutral-950">
                                            {formatMoney(totals.delivery_total)}
                                        </span>
                                    </div>

                                    <div className="flex items-center justify-between gap-4 text-sm">
                                        <span className="text-neutral-600">Tax</span>

                                        <span className="font-semibold text-neutral-950">
                                            {formatMoney(totals.tax_total)}
                                        </span>
                                    </div>

                                    <div className="flex items-center justify-between gap-4 text-sm">
                                        <span className="text-neutral-600">Payment</span>

                                        <span className="text-right font-medium text-neutral-950">
                                            {payment.label}
                                        </span>
                                    </div>
                                </div>

                                <div className="mt-5 border-t border-neutral-200 pt-5">
                                    <div className="flex items-center justify-between gap-4">
                                        <span className="font-semibold text-neutral-950">
                                            Total
                                        </span>

                                        <span className="text-xl font-bold text-neutral-950">
                                            {formatMoney(totals.grand_total)}
                                        </span>
                                    </div>
                                </div>

                                {selectedAddressId !== null ? (
                                    <div className="mt-6 rounded-xl bg-green-50 p-4 text-sm font-medium leading-6 text-green-800">
                                        Delivery address selected.
                                    </div>
                                ) : (
                                    <div className="mt-6 rounded-xl bg-amber-50 p-4 text-sm leading-6 text-amber-800">
                                        Select or add a delivery address before continuing.
                                    </div>
                                )}

                                {checkoutForm.errors.cart && (
                                    <div className="mt-4 rounded-xl bg-red-50 p-4 text-sm leading-6 text-red-700">
                                        {checkoutForm.errors.cart}
                                    </div>
                                )}

                                {checkoutForm.errors.address_id && (
                                    <div className="mt-4 rounded-xl bg-red-50 p-4 text-sm leading-6 text-red-700">
                                        {checkoutForm.errors.address_id}
                                    </div>
                                )}

                                <button
                                    type="button"
                                    onClick={placeOrder}
                                    disabled={selectedAddressId === null || checkoutForm.processing}
                                    className="mt-5 inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-neutral-950 px-5 text-sm font-semibold text-white transition hover:bg-neutral-800 disabled:cursor-not-allowed disabled:bg-neutral-300 disabled:text-neutral-600"
                                >
                                    {checkoutForm.processing ? 'Placing order...' : 'Place order'}
                                </button>

                                <p className="mt-3 text-center text-xs leading-5 text-neutral-500">
                                    Your cart, delivery address, payment method, prices, stock, and
                                    totals will be verified again by the server before the order is
                                    placed.
                                </p>
                            </div>
                        </aside>
                    </div>
                </div>
            </main>
        </FrontendLayout>
    )
}
