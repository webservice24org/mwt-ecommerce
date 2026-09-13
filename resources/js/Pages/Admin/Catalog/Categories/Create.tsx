import CategoryForm, { type CategoryFormData } from '@/Components/Admin/Catalog/CategoryForm'
import AdminLayout from '@/Layouts/Admin/AdminLayout'
import type { CategoryParentOption } from '@/types/catalog'
import { Link, useForm } from '@inertiajs/react'

type Props = {
    parentCategories: CategoryParentOption[]
}

export default function Create({ parentCategories }: Props) {
    const form = useForm<CategoryFormData>({
        parent_id: '',
        name: '',
        slug: '',
        description: '',
        image: null,
        remove_image: false,
        position: 0,
        is_active: true,
        meta_title: '',
        meta_description: '',
    })

    return (
        <AdminLayout
            title="Create Category"
            description="Add a new catalog category."
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
                submitLabel="Create Category"
                onSubmit={() => form.post(route('admin.categories.store'))}
            />
        </AdminLayout>
    )
}
