import ProductForm, { type ProductFormData } from '@/Components/Admin/Catalog/ProductForm'
import ProductMediaPanel from '@/Components/Admin/Catalog/ProductMediaPanel'
import AdminLayout from '@/Layouts/Admin/AdminLayout'
import ProductVariantsPanel from '@/Components/Admin/Catalog/ProductVariantsPanel'
import type {
    ProductBrandOption,
    ProductCategoryOption,
    ProductFormProduct,
    ProductStatusOption,
} from '@/types/catalog'
import { Link, useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'
import type {
    ProductVariant,
    VariantAttributeOption,
    ProductImage,
    ProductVideo,
} from '@/types/catalog'

type Props = {
    product: ProductFormProduct
    brands: ProductBrandOption[]
    categories: ProductCategoryOption[]
    statuses: ProductStatusOption[]
    variants: ProductVariant[]
    variantAttributes: VariantAttributeOption[]
    images: ProductImage[]
    video: ProductVideo | null
}

export default function Edit({
    product,
    brands,
    categories,
    statuses,
    variants,
    variantAttributes,
    images,
    video,
}: Props) {
    const form = useForm<ProductFormData>(`EditProduct:${product.id}`, {
        brand_id: product.brand_id ?? null,
        type: product.type,
        sku: product.sku ?? '',
        price: product.price,
        compare_at_price: product.compare_at_price,
        cost_price: product.cost_price,
        name: product.name ?? '',
        slug: product.slug ?? '',
        short_description: product.short_description ?? '',
        description: product.description ?? '',
        status: product.status,
        is_featured: product.is_featured ?? false,
        position: product.position ?? 0,
        published_at: product.published_at ?? '',
        meta_title: product.meta_title ?? '',
        meta_description: product.meta_description ?? '',
        category_ids: product.category_ids ?? [],
    })

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        form.put(route('admin.products.update', product.id), {
            preserveScroll: true,
        })
    }

    return (
        <AdminLayout
            title="Edit Product"
            description={product.name}
            actions={
                <Link
                    href={route('admin.products.index')}
                    className="rounded-lg border border-neutral-300 bg-white px-4 py-2.5 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50"
                >
                    Back to Products
                </Link>
            }
        >
            <ProductForm
                form={form}
                brands={brands}
                categories={categories}
                statuses={statuses}
                submitLabel="Update Product"
                onSubmit={submit}
            />
            <div className="mt-8">
                <ProductMediaPanel productId={product.id} images={images} video={video} />
            </div>
            <div className="mt-6">
                {product.type === 'variable' ? (
                    <ProductVariantsPanel
                        productId={product.id}
                        productPrice={product.price}
                        productCompareAtPrice={product.compare_at_price}
                        productCostPrice={product.cost_price}
                        variants={variants}
                        attributes={variantAttributes}
                    />
                ) : (
                    <section className="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                        <h2 className="font-semibold text-neutral-900">Product Variants</h2>

                        <p className="mt-2 text-sm text-neutral-500">
                            Variants are available only for variable products. Change the product
                            type to Variable and save the product before adding variants.
                        </p>
                    </section>
                )}
            </div>
        </AdminLayout>
    )
}
