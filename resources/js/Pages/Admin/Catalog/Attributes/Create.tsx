import AttributeForm, { type AttributeFormData } from '@/Components/Admin/Catalog/AttributeForm'
import AdminLayout from '@/Layouts/Admin/AdminLayout'
import { Link, useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'

export default function Create() {
    const form = useForm<AttributeFormData>({
        name: '',
        slug: '',
        position: 0,
        is_active: true,
    })

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        form.post(route('admin.attributes.store'))
    }

    return (
        <AdminLayout
            title="Add Attribute"
            description="Create a product attribute such as Color or Size."
            actions={
                <Link
                    href={route('admin.attributes.index')}
                    className="rounded-lg border border-neutral-300 px-4 py-2 text-sm"
                >
                    Back to Attributes
                </Link>
            }
        >
            <div className="max-w-3xl">
                <AttributeForm form={form} submitLabel="Create Attribute" onSubmit={submit} />
            </div>
        </AdminLayout>
    )
}
