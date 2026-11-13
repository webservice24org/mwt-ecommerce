import { router, usePage } from '@inertiajs/react'
import { Heart } from 'lucide-react'
import {
    useEffect,
    useState,
} from 'react'

import { jsonRequestHeaders } from '@/lib/csrf'
import {
    dispatchWishlistProductChanged,
    STOREFRONT_WISHLIST_CHANGED,
    type StorefrontWishlistChangedDetail,
} from '@/lib/storefrontWishlist'
import type { PageProps } from '@/types'
import type { StorefrontWishlist } from '@/types/storefront'

interface Props {
    productId: number
    initialActive?: boolean
    onChanged?: (
        active: boolean,
        count: number,
    ) => void
    className?: string
}

interface WishlistToggleResponse {
    active: boolean
    count: number
}

type WishlistPageProps =
    PageProps<{
        storefrontWishlist?: StorefrontWishlist
    }>

export default function WishlistToggleButton({
    productId,
    initialActive,
    onChanged,
    className = '',
}: Props) {
    const {
        storefrontWishlist,
    } =
        usePage<WishlistPageProps>()
            .props

    /*
     * Explicit initialActive remains supported for
     * special callers, but shared authoritative state
     * is the normal source.
     */
    const authoritativeActive =
        initialActive ??
        storefrontWishlist
            ?.product_ids
            .includes(
                productId,
            ) ??
        false

    const [
        activeOverride,
        setActiveOverride,
    ] =
        useState<boolean | null>(
            null,
        )

    const active =
        activeOverride ??
        authoritativeActive

    const [
        processing,
        setProcessing,
    ] =
        useState(false)

    /*
     * Synchronize duplicate heart buttons and explicit
     * reset events. Browser event state is temporary;
     * the next Inertia response becomes authoritative.
     */
    useEffect(() => {
        const handleWishlistChanged = (
            event: Event,
        ) => {
            const customEvent =
                event as CustomEvent<StorefrontWishlistChangedDetail>

            if (
                customEvent.detail.type ===
                'product'
            ) {
                if (
                    customEvent.detail.productId !==
                    productId
                ) {
                    return
                }

                setActiveOverride(
                    customEvent.detail.active,
                )

                return
            }

            setActiveOverride(
                customEvent.detail.productIds.includes(
                    productId,
                ),
            )
        }

        window.addEventListener(
            STOREFRONT_WISHLIST_CHANGED,
            handleWishlistChanged,
        )

        const removeFinishListener =
            router.on('finish', () => {
                setActiveOverride(
                    null,
                )
            })

        return () => {
            window.removeEventListener(
                STOREFRONT_WISHLIST_CHANGED,
                handleWishlistChanged,
            )

            removeFinishListener()
        }
    }, [
        productId,
    ])

    const toggle =
        async () => {
            if (processing) {
                return
            }

            setProcessing(
                true,
            )

            try {
                const response =
                    await fetch(
                        `/wishlist/${productId}/toggle`,
                        {
                            method:
                                'POST',

                            credentials:
                                'same-origin',

                            headers:
                                jsonRequestHeaders(),
                        },
                    )

                if (!response.ok) {
                    throw new Error(
                        'Unable to update wishlist.',
                    )
                }

                const result =
                    (await response.json()) as WishlistToggleResponse

                setActiveOverride(
                    result.active,
                )

                dispatchWishlistProductChanged(
                    productId,
                    result.active,
                    result.count,
                )

                onChanged?.(
                    result.active,
                    result.count,
                )
            } finally {
                setProcessing(
                    false,
                )
            }
        }

    return (
        <button
            type="button"
            disabled={
                processing
            }
            aria-pressed={
                active
            }
            aria-label={
                active
                    ? 'Remove from wishlist'
                    : 'Add to wishlist'
            }
            title={
                active
                    ? 'Remove from wishlist'
                    : 'Add to wishlist'
            }
            onClick={() => {
                void toggle()
            }}
            className={[
                'inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full border bg-white transition',
                active
                    ? 'border-rose-200 text-rose-600'
                    : 'border-neutral-200 text-neutral-600 hover:border-rose-200 hover:text-rose-600',
                processing
                    ? 'cursor-wait opacity-60'
                    : '',
                className,
            ].join(
                ' ',
            )}
        >
            <Heart
                className={[
                    'h-5 w-5',
                    active
                        ? 'fill-current'
                        : '',
                ].join(
                    ' ',
                )}
                aria-hidden="true"
            />
        </button>
    )
}