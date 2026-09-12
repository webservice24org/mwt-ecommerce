import AdminLayout from '@/Layouts/Admin/AdminLayout'

export default function Dashboard() {
    return (
        <AdminLayout title="Dashboard" description="Overview of your e-commerce administration.">
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
                    <p className="text-sm font-medium text-neutral-500">Orders</p>

                    <p className="mt-2 text-3xl font-bold text-neutral-900">0</p>
                </div>

                <div className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
                    <p className="text-sm font-medium text-neutral-500">Customers</p>

                    <p className="mt-2 text-3xl font-bold text-neutral-900">0</p>
                </div>

                <div className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
                    <p className="text-sm font-medium text-neutral-500">Products</p>

                    <p className="mt-2 text-3xl font-bold text-neutral-900">0</p>
                </div>

                <div className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
                    <p className="text-sm font-medium text-neutral-500">Revenue</p>

                    <p className="mt-2 text-3xl font-bold text-neutral-900">৳0</p>
                </div>
            </div>

            <div className="mt-6 grid gap-6 xl:grid-cols-3">
                <div className="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm xl:col-span-2">
                    <h3 className="text-lg font-semibold text-neutral-900">Recent Orders</h3>

                    <p className="mt-2 text-sm text-neutral-500">
                        Order information will appear here once the order module is implemented.
                    </p>
                </div>

                <div className="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <h3 className="text-lg font-semibold text-neutral-900">Store Activity</h3>

                    <p className="mt-2 text-sm text-neutral-500">
                        Recent store activity will appear here.
                    </p>
                </div>
            </div>
        </AdminLayout>
    )
}
