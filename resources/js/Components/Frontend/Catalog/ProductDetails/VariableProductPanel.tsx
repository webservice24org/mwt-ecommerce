import ProductPrice from '@/Components/Frontend/Catalog/ProductPrice'
import ProductVariantSelector from '@/Components/Frontend/Catalog/ProductDetails/ProductVariantSelector'
import { useStorefrontVariantSelection } from '@/hooks/useStorefrontVariantSelection'
import type { StorefrontProductDetail } from '@/types/storefront'

interface VariableProductPanelProps {
    product: StorefrontProductDetail
}

export default function VariableProductPanel({ product }: VariableProductPanelProps) {
    const { selectedVariant, attributes, select, reset, complete } = useStorefrontVariantSelection(
        product.variants,
    )

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
            <ProductVariantSelector attributes={attributes} onSelect={select} onReset={reset} />

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
                    disabled
                    className="mt-4 w-full rounded-lg bg-neutral-900 px-4 py-3 text-sm font-semibold text-white opacity-50"
                >
                    Add to cart
                </button>

                <p className="mt-2 text-xs text-neutral-500">
                    Cart functionality will be enabled in the cart phase.
                </p>

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
    variant: StorefrontProductDetail['variants'][number]
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
