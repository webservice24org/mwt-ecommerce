import { Head } from '@inertiajs/react'

export default function Home() {
    return (
        <>
            <Head title="Home" />

            <main className="min-h-screen bg-white">
                <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                    <h1 className="text-3xl font-bold">MWT Ecommerce</h1>

                    <p className="mt-3 text-neutral-600">Ecommerce foundation is ready.</p>
                </div>
            </main>
        </>
    )
}
