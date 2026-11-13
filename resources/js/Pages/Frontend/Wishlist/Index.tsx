import {
    Link,
    router,
} from '@inertiajs/react'
import {
    Heart,
    ShoppingBag,
    Trash2,
} from 'lucide-react'
import {
    useState,
} from 'react'

import ProductImage from '@/Components/Frontend/Catalog/ProductImage'
import ProductPrice from '@/Components/Frontend/Catalog/ProductPrice'
import StorefrontBreadcrumbs from '@/Components/Frontend/Navigation/StorefrontBreadcrumbs'
import StorefrontSeo from '@/Components/Frontend/Seo/StorefrontSeo'
import FrontendLayout from '@/Layouts/Frontend/FrontendLayout'
import {
    jsonRequestHeaders,
} from '@/lib/csrf'
import {
    dispatchCartChanged,
} from '@/lib/storefrontCart'
import {
    dispatchWishlistProductChanged,
    dispatchWishlistReset,
} from '@/lib/storefrontWishlist'
import {
    buildBreadcrumbJsonLd,
} from '@/lib/storefrontSeo'
import type {
    StorefrontBreadcrumbItem,
    StorefrontCart,
    StorefrontProductCard,
} from '@/types/storefront'

type WishlistAvailability =
    | 'available'
    | 'requires_options'
    | 'unavailable'

interface WishlistItem {
    product: StorefrontProductCard
    availability: WishlistAvailability
    availability_label: string
    can_add_to_cart: boolean
}

interface WishlistIndexProps {
    items: WishlistItem[]
}

