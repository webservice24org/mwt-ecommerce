import { useState } from 'react'

import ProductPrice from '@/Components/Frontend/Catalog/ProductPrice'
import ProductVariantSelector from '@/Components/Frontend/Catalog/ProductDetails/ProductVariantSelector'
import { useStorefrontVariantSelection } from '@/hooks/useStorefrontVariantSelection'
import { addToCart } from '@/lib/addToCart'
import type { StorefrontProductDetail, StorefrontVariant } from '@/types/storefront'

interface VariableProductPanelProps {
    product: StorefrontProductDetail
}

export default function VariableProductPanel({ product }: VariableProductPanelProps) {
    const { selectedVariant, attributes, select, reset, complete } = useStorefrontVariantSelection(
        product.variants,
    )

    const [processing, setProcessing] = useState(false)
    const [message, setMessage] = useState<string | null>(null)
    const [failed, setFailed] = useState(false)

    const canAdd =
        complete &&
        selectedVariant !== null &&
        selectedVariant.pricing.price !== null &&
        !processing

    const handleAddToCart = async () => {
        if (!canAdd || selectedVariant === null) {
            return
        }

        setProcessing(true)
        setMessage(null)
        setFailed(false)

        try {
            await addToCart({
                productId: product.id,
                variantId: selectedVariant.id,
                quantity: 1,
            })

            setMessage('Added to cart.')
        } catch {
            setFailed(true)
            setMessage('Unable to add this option to the cart.')
        } finally {
            setProcessing(false)
        }
    }

    if (product.variants.length === 0) {
        return (
            <section className="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                <h2 className="font-semibold text-neutral-950">Product options</h2>

                <p className="mt-2 text-sm leading-6 text-neutral-600">
                    There are currently no available options for this product.
                </p>
            </section>
        )
    }

    return (
        <div className="space-y-6">
            <ProductVariantSelector
                attributes={attributes}
                onSelect={select}
                onReset={reset}
            />

            <div
                className="rounded-xl border border-neutral-200 bg-neutral-50 p-4"
                aria-live="polite"
                aria-atomic="true"
            >
                {selectedVariant ? (
                    <SelectedVariantSummary variant={selectedVariant} />
                ) : (
                    <IncompleteSelection />
                )}

                <button
                    type="button"
                    disabled={!canAdd}
                    onClick={() => {
                        void handleAddToCart()
                    }}
                    className="mt-4 min-h-11 w-full rounded-lg bg-neutral-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {processing ? 'Adding…' : 'Add to cart'}
                </button>

                {!complete && (
                    <p className="mt-2 text-sm font-medium text-amber-700">
                        Select all required options.
                    </p>
                )}

                {message && (
                    <p
                        className={[
                            'mt-2 text-xs',
                            failed ? 'text-red-600' : 'text-emerald-700',
                        ].join(' ')}
                        role={failed ? 'alert' : 'status'}
                    >
                        {message}
                    </p>
                )}
            </div>
        </div>
    )
}

interface SelectedVariantSummaryProps {
    variant: StorefrontVariant
}

function SelectedVariantSummary({ variant }: SelectedVariantSummaryProps) {
    return (
        <div className="space-y-3">
            <ProductPrice mode="resolved" pricing={variant.pricing} />

            {variant.sku && (
                <div className="flex min-w-0 gap-3 text-sm">
                    <span className="shrink-0 font-medium text-neutral-500">SKU</span>

                    <span className="min-w-0 break-all text-neutral-900">{variant.sku}</span>
                </div>
            )}

            {variant.name && <p className="text-sm text-neutral-600">{variant.name}</p>}
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
