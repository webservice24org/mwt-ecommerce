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
        meta_title: page.meta_title ?? '',
        meta_description: page.meta_description ?? '',
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
            description="Edit the details of your e-commerce page."
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
                onChange={form.setData}
                onSubmit={submit}
            />

            <section className="mt-6 rounded-xl border border-dashed border-neutral-300 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-950">
                <div className="flex flex-col gap-2">
                    <h2 className="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                        Page Sections
                    </h2>

                    <p className="text-sm text-neutral-500 dark:text-neutral-400">
                        This page currently has {page.sections.length}{' '}
                        {page.sections.length === 1 ? 'section' : 'sections'}. The visual section
                        editor is introduced in the next Page Builder step.
                    </p>
                </div>
            </section>
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
