import { Link, useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'

import AdminLayout from '@/Layouts/Admin/AdminLayout'
import type { PageData, PageFormOptions, PageFormValues } from '@/types/page-builder'

import PageForm from './Partials/PageForm'

interface Props {
    page: PageData
    options: PageFormOptions
}

export default function Edit({ page, options }: Props) {
    const form = useForm<PageFormValues>({
        type: page.type,
        title: page.title,
        slug: page.slug,
        status: page.status,
        content_mode: page.content_mode,
        content: page.content ?? '',
        meta_title: page.seo.meta_title ?? '',
        meta_description: page.seo.meta_description ?? '',
        published_at: toDateTimeLocal(page.published_at),
    })

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        form.put(route('admin.pages.update', page.id), {
            preserveScroll: true,
        })
    }

    return (
        <AdminLayout
            title={`Edit ${page.title}`}
            description="Edit page details, content, publishing settings, and SEO."
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
                submitLabel="Save Changes"
                pageId={page.id}
                featuredImage={page.featured_image}
                onChange={form.setData}
                onSubmit={submit}
            />
        </AdminLayout>
    )
}

function toDateTimeLocal(value: string | null): string {
    if (!value) {
        return ''
    }

    const date = new Date(value)

    if (Number.isNaN(date.getTime())) {
        return ''
    }

    const offset = date.getTimezoneOffset() * 60_000

    return new Date(date.getTime() - offset).toISOString().slice(0, 16)
}
