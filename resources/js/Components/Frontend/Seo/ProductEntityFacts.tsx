import EntityContextLinks from '@/Components/Frontend/Seo/EntityContextLinks'
import type {
    StorefrontProductDetail,
} from '@/types/storefront'

type Props = {
    product: StorefrontProductDetail
}

export default function ProductEntityFacts({
    product,
}: Props) {
    const relationshipLinks = [
        ...(product.brand
            ? [
                  {
                      key:
                          `brand-${product.brand.id}`,

                      label:
                          product.brand.name,

                      href:
                          `/brand/${product.brand.slug}`,
                  },
              ]
            : []),

        ...product.categories.map(
            (
                category,
            ) => ({
                key:
                    `category-${category.id}`,

                label:
                    category.name,

                href:
                    `/category/${category.slug}`,
            }),
        ),
    ]

    const facts = [
        ...(product.sku?.trim()
            ? [
                  {
                      label:
                          'SKU',

                      value:
                          product.sku.trim(),
                  },
              ]
            : []),

        {
            label:
                'Product type',

            value:
                product.type ===
                'variable'
                    ? 'Variable product'
                    : 'Simple product',
        },

        ...(product.type ===
            'variable'
            ? [
                  {
                      label:
                          'Available variants',

                      value:
                          String(
                              product.variants.length,
                          ),
                  },
              ]
            : []),
    ]

    return (
        <section
            className="mt-8 rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-950"
            aria-labelledby="product-facts-title"
        >
            <h2
                id="product-facts-title"
                className="text-lg font-semibold text-neutral-950 dark:text-neutral-100"
            >
                Product facts
            </h2>

            <dl className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {facts.map(
                    (
                        fact,
                    ) => (
                        <div
                            key={
                                fact.label
                            }
                            className="min-w-0"
                        >
                            <dt className="text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                                {
                                    fact.label
                                }
                            </dt>

                            <dd className="mt-1 break-words text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                {
                                    fact.value
                                }
                            </dd>
                        </div>
                    ),
                )}
            </dl>

            <EntityContextLinks
                title="Related brand and categories"
                items={
                    relationshipLinks
                }
                maxItems={
                    10
                }
            />
        </section>
    )
}