export default function Index({
    items,
}: WishlistIndexProps) {
    const [
        processingProductId,
        setProcessingProductId,
    ] =
        useState<number | null>(
            null,
        )

    const [
        clearing,
        setClearing,
    ] =
        useState(false)

    const [
        cartError,
        setCartError,
    ] =
        useState<string | null>(
            null,
        )

    const [
        cartSuccess,
        setCartSuccess,
    ] =
        useState<string | null>(
            null,
        )

    const breadcrumbs: StorefrontBreadcrumbItem[] =
        [
            {
                label:
                    'Home',

                href:
                    '/',
            },

            {
                label:
                    'Wishlist',
            },
        ]

    const breadcrumbJsonLd =
        buildBreadcrumbJsonLd(
            breadcrumbs,
        )

    const removeItem = (
        item: WishlistItem,
    ) => {
        if (
            processingProductId !==
                null
            || clearing
        ) {
            return
        }

        setProcessingProductId(
            item.product.id,
        )

        router.delete(
            `/wishlist/${item.product.id}`,
            {
                preserveScroll:
                    true,

                preserveState:
                    true,

                onSuccess: () => {
                    dispatchWishlistProductChanged(
                        item.product.id,
                        false,
                        Math.max(
                            0,
                            items.length -
                                1,
                        ),
                    )
                },

                onFinish: () => {
                    setProcessingProductId(
                        null,
                    )
                },
            },
        )
    }

    const clearWishlist =
        () => {
            if (
                clearing
                || processingProductId !==
                    null
            ) {
                return
            }

            if (
                ! window.confirm(
                    'Clear all products from your wishlist?',
                )
            ) {
                return
            }

            setClearing(
                true,
            )

            router.delete(
                '/wishlist',
                {
                    preserveScroll:
                        true,

                    preserveState:
                        true,

                    onSuccess: () => {
                        dispatchWishlistReset(
                            [],
                            0,
                        )
                    },

                    onFinish: () => {
                        setClearing(
                            false,
                        )
                    },
                },
            )
        }

    const addToCart =
        async (
            item: WishlistItem,
        ) => {
            if (
                processingProductId !==
                    null
                || clearing
                || ! item
                    .can_add_to_cart
            ) {
                return
            }

            setProcessingProductId(
                item.product.id,
            )

            setCartError(
                null,
            )

            setCartSuccess(
                null,
            )

            try {
                const response =
                    await fetch(
                        '/cart/items',
                        {
                            method:
                                'POST',

                            credentials:
                                'same-origin',

                            headers:
                                jsonRequestHeaders(),

                            body:
                                JSON.stringify(
                                    {
                                        product_id:
                                            item
                                                .product
                                                .id,

                                        variant_id:
                                            null,

                                        quantity:
                                            1,
                                    },
                                ),
                        },
                    )

                if (
                    ! response.ok
                ) {
                    throw new Error(
                        await cartErrorMessage(
                            response,
                        ),
                    )
                }

                const cart =
                    (await response.json()) as StorefrontCart

                dispatchCartChanged(
                    cart,
                )

                setCartSuccess(
                    `${item.product.name} added to cart.`,
                )
            } catch (
                error: unknown
            ) {
                setCartError(
                    error instanceof
                        Error
                        ? error.message
                        : 'Unable to add this product to the cart.',
                )
            } finally {
                setProcessingProductId(
                    null,
                )
            }
        }

    return (
        <FrontendLayout>
            <StorefrontSeo
                title="Wishlist"
                description="View products saved to your wishlist."
                canonicalPath="/wishlist"
                jsonLd={
                    breadcrumbJsonLd
                }
            />

            <main className="mx-auto w-full max-w-7xl min-w-0 px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-6 min-w-0">
                    <StorefrontBreadcrumbs
                        items={
                            breadcrumbs
                        }
                    />
                </div>

                <div className="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div className="min-w-0">
                        <p className="text-sm font-medium text-neutral-500">
                            Saved products
                        </p>

                        <h1 className="mt-1 break-words text-2xl font-bold tracking-tight text-neutral-950 sm:text-3xl">
                            My Wishlist
                        </h1>

                        <p className="mt-2 text-sm text-neutral-600">
                            {items.length ===
                            1
                                ? '1 product saved'
                                : `${items.length} products saved`}
                        </p>
                    </div>

                    {items.length >
                        0 && (
                        <button
                            type="button"
                            disabled={
                                clearing
                                || processingProductId !==
                                    null
                            }
                            onClick={
                                clearWishlist
                            }
                            className="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-lg border border-red-200 bg-white px-4 text-sm font-semibold text-red-700 transition hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-50"
                        >
                            <Trash2
                                className="h-4 w-4"
                                aria-hidden="true"
                            />

                            {clearing
                                ? 'Clearing...'
                                : 'Clear wishlist'}
                        </button>
                    )}
                </div>

                {cartError && (
                    <div
                        role="alert"
                        className="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                    >
                        {cartError}
                    </div>
                )}

                {cartSuccess && (
                    <div
                        role="status"
                        aria-live="polite"
                        className="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
                    >
                        {cartSuccess}
                    </div>
                )}

                {items.length >
                0 ? (
                    <ul className="mt-8 space-y-4">
                        {items.map(
                            (
                                item,
                            ) => (
                                <WishlistRow
                                    key={
                                        item
                                            .product
                                            .id
                                    }
                                    item={
                                        item
                                    }
                                    processing={
                                        processingProductId ===
                                        item
                                            .product
                                            .id
                                    }
                                    locked={
                                        clearing
                                        || processingProductId !==
                                            null
                                    }
                                    onRemove={() =>
                                        removeItem(
                                            item,
                                        )
                                    }
                                    onAddToCart={() => {
                                        void addToCart(
                                            item,
                                        )
                                    }}
                                />
                            ),
                        )}
                    </ul>
                ) : (
                    <div className="mt-8 rounded-2xl border border-dashed border-neutral-300 px-6 py-16 text-center">
                        <span className="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-neutral-100 text-neutral-500">
                            <Heart
                                className="h-6 w-6"
                                aria-hidden="true"
                            />
                        </span>

                        <h2 className="mt-5 text-lg font-semibold text-neutral-950">
                            Your wishlist
                            is empty
                        </h2>

                        <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-neutral-600">
                            Save products
                            you like and
                            they will
                            appear here
                            for later.
                        </p>

                        <Link
                            href="/products"
                            className="mt-6 inline-flex min-h-11 items-center justify-center rounded-lg bg-neutral-950 px-4 text-sm font-semibold text-white transition hover:bg-neutral-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 focus-visible:ring-offset-2"
                        >
                            Browse products
                        </Link>
                    </div>
                )}
            </main>
        </FrontendLayout>
    )
}

