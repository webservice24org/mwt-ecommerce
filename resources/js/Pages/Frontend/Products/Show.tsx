import ProductDescription from '@/Components/Frontend/Catalog/ProductDetails/ProductDescription'
import ProductDetailsLayout from '@/Components/Frontend/Catalog/ProductDetails/ProductDetailsLayout'
import ProductGallery from '@/Components/Frontend/Catalog/ProductDetails/ProductGallery'
import ProductSummary from '@/Components/Frontend/Catalog/ProductDetails/ProductSummary'
import StorefrontBreadcrumbs from '@/Components/Frontend/Navigation/StorefrontBreadcrumbs'
import StorefrontSeo from '@/Components/Frontend/Seo/StorefrontSeo'
import FrontendLayout from '@/Layouts/Frontend/FrontendLayout'
import { buildBreadcrumbJsonLd } from '@/lib/storefrontSeo'
import type { StorefrontBreadcrumbItem, StorefrontProductDetail } from '@/types/storefront'

interface Props {
    product: StorefrontProductDetail
}

export default function Show({ product }: Props) {
    const pageTitle = product.meta_title?.trim() || product.name

    const metaDescription =
        product.meta_description?.trim() || product.short_description?.trim() || null

    const canonicalPath = `/products/${product.slug}`

    const seoImage = product.images[0]?.url ?? null

    const primaryCategory = product.categories[0] ?? null

    const breadcrumbs: StorefrontBreadcrumbItem[] = [
        {
            label: 'Home',
            href: '/',
        },
        {
            label: 'Shop',
            href: '/products',
        },
        ...(primaryCategory
            ? [
                  {
                      label: primaryCategory.name,
                      href: `/category/${primaryCategory.slug}`,
                  },
              ]
            : []),
        {
            label: product.name,
        },
    ]

    const breadcrumbJsonLd = buildBreadcrumbJsonLd(breadcrumbs)

    return (
        <FrontendLayout>
            <StorefrontSeo
                title={pageTitle}
                description={metaDescription}
                canonicalPath={canonicalPath}
                image={seoImage}
                type="product"
                jsonLd={breadcrumbJsonLd}
            />

            <main className="mx-auto w-full max-w-7xl min-w-0 px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
                <div className="mb-6 min-w-0">
                    <StorefrontBreadcrumbs items={breadcrumbs} />
                </div>

                <ProductDetailsLayout
                    media={
                        <ProductGallery
                            productName={product.name}
                            images={product.images}
                            video={product.video}
                        />
                    }
                    summary={<ProductSummary product={product} />}
                />

                {product.description && (
                    <div className="mt-12 lg:mt-16">
                        <ProductDescription description={product.description} />
                    </div>
                )}
            </main>
        </FrontendLayout>
    )
}
