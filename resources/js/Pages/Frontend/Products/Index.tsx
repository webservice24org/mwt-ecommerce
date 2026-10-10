import ProductGrid from '@/Components/Frontend/Catalog/ProductGrid'
import ProductListingHeader from '@/Components/Frontend/Catalog/ProductListingHeader'
import ProductPagination from '@/Components/Frontend/Catalog/ProductPagination'
import StorefrontFilterPanel from '@/Components/Frontend/Catalog/StorefrontFilterPanel'
import StorefrontListingControls from '@/Components/Frontend/Catalog/StorefrontListingControls'
import StorefrontBreadcrumbs from '@/Components/Frontend/Navigation/StorefrontBreadcrumbs'
import StorefrontSeo from '@/Components/Frontend/Seo/StorefrontSeo'
import { useStorefrontFilters } from '@/hooks/useStorefrontFilters'
import FrontendLayout from '@/Layouts/Frontend/FrontendLayout'
import { buildBreadcrumbJsonLd } from '@/lib/storefrontSeo'
import type {
    PaginatedStorefrontProducts,
    StorefrontBreadcrumbItem,
    StorefrontFilterOptions,
    StorefrontProductFilters,
} from '@/types/storefront'
import { Link } from '@inertiajs/react'

interface ProductsIndexProps {
    products: PaginatedStorefrontProducts
    filters: StorefrontProductFilters & {
        q?: string
    }
    filterOptions: StorefrontFilterOptions
}

export default function Index({ products, filters, filterOptions }: ProductsIndexProps) {
    const storefrontFilters = useStorefrontFilters({
        url: '/products',
        filters,
    })

    const searchQuery = filters.q?.trim() ?? ''

    const breadcrumbs: StorefrontBreadcrumbItem[] = [
        {
            label: 'Home',
            href: '/',
        },
        {
            label: 'Shop',
        },
    ]

    const breadcrumbJsonLd = buildBreadcrumbJsonLd(breadcrumbs)

    const pageTitle = searchQuery !== '' ? `Search results for "${searchQuery}"` : 'Shop'

    const pageDescription =
        searchQuery !== ''
            ? `Browse products matching "${searchQuery}".`
            : 'Browse our products and discover available product options.'

    return (
        <FrontendLayout>
            <StorefrontSeo
                title={pageTitle}
                description={pageDescription}
                canonicalPath="/products"
                jsonLd={breadcrumbJsonLd}
            />

            <main className="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-6">
                    <StorefrontBreadcrumbs items={breadcrumbs} />
                </div>

                <ProductListingHeader
                    title={searchQuery !== '' ? 'Search Results' : 'Shop'}
                    total={products.total}
                />

                {searchQuery !== '' && (
                    <div className="mt-4 flex flex-col gap-3 rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <p className="min-w-0 text-sm text-neutral-600">
                            Search results for{' '}
                            <span className="font-semibold text-neutral-950">“{searchQuery}”</span>
                        </p>

                        <Link
                            href="/products"
                            className="inline-flex min-h-9 shrink-0 items-center justify-center rounded-lg border border-neutral-300 bg-white px-3 text-sm font-semibold text-neutral-800 transition hover:border-neutral-400 hover:bg-neutral-100"
                        >
                            View all products
                        </Link>
                    </div>
                )}

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
                        ) : searchQuery !== '' ? (
                            <div className="rounded-xl border border-dashed border-neutral-300 px-6 py-14 text-center">
                                <h2 className="text-lg font-semibold text-neutral-950">
                                    No products found
                                </h2>

                                <p className="mt-2 text-sm text-neutral-600">
                                    No published products matched{' '}
                                    <span className="font-semibold text-neutral-900">
                                        “{searchQuery}”
                                    </span>
                                    .
                                </p>

                                <div className="mt-5 flex flex-wrap items-center justify-center gap-3">
                                    <Link
                                        href="/products"
                                        className="inline-flex min-h-10 items-center justify-center rounded-lg bg-neutral-950 px-4 text-sm font-semibold text-white transition hover:bg-neutral-800"
                                    >
                                        View all products
                                    </Link>

                                    <button
                                        type="button"
                                        onClick={storefrontFilters.clearAll}
                                        className="inline-flex min-h-10 items-center justify-center rounded-lg border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-800 transition hover:bg-neutral-100"
                                    >
                                        Clear filters
                                    </button>
                                </div>
                            </div>
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
