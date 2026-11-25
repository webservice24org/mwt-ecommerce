import ProductGrid from '@/Components/Frontend/Catalog/ProductGrid'
import ProductListingHeader from '@/Components/Frontend/Catalog/ProductListingHeader'
import ProductPagination from '@/Components/Frontend/Catalog/ProductPagination'
import StorefrontFilterPanel from '@/Components/Frontend/Catalog/StorefrontFilterPanel'
import StorefrontListingControls from '@/Components/Frontend/Catalog/StorefrontListingControls'
import StorefrontBreadcrumbs from '@/Components/Frontend/Navigation/StorefrontBreadcrumbs'
import StorefrontSeo from '@/Components/Frontend/Seo/StorefrontSeo'
import EntityContextLinks from '@/Components/Frontend/Seo/EntityContextLinks'
import { useStorefrontFilters } from '@/hooks/useStorefrontFilters'
import FrontendLayout from '@/Layouts/Frontend/FrontendLayout'

import type { ResolvedSeo } from '@/types/seo'

import type {
    StructuredDataDocument,
} from '@/types/structured-data'

import type {
    PaginatedStorefrontProducts,
    StorefrontBrandDetail,
    StorefrontBreadcrumbItem,
    StorefrontFilterOptions,
    StorefrontProductFilters,
} from '@/types/storefront'

interface Props {
    brand: StorefrontBrandDetail
    products: PaginatedStorefrontProducts
    filters: StorefrontProductFilters
    filterOptions: StorefrontFilterOptions
    seo: ResolvedSeo
    breadcrumbStructuredData:
        | StructuredDataDocument
        | null
}

export default function Show({
    brand,
    products,
    filters,
    filterOptions,
    seo,
    breadcrumbStructuredData,
}: Props) {
    const storefrontFilters =
        useStorefrontFilters({
            url:
                `/brand/${brand.slug}`,

            filters,
        })

    const breadcrumbs:
        StorefrontBreadcrumbItem[] = [
            {
                label:
                    'Home',

                href:
                    '/',
            },

            {
                label:
                    'Shop',

                href:
                    '/products',
            },

            {
                label:
                    brand.name,
            },
        ]

    return (
        <FrontendLayout>
            <StorefrontSeo
                seo={seo}
                jsonLd={
                    breadcrumbStructuredData
                }
            />

            <main className="mx-auto w-full max-w-7xl min-w-0 px-4 py-8 sm:px-6 lg:px-8">
                <StorefrontBreadcrumbs
                    items={
                        breadcrumbs
                    }
                />

                {brand.logo_url && (
                    <div className="mt-6 flex min-h-36 items-center justify-center overflow-hidden rounded-xl border border-neutral-200 bg-white p-6">
                        <img
                            src={
                                brand.logo_url
                            }
                            alt={
                                brand.name
                            }
                            loading="eager"
                            decoding="async"
                            className="max-h-28 max-w-full object-contain"
                        />
                    </div>
                )}

                <div className="mt-8">
                    <ProductListingHeader
                        title={
                            brand.name
                        }
                        description={
                            brand.description
                        }
                        total={
                            products.total
                        }
                    />
                </div>

                <EntityContextLinks
                    title={`Categories from ${brand.name}`}
                    items={
                        filterOptions.categories.map(
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
                        )
                    }
                />

                <div className="mt-6">
                    <StorefrontListingControls
                        filters={
                            filters
                        }
                        options={
                            filterOptions
                        }
                        showBrands={
                            false
                        }
                        onSortChange={
                            storefrontFilters
                                .setSort
                        }
                        onBrandChange={
                            storefrontFilters
                                .setBrand
                        }
                        onCategoryChange={
                            storefrontFilters
                                .setCategory
                        }
                        onPriceChange={
                            storefrontFilters
                                .setPrice
                        }
                        onAttributeChange={
                            storefrontFilters
                                .setAttribute
                        }
                        onClearAll={
                            storefrontFilters
                                .clearAll
                        }
                    />
                </div>

                <div className="mt-6 grid min-w-0 gap-8 lg:grid-cols-[16rem_minmax(0,1fr)]">
                    <aside
                        className="hidden min-w-0 lg:block"
                        aria-label="Product filters"
                    >
                        <div className="sticky top-6 rounded-xl border border-neutral-200 bg-white p-4">
                            <StorefrontFilterPanel
                                filters={
                                    filters
                                }
                                options={
                                    filterOptions
                                }
                                showBrands={
                                    false
                                }
                                onBrandChange={
                                    storefrontFilters
                                        .setBrand
                                }
                                onCategoryChange={
                                    storefrontFilters
                                        .setCategory
                                }
                                onPriceChange={
                                    storefrontFilters
                                        .setPrice
                                }
                                onAttributeChange={
                                    storefrontFilters
                                        .setAttribute
                                }
                            />
                        </div>
                    </aside>

                    <section className="min-w-0">
                        {products.data.length > 0 ? (
                            <>
                                <ProductGrid
                                    products={
                                        products.data
                                    }
                                />

                                <div className="mt-8">
                                    <ProductPagination
                                        links={
                                            products.links
                                        }
                                    />
                                </div>
                            </>
                        ) : (
                            <div className="rounded-xl border border-neutral-200 bg-white px-6 py-16 text-center">
                                <h2 className="text-lg font-semibold text-neutral-900">
                                    No products found
                                </h2>

                                <p className="mt-2 text-sm text-neutral-600">
                                    No products match the selected filters for this brand.
                                </p>
                            </div>
                        )}
                    </section>
                </div>
            </main>
        </FrontendLayout>
    )
}