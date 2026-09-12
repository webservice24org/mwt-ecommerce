import CustomerLayout from '@/Layouts/Customer/CustomerLayout'

export default function Dashboard() {
    return (
        <CustomerLayout title="My Account" description="Manage your account and shopping activity.">
            <div className="grid gap-4 md:grid-cols-3">
                <div className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
                    <p className="text-sm font-medium text-neutral-500">Orders</p>

                    <p className="mt-2 text-3xl font-bold text-neutral-900">0</p>
                </div>

                <div className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
                    <p className="text-sm font-medium text-neutral-500">Pending Orders</p>

                    <p className="mt-2 text-3xl font-bold text-neutral-900">0</p>
                </div>

                <div className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
                    <p className="text-sm font-medium text-neutral-500">Completed Orders</p>

                    <p className="mt-2 text-3xl font-bold text-neutral-900">0</p>
                </div>
            </div>

            <div className="mt-6 rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                <h3 className="text-lg font-semibold text-neutral-900">Welcome to your account</h3>

                <p className="mt-2 text-sm leading-6 text-neutral-500">
                    Your orders, addresses, profile information, and other account features will be
                    available here as we build the e-commerce modules.
                </p>
            </div>
        </CustomerLayout>
    )
}
