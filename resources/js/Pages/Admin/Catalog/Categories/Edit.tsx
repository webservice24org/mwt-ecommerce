import CategoryForm, { type CategoryFormData } from '@/Components/Admin/Catalog/CategoryForm'
import AdminLayout from '@/Layouts/Admin/AdminLayout'
import type { Category, CategoryParentOption } from '@/types/catalog'
import { Link, useForm } from '@inertiajs/react'

type Props = {
    category: Category
    parentCategories: CategoryParentOption[]
}

export default function Edit({ category, parentCategories }: Props) {
    const form = useForm<CategoryFormData>({
        parent_id: category.parent_id?.toString() ?? '',
        name: category.name,
        slug: category.slug,
        description: category.description ?? '',
        image: null,
        remove_image: false,
        position: category.position,
        is_active: category.is_active,
        meta_title: category.meta_title ?? '',
        meta_description: category.meta_description ?? '',
    })

    return (
        <AdminLayout
            title="Edit Category"
            description={`Update ${category.name}.`}
            actions={
                <Link
                    href={route('admin.categories.index')}
                    className="rounded-md border px-4 py-2 text-sm font-medium"
                >
                    Back to categories
                </Link>
            }
        >
            <CategoryForm
                form={form}
                parentCategories={parentCategories}
                currentImageUrl={category.image_url}
                submitLabel="Save Changes"
                onSubmit={() => {
                    form.transform((data) => ({
                        ...data,
                        _method: 'put',
                    }))

                    form.post(route('admin.categories.update', category.id), {
                        forceFormData: true,
                        preserveScroll: true,
                    })
                }}
            />
        </AdminLayout>
    )
}
