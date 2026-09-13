import BrandForm, { type BrandFormData } from '@/Components/Admin/Catalog/BrandForm'
import AdminLayout from '@/Layouts/Admin/AdminLayout'
import { Link, useForm } from '@inertiajs/react'

export default function Create() {
    const form = useForm<BrandFormData>({
        name: '',
        slug: '',
        description: '',
        logo: null,
        remove_logo: false,
        position: 0,
        is_active: true,
        meta_title: '',
        meta_description: '',
    })

    return (
        <AdminLayout
            title="Create Brand"
            description="Add a new product brand."
            actions={
                <Link
                    href={route('admin.brands.index')}
                    className="rounded-md border px-4 py-2 text-sm font-medium"
                >
                    Back to brands
                </Link>
            }
        >
            <BrandForm
                form={form}
                submitLabel="Create Brand"
                onSubmit={() => {
                    form.post(route('admin.brands.store'), {
                        forceFormData: true,
                    })
                }}
            />
        </AdminLayout>
    )
}
