import AppFlashToaster from '@/Components/AppFlashToaster'
import AdminHeader from '@/Components/Admin/AdminHeader'
import AdminSidebar from '@/Components/Admin/AdminSidebar'
import { Head } from '@inertiajs/react'
import { PropsWithChildren, ReactNode, useState } from 'react'

type Props = PropsWithChildren<{
    title: string
    description?: string
    actions?: ReactNode
}>

export default function AdminLayout({ title, description, actions, children }: Props) {
    const [sidebarOpen, setSidebarOpen] = useState(false)

    return (
        <>
            <Head title={title} />

            <AppFlashToaster />

            <div className="min-h-screen overflow-x-hidden bg-neutral-50 dark:bg-neutral-950">
                <AdminSidebar open={sidebarOpen} onClose={() => setSidebarOpen(false)} />

                <div className="min-w-0 lg:pl-72">
                    <AdminHeader title={title} onMenuClick={() => setSidebarOpen(true)} />

                    <main className="min-w-0">
                        <div className="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                            <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div className="min-w-0">
                                    <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                        {title}
                                    </h1>

                                    {description && (
                                        <p className="mt-1 max-w-3xl text-sm text-neutral-500 dark:text-neutral-400">
                                            {description}
                                        </p>
                                    )}
                                </div>

                                {actions && (
                                    <div className="flex shrink-0 flex-wrap items-center gap-2">
                                        {actions}
                                    </div>
                                )}
                            </div>

                            <div className="min-w-0">{children}</div>
                        </div>
                    </main>
                </div>
            </div>
        </>
    )
}
