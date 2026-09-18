import ProductDescription from '@/Components/Frontend/Catalog/ProductDetails/ProductDescription'
import ProductDetailsLayout from '@/Components/Frontend/Catalog/ProductDetails/ProductDetailsLayout'
import ProductPrimaryMedia from '@/Components/Frontend/Catalog/ProductDetails/ProductPrimaryMedia'
import ProductSummary from '@/Components/Frontend/Catalog/ProductDetails/ProductSummary'
import FrontendLayout from '@/Layouts/Frontend/FrontendLayout'
import type { StorefrontProductDetail } from '@/types/storefront'
import { Head } from '@inertiajs/react'

interface Props {
    product: StorefrontProductDetail
}

export default function Show({ product }: Props) {
    const pageTitle = product.meta_title?.trim() || product.name

    const metaDescription =
        product.meta_description?.trim() || product.short_description?.trim() || null

    return (
        <FrontendLayout>
            <Head title={pageTitle}>
                {metaDescription && (
                    <meta head-key="description" name="description" content={metaDescription} />
                )}
            </Head>

            <main className="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <ProductDetailsLayout
                    media={
                        <ProductPrimaryMedia productName={product.name} images={product.images} />
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
