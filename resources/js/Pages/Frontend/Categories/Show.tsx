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
    StorefrontCategoryDetail,
    StorefrontFilterOptions,
    StorefrontProductFilters,
} from '@/types/storefront'

interface Props {
    category: StorefrontCategoryDetail
    products: PaginatedStorefrontProducts
    filters: StorefrontProductFilters
    filterOptions: StorefrontFilterOptions
}

export default function Show({ category, products, filters, filterOptions }: Props) {
    const pageTitle = category.meta_title?.trim() || category.name

    const storefrontFilters = useStorefrontFilters({
        url: `/category/${category.slug}`,
        filters,
    })

    const metaDescription =
        category.meta_description?.trim() || category.description?.trim() || null

    const breadcrumbs: StorefrontBreadcrumbItem[] = [
        {
            label: 'Home',
            href: '/',
        },
        {
            label: 'Shop',
            href: '/products',
        },
        {
            label: category.name,
        },
    ]

    const breadcrumbJsonLd = buildBreadcrumbJsonLd(breadcrumbs)

    return (
        <FrontendLayout>
            <StorefrontSeo
                title={pageTitle}
                description={metaDescription}
                canonicalPath={`/category/${category.slug}`}
                image={category.image_url}
                jsonLd={breadcrumbJsonLd}
            />

            <main className="mx-auto w-full max-w-7xl min-w-0 px-4 py-8 sm:px-6 lg:px-8">
                <StorefrontBreadcrumbs items={breadcrumbs} />

                {category.image_url && (
                    <div className="mb-8 mt-5 overflow-hidden rounded-xl bg-neutral-100">
                        <img
                            src={category.image_url}
                            alt=""
                            loading="eager"
                            decoding="async"
                            className="max-h-80 w-full object-cover"
                        />
                    </div>
                )}

                <ProductListingHeader
                    title={category.name}
                    description={category.description}
                    total={products.total}
                />

                <div className="mt-6">
                    <StorefrontListingControls
                        filters={filters}
                        options={filterOptions}
                        showCategories={false}
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
                                showCategories={false}
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
                            <CategoryEmptyState
                                categoryName={category.name}
                                hasActiveFilters={hasActiveFilters(filters)}
                                onClearFilters={storefrontFilters.clearAll}
                            />
                        )}
                    </div>
                </div>
            </main>
        </FrontendLayout>
    )
}

interface CategoryEmptyStateProps {
    categoryName: string
    hasActiveFilters: boolean
    onClearFilters: () => void
}

function CategoryEmptyState({
    categoryName,
    hasActiveFilters,
    onClearFilters,
}: CategoryEmptyStateProps) {
    return (
        <div className="rounded-xl border border-dashed border-neutral-300 px-6 py-14 text-center">
            <h2 className="text-base font-medium text-neutral-950">No products found</h2>

            <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-neutral-600">
                {hasActiveFilters
                    ? `No products in ${categoryName} match the selected filters.`
                    : `There are currently no products available in ${categoryName}.`}
            </p>

            {hasActiveFilters && (
                <button
                    type="button"
                    onClick={onClearFilters}
                    className="mt-4 rounded-sm text-sm font-semibold text-neutral-900 underline underline-offset-4 transition hover:text-neutral-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 focus-visible:ring-offset-2"
                >
                    Clear filters
                </button>
            )}
        </div>
    )
}

function hasActiveFilters(filters: StorefrontProductFilters): boolean {
    return (
        filters.sort !== 'newest' ||
        filters.brand !== null ||
        filters.min_price !== null ||
        filters.max_price !== null ||
        Object.keys(filters.attributes).length > 0
    )
}
