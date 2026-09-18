import type { StorefrontVariantAttribute } from '@/types/storefront-variant-selection'

interface ProductVariantSelectorProps {
    attributes: StorefrontVariantAttribute[]
    onSelect: (attributeSlug: string, valueSlug: string) => void
    onReset: () => void
}

export default function ProductVariantSelector({
    attributes,
    onSelect,
    onReset,
}: ProductVariantSelectorProps) {
    if (attributes.length === 0) {
        return null
    }

    return (
        <section className="space-y-6" aria-labelledby="product-options-heading">
            <div className="flex items-center justify-between gap-4">
                <h2
                    id="product-options-heading"
                    className="text-base font-semibold text-neutral-950"
                >
                    Product options
                </h2>

                <button
                    type="button"
                    onClick={onReset}
                    aria-label="Reset product options to default"
                    className="text-sm font-medium text-neutral-500 underline-offset-4 hover:text-neutral-900 hover:underline"
                >
                    Reset
                </button>
            </div>

            {attributes.map((attribute) => (
                <fieldset key={attribute.id} className="min-w-0">
                    <legend className="text-sm font-medium text-neutral-900">
                        {attribute.name}
                    </legend>

                    <div className="mt-3 flex flex-wrap gap-2">
                        {attribute.options.map((option) => (
                            <button
                                key={option.value.id}
                                type="button"
                                disabled={!option.available}
                                aria-pressed={option.selected}
                                aria-label={
                                    option.available
                                        ? `${attribute.name}: ${option.value.name}`
                                        : `${attribute.name}: ${option.value.name} — unavailable`
                                }
                                onClick={() => onSelect(attribute.slug, option.value.slug)}
                                className={[
                                    'min-h-10 rounded-lg border px-4 py-2 text-sm font-medium transition',
                                    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 focus-visible:ring-offset-2',
                                    option.selected
                                        ? 'border-neutral-950 bg-neutral-950 text-white'
                                        : 'border-neutral-300 bg-white text-neutral-900 hover:border-neutral-500',
                                    !option.available ? 'cursor-not-allowed opacity-40' : '',
                                ].join(' ')}
                            >
                                {option.value.name}
                            </button>
                        ))}
                    </div>
                </fieldset>
            ))}
        </section>
    )
}
