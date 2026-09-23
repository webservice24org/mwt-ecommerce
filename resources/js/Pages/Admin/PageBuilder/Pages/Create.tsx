import { Link, useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'

import AdminLayout from '@/Layouts/Admin/AdminLayout'
import type { PageFormOptions, PageFormValues } from '@/types/page-builder'

import PageForm from './Partials/PageForm'

interface Props {
    options: PageFormOptions
}

export default function Create({ options }: Props) {
    const form = useForm<PageFormValues>({
        type: 'standard',
        title: '',
        slug: '',
        status: 'draft',
        content_mode: 'classic',
        content: '',
        meta_title: '',
        meta_description: '',
        published_at: '',
    })

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        form.post(route('admin.pages.store'))
    }

    return (
        <AdminLayout
            title="Create Page"
            description="Create a classic content page or build a section-based page for your e-commerce store."
            actions={
                <Link
                    href={route('admin.pages.index')}
                    className="inline-flex items-center rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200 dark:hover:bg-neutral-800"
                >
                    Back to Pages
                </Link>
            }
        >
            <PageForm
                data={form.data}
                options={options}
                errors={form.errors}
                processing={form.processing}
                submitLabel="Create Page"
                sections={[]}
                featuredImage={null}
                onChange={form.setData}
                onSubmit={submit}
            />
        </AdminLayout>
    )
}
