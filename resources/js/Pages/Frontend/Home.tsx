import FrontendLayout from '@/Layouts/Frontend/FrontendLayout'
import { Head } from '@inertiajs/react'

export default function Home() {
    return (
        <FrontendLayout>
            <Head title="Home" />

            <section className="mx-auto w-full max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div className="max-w-2xl">
                    <p className="text-sm font-medium uppercase tracking-wider text-neutral-500">
                        Laravel + React Ecommerce
                    </p>

                    <h1 className="mt-3 text-3xl font-bold tracking-tight text-neutral-950 sm:text-4xl">
                        MWT Ecommerce
                    </h1>

                    <p className="mt-4 text-base leading-7 text-neutral-600">
                        The storefront foundation is ready. Products, categories and dynamic
                        homepage sections will be introduced throughout Phase 3.
                    </p>
                </div>
            </section>
        </FrontendLayout>
    )
}
