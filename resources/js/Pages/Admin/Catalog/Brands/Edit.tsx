import BrandForm, { type BrandFormData } from '@/Components/Admin/Catalog/BrandForm'
import AdminLayout from '@/Layouts/Admin/AdminLayout'
import type { Brand } from '@/types/catalog'
import { Link, useForm } from '@inertiajs/react'

type Props = {
    brand: Brand
}

export default function Edit({ brand }: Props) {
    const form = useForm<BrandFormData>({
        name: brand.name,
        slug: brand.slug,
        description: brand.description ?? '',
        logo: null,
        remove_logo: false,
        position: brand.position,
        is_active: brand.is_active,
        meta_title: brand.meta_title ?? '',
        meta_description: brand.meta_description ?? '',
    })

    return (
        <AdminLayout
            title="Edit Brand"
            description={`Update ${brand.name}.`}
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
                currentLogoUrl={brand.logo_url}
                submitLabel="Save Changes"
                onSubmit={() => {
                    form.transform((data) => ({
                        ...data,
                        _method: 'put',
                    }))

                    form.post(route('admin.brands.update', brand.id), {
                        forceFormData: true,
                        preserveScroll: true,
                    })
                }}
            />
        </AdminLayout>
    )
}