function WishlistRow({
    item,
    processing,
    locked,
    onRemove,
    onAddToCart,
}: {
    item: WishlistItem
    processing: boolean
    locked: boolean
    onRemove: () => void
    onAddToCart: () => void
}) {
    const product =
        item.product

    const productUrl =
        `/products/${product.slug}`

    return (
        <li className="min-w-0 overflow-hidden rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm sm:p-5">
            <div className="flex min-w-0 flex-col gap-5 sm:flex-row">
                <Link
                    href={
                        productUrl
                    }
                    className="block aspect-square w-full shrink-0 overflow-hidden rounded-xl bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 sm:h-36 sm:w-36"
                    aria-label={`View ${product.name}`}
                >
                    <ProductImage
                        image={
                            product.image
                        }
                        alt={
                            product.name
                        }
                        className="h-full w-full object-cover"
                        sizes="(min-width: 640px) 144px, 100vw"
                    />
                </Link>

                <div className="flex min-w-0 flex-1 flex-col">
                    <div className="flex min-w-0 flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div className="min-w-0">
                            {product.brand && (
                                <p className="truncate text-xs font-semibold uppercase tracking-wide text-neutral-500">
                                    {
                                        product
                                            .brand
                                            .name
                                    }
                                </p>
                            )}

                            <h2 className="mt-1 min-w-0 text-lg font-semibold leading-6 text-neutral-950">
                                {item.availability ===
                                'unavailable' ? (
                                    <span className="line-clamp-2 break-words">
                                        {
                                            product.name
                                        }
                                    </span>
                                ) : (
                                    <Link
                                        href={
                                            productUrl
                                        }
                                        className="line-clamp-2 break-words underline-offset-4 transition hover:text-neutral-600 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950"
                                    >
                                        {
                                            product.name
                                        }
                                    </Link>
                                )}
                            </h2>

                            {product.short_description && (
                                <p className="mt-2 line-clamp-2 break-words text-sm leading-6 text-neutral-600">
                                    {
                                        product.short_description
                                    }
                                </p>
                            )}
                        </div>

                        <ProductPrice
                            mode="listing"
                            pricing={
                                product.pricing
                            }
                            className="shrink-0"
                        />
                    </div>

                    <div className="mt-4">
                        <AvailabilityBadge
                            availability={
                                item.availability
                            }
                            label={
                                item.availability_label
                            }
                        />
                    </div>

                    <div className="mt-auto flex flex-col gap-2 pt-5 sm:flex-row sm:items-center">
                        {item.can_add_to_cart ? (
                            <button
                                type="button"
                                disabled={
                                    locked
                                }
                                onClick={
                                    onAddToCart
                                }
                                className="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-neutral-950 px-4 text-sm font-semibold text-white transition hover:bg-neutral-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-50"
                            >
                                <ShoppingBag
                                    className="h-4 w-4"
                                    aria-hidden="true"
                                />

                                {processing
                                    ? 'Adding...'
                                    : 'Add to cart'}
                            </button>
                        ) : item.availability ===
                          'requires_options' ? (
                            <Link
                                href={
                                    productUrl
                                }
                                className="inline-flex min-h-11 items-center justify-center rounded-lg bg-neutral-950 px-4 text-sm font-semibold text-white transition hover:bg-neutral-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 focus-visible:ring-offset-2"
                            >
                                Choose options
                            </Link>
                        ) : (
                            <button
                                type="button"
                                disabled
                                className="inline-flex min-h-11 cursor-not-allowed items-center justify-center rounded-lg bg-neutral-200 px-4 text-sm font-semibold text-neutral-500"
                            >
                                Unavailable
                            </button>
                        )}

                        <button
                            type="button"
                            disabled={
                                locked
                            }
                            onClick={
                                onRemove
                            }
                            className="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 transition hover:border-red-200 hover:bg-red-50 hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-50"
                        >
                            <Trash2
                                className="h-4 w-4"
                                aria-hidden="true"
                            />

                            {processing
                                ? 'Working...'
                                : 'Remove'}
                        </button>
                    </div>
                </div>
            </div>
        </li>
    )
}

function AvailabilityBadge({
    availability,
    label,
}: {
    availability: WishlistAvailability
    label: string
}) {
    const classes: Record<
        WishlistAvailability,
        string
    > = {
        available:
            'border-emerald-200 bg-emerald-50 text-emerald-700',

        requires_options:
            'border-amber-200 bg-amber-50 text-amber-700',

        unavailable:
            'border-neutral-200 bg-neutral-100 text-neutral-600',
    }

    return (
        <span
            className={[
                'inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold',
                classes[
                    availability
                ],
            ].join(
                ' ',
            )}
        >
            {label}
        </span>
    )
}

async function cartErrorMessage(
    response: Response,
): Promise<string> {
    try {
        const payload =
            (await response.json()) as {
                message?: string
                errors?: Record<
                    string,
                    string[]
                >
            }

        if (
            payload.message
            && payload.message.trim() !==
                ''
        ) {
            return payload.message
        }

        const firstError =
            Object.values(
                payload.errors ??
                    {},
            )
                .flat()
                .find(
                    (
                        message,
                    ) =>
                        message.trim() !==
                        '',
                )

        return firstError ??
            'Unable to add this product to the cart.'
    } catch {
        return 'Unable to add this product to the cart.'
    }
}