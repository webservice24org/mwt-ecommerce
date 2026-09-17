import ProductForm, { type ProductFormData } from '@/Components/Admin/Catalog/ProductForm'
import AdminLayout from '@/Layouts/Admin/AdminLayout'
import type {
    ProductBrandOption,
    ProductCategoryOption,
    ProductStatusOption,
} from '@/types/catalog'
import { Link, useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'

type Props = {
    brands: ProductBrandOption[]
    categories: ProductCategoryOption[]
    statuses: ProductStatusOption[]
}

export default function Create({ brands, categories, statuses }: Props) {
    const form = useForm<ProductFormData>({
        brand_id: null,
        type: 'simple',
        sku: '',
        price: null,
        compare_at_price: null,
        cost_price: null,
        name: '',
        slug: '',
        short_description: '',
        description: '',
        status: 'draft',
        is_featured: false,
        position: 0,
        published_at: '',
        meta_title: '',
        meta_description: '',
        category_ids: [],
    })

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        form.post(route('admin.products.store'), {
            preserveScroll: true,
        })
    }

    return (
        <AdminLayout
            title="Add Product"
            description="Create a new catalog product."
            actions={
                <Link
                    href={route('admin.products.index')}
                    className="rounded-lg border border-neutral-300 bg-white px-4 py-2.5 text-sm font-medium text-neutral-700 hover:bg-neutral-50"
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
                submitLabel="Create Product"
                onSubmit={submit}
            />

            <div className="rounded-xl border border-dashed border-neutral-300 bg-neutral-50 p-5">
                <h3 className="font-medium text-neutral-900">Media & Variants</h3>

                <p className="mt-1 text-sm text-neutral-500">
                    Save the product first. You can then add the featured image, gallery, video and
                    product variants.
                </p>
            </div>
        </AdminLayout>
    )
}
