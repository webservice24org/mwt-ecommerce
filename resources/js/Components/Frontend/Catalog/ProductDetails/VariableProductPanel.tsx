import {
    useEffect,
    useRef,
} from 'react'

import ProductPrice from '@/Components/Frontend/Catalog/ProductPrice'
import ProductVariantSelector from '@/Components/Frontend/Catalog/ProductDetails/ProductVariantSelector'
import { useStorefrontVariantSelection } from '@/hooks/useStorefrontVariantSelection'
import ProductPurchaseActions from '@/Components/Frontend/Catalog/ProductPurchaseActions'
import type {
    StorefrontProductDetail,
    StorefrontVariant,
} from '@/types/storefront'

interface VariableProductPanelProps {
    product: StorefrontProductDetail
    initialVariantId?: number | null
}

export default function VariableProductPanel({
    product,
    initialVariantId = null,
}: VariableProductPanelProps) {
    const {
        selectedVariant,
        attributes,
        select,
        reset,
        complete,
    } =
        useStorefrontVariantSelection(
            product.variants,
            initialVariantId,
        )

    /*
     * The server validates ?variant= and sends the only
     * allowed initial variant ID.
     *
     * The browser only keeps the URL synchronized after
     * the initial render. It never decides whether a
     * requested variant is valid.
     */
    const firstUrlSync =
        useRef(
            true,
        )

    const suppressNextUrlSync =
        useRef(
            false,
        )

    useEffect(() => {
        if (
            typeof window ===
            'undefined'
        ) {
            return
        }

        const url =
            new URL(
                window.location.href,
            )

        if (firstUrlSync.current) {
            firstUrlSync.current =
                false

            /*
             * If Laravel rejected an invalid/inactive
             * ?variant= value, remove it from the visible URL.
             *
             * For a valid deep link, the current URL already
             * contains the correct variant ID, so no rewrite
             * is necessary on first render.
             */
            if (
                initialVariantId ===
                    null
                && url.searchParams.has(
                    'variant',
                )
            ) {
                url.searchParams.delete(
                    'variant',
                )

                window.history.replaceState(
                    {},
                    '',
                    url,
                )
            }

            return
        }

        if (
            suppressNextUrlSync.current
        ) {
            suppressNextUrlSync.current =
                false

            return
        }

        if (
            selectedVariant ===
            null
        ) {
            if (
                ! url.searchParams.has(
                    'variant',
                )
            ) {
                return
            }

            url.searchParams.delete(
                'variant',
            )

            window.history.replaceState(
                {},
                '',
                url,
            )

            return
        }

        const selectedVariantId =
            String(
                selectedVariant.id,
            )

        if (
            url.searchParams.get(
                'variant',
            ) ===
            selectedVariantId
        ) {
            return
        }

        url.searchParams.set(
            'variant',
            selectedVariantId,
        )

        window.history.replaceState(
            {},
            '',
            url,
        )
    }, [
        initialVariantId,
        selectedVariant,
    ])

    const handleReset =
        () => {
            /*
             * reset() may resolve the catalog's default
             * variant. Suppress that one state transition so
             * Reset genuinely leaves the URL without
             * ?variant=.
             */
            suppressNextUrlSync.current =
                true

            reset()

            if (
                typeof window ===
                'undefined'
            ) {
                return
            }

            const url =
                new URL(
                    window.location.href,
                )

            url.searchParams.delete(
                'variant',
            )

            window.history.replaceState(
                {},
                '',
                url,
            )
        }

    const canAdd = complete && selectedVariant !== null && selectedVariant.pricing.price !== null

    if (
        product.variants.length ===
        0
    ) {
        return (
            <section className="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                <h2 className="font-semibold text-neutral-950">
                    Product options
                </h2>

                <p className="mt-2 text-sm leading-6 text-neutral-600">
                    There are currently no available options for this product.
                </p>
            </section>
        )
    }

    return (
        <div id="product-purchase" className="space-y-6">
            <ProductVariantSelector
                attributes={
                    attributes
                }
                onSelect={
                    select
                }
                onReset={
                    handleReset
                }
            />

            <div
                className="rounded-xl border border-neutral-200 bg-neutral-50 p-4"
                aria-live="polite"
                aria-atomic="true"
            >
                {selectedVariant ? (
                    <SelectedVariantSummary
                        variant={
                            selectedVariant
                        }
                    />
                ) : (
                    <IncompleteSelection />
                )}

                <ProductPurchaseActions
                    productId={product.id}
                    variantId={selectedVariant?.id ?? null}
                    available={canAdd}
                />

                {!complete && (
                    <p className="mt-2 text-sm font-medium text-amber-700">
                        Select all required options.
                    </p>
                )}
            </div>
        </div>
    )
}

interface SelectedVariantSummaryProps {
    variant: StorefrontVariant
}

function SelectedVariantSummary({
    variant,
}: SelectedVariantSummaryProps) {
    return (
        <div className="space-y-3">
            <ProductPrice
                mode="resolved"
                pricing={
                    variant.pricing
                }
            />

            {variant.sku && (
                <div className="flex min-w-0 gap-3 text-sm">
                    <span className="shrink-0 font-medium text-neutral-500">
                        SKU
                    </span>

                    <span className="min-w-0 break-all text-neutral-900">
                        {variant.sku}
                    </span>
                </div>
            )}

            {variant.name && (
                <p className="text-sm text-neutral-600">
                    {variant.name}
                </p>
            )}
        </div>
    )
}

function IncompleteSelection() {
    return (
        <p className="text-sm leading-6 text-neutral-600">
            Choose the available product options to see the selected variant.
        </p>
    )
}
