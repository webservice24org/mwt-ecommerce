import ProductGrid from '@/Components/Frontend/Catalog/ProductGrid'
import ProductListingHeader from '@/Components/Frontend/Catalog/ProductListingHeader'
import ProductPagination from '@/Components/Frontend/Catalog/ProductPagination'
import StorefrontFilterPanel from '@/Components/Frontend/Catalog/StorefrontFilterPanel'
import StorefrontListingControls from '@/Components/Frontend/Catalog/StorefrontListingControls'
import StorefrontBreadcrumbs from '@/Components/Frontend/Navigation/StorefrontBreadcrumbs'
import FrontendLayout from '@/Layouts/Frontend/FrontendLayout'
import { useStorefrontFilters } from '@/hooks/useStorefrontFilters'
import type {
    PaginatedStorefrontProducts,
    StorefrontFilterOptions,
    StorefrontProductFilters,
} from '@/types/storefront'
//import { Head } from '@inertiajs/react'
import { buildBreadcrumbJsonLd } from '@/lib/storefrontSeo'
import StorefrontSeo from '@/Components/Frontend/Seo/StorefrontSeo'

interface ProductsIndexProps {
    products: PaginatedStorefrontProducts
    filters: StorefrontProductFilters
    filterOptions: StorefrontFilterOptions
}

export default function Index({ products, filters, filterOptions }: ProductsIndexProps) {
    const storefrontFilters = useStorefrontFilters({
        url: '/products',
        filters,
    })

    const breadcrumbs = [
        {
            label: 'Home',
            href: '/',
        },
        {
            label: 'Shop',
        },
    ]
    const breadcrumbJsonLd = buildBreadcrumbJsonLd(breadcrumbs)

    return (
        <FrontendLayout>
            <StorefrontSeo
                title="Shop"
                description="Browse our products and discover available product options."
                canonicalPath="/products"
                jsonLd={breadcrumbJsonLd}
            />

            <main className="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-6">
                    <StorefrontBreadcrumbs items={breadcrumbs} />
                </div>
                <ProductListingHeader title="Shop" total={products.total} />

                <div className="mt-6">
                    <StorefrontListingControls
                        filters={filters}
                        options={filterOptions}
                        onSortChange={storefrontFilters.setSort}
                        onBrandChange={storefrontFilters.setBrand}
                        onCategoryChange={storefrontFilters.setCategory}
                        onPriceChange={storefrontFilters.setPrice}
                        onAttributeChange={storefrontFilters.setAttribute}
                        onClearAll={storefrontFilters.clearAll}
                    />
                </div>

                <div className="mt-6 grid min-w-0 gap-8 lg:grid-cols-[240px_minmax(0,1fr)]">
                    <aside className="hidden lg:block" aria-label="Product filters">
                        <div className="sticky top-6 rounded-xl border border-neutral-200 bg-white p-4">
                            <StorefrontFilterPanel
                                filters={filters}
                                options={filterOptions}
                                onBrandChange={storefrontFilters.setBrand}
                                onCategoryChange={storefrontFilters.setCategory}
                                onPriceChange={storefrontFilters.setPrice}
                                onAttributeChange={storefrontFilters.setAttribute}
                            />
                        </div>
                    </aside>

                    <div className="min-w-0">
                        {products.data.length > 0 ? (
                            <>
                                <ProductGrid products={products.data} />

                                <div className="mt-8">
                                    <ProductPagination links={products.links} />
                                </div>
                            </>
                        ) : (
                            <div className="rounded-xl border border-dashed border-neutral-300 px-6 py-14 text-center">
                                <h2 className="text-lg font-semibold">No products found</h2>

                                <p className="mt-2 text-sm text-neutral-600">
                                    Try changing or clearing your filters.
                                </p>

                                <button
                                    type="button"
                                    onClick={storefrontFilters.clearAll}
                                    className="mt-4 text-sm font-semibold underline underline-offset-4"
                                >
                                    Clear filters
                                </button>
                            </div>
                        )}
                    </div>
                </div>
            </main>
        </FrontendLayout>
    )
}
