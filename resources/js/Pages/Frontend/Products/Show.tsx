import ProductDescription from '@/Components/Frontend/Catalog/ProductDetails/ProductDescription'
import ProductDetailsLayout from '@/Components/Frontend/Catalog/ProductDetails/ProductDetailsLayout'
import ProductGallery from '@/Components/Frontend/Catalog/ProductDetails/ProductGallery'
import ProductReviewForm from '@/Components/Frontend/Catalog/ProductDetails/ProductReviewForm'
import ProductReviewsSection from '@/Components/Frontend/Catalog/ProductDetails/ProductReviewsSection'
import RelatedProductsSection from '@/Components/Frontend/Catalog/ProductDetails/RelatedProductsSection'
import ProductSummary from '@/Components/Frontend/Catalog/ProductDetails/ProductSummary'
import StorefrontBreadcrumbs from '@/Components/Frontend/Navigation/StorefrontBreadcrumbs'
import StorefrontSeo from '@/Components/Frontend/Seo/StorefrontSeo'
import ProductEntityFacts from '@/Components/Frontend/Seo/ProductEntityFacts'
import WishlistToggleButton from '@/Components/Frontend/Wishlist/WishlistToggleButton'
import FrontendLayout from '@/Layouts/Frontend/FrontendLayout'

import type {
    StorefrontProductReviews,
    StorefrontProductReviewState,
} from '@/types/product-review'

import type {
    ResolvedSeo,
} from '@/types/seo'

import type {
    StructuredDataDocument,
} from '@/types/structured-data'

import type {
    StorefrontBreadcrumbItem,
    StorefrontProductDetail,
    StorefrontProductCard,
} from '@/types/storefront'

interface Props {
    product: StorefrontProductDetail
    shortDescriptionHtml?: string | null
    reviewState: StorefrontProductReviewState
    reviews: StorefrontProductReviews
    relatedProducts: StorefrontProductCard[]
    seo: ResolvedSeo

    structuredData:
        | StructuredDataDocument
        | null

    selectedVariantId:
        number | null
}

export default function Show({
    product,
    shortDescriptionHtml = null,
    reviewState,
    reviews,
    relatedProducts,
    seo,
    structuredData,
    selectedVariantId,
}: Props) {
    const preferredSocialImage =
        product.images[0] ?? null

    const primaryCategory =
        product.categories[0]
        ?? null

    const breadcrumbs:
        StorefrontBreadcrumbItem[] =
        [
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

            ...(primaryCategory
                ? [
                      {
                          label:
                              primaryCategory.name,

                          href:
                              `/category/${primaryCategory.slug}`,
                      },
                  ]
                : []),

            {
                label:
                    product.name,
            },
        ]

    return (
        <FrontendLayout>
            {/*
             * SEO and JSON-LD are authoritative Laravel
             * payloads.
             *
             * ?variant= is a storefront selection deep link;
             * it does not replace the base Product canonical.
             */}
            <StorefrontSeo
                seo={
                    seo
                }
                type="product"
                imageMetadata={
                    preferredSocialImage
                }
                jsonLd={
                    structuredData
                }
            />

            <main className="mx-auto w-full max-w-7xl min-w-0 px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
                <div className="mb-6 min-w-0">
                    <StorefrontBreadcrumbs
                        items={
                            breadcrumbs
                        }
                    />
                </div>

                <ProductDetailsLayout
                    media={
                        <ProductGallery
                            productName={
                                product.name
                            }
                            images={
                                product.images
                            }
                            video={
                                product.video
                            }
                        />
                    }
                    summary={
                        <div className="min-w-0">
                            <div className="mb-4 flex justify-end">
                                <WishlistToggleButton
                                    productId={
                                        product.id
                                    }
                                />
                            </div>

                            <ProductSummary
                                product={
                                    product
                                }
                                initialVariantId={
                                    selectedVariantId
                                }
                                shortDescriptionHtml={shortDescriptionHtml}
                            />
                        </div>
                    }
                />

                <ProductEntityFacts
                    product={
                        product
                    }
                />

                {product.description && (
                    <div className="mt-12 lg:mt-16">
                        <ProductDescription
                            description={
                                product.description
                            }
                        />
                    </div>
                )}

                <div className="mt-12 lg:mt-16">
                    <ProductReviewsSection
                        key={product.id}
                        productSlug={product.slug}
                        reviews={
                            reviews
                        }
                    />
                </div>

                <div className="mt-8">
                    <ProductReviewForm
                        productId={
                            product.id
                        }
                        state={
                            reviewState
                        }
                    />
                </div>
                <RelatedProductsSection products={relatedProducts} />
            </main>
        </FrontendLayout>
    )
}
