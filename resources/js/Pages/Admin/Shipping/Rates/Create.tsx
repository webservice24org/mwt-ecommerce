import {
    Link,
    useForm,
} from '@inertiajs/react'
import type { FormEvent } from 'react'

import ShippingRateForm from '@/Components/Admin/Shipping/ShippingRateForm'
import AdminLayout from '@/Layouts/Admin/AdminLayout'
import ShippingAdminNav from '@/Components/Admin/Shipping/ShippingAdminNav'

import type {
    ShippingMethodOption,
    ShippingRateFormData,
    ShippingRateFormOptions,
    ShippingRateZoneOption,
} from '@/types/shipping'

type Props = {
    shippingMethods?: ShippingMethodOption[]
    zones?: ShippingRateZoneOption[]
}

export default function Create({
    shippingMethods = [],
    zones = [],
}: Props) {
    const options: ShippingRateFormOptions = {
        shippingMethods,
        zones,
    }

    const defaultMethodId =
        shippingMethods.find(
            (method) =>
                method.is_active,
        )?.id ??
        shippingMethods[0]?.id ??
        null

    const form =
        useForm<ShippingRateFormData>({
            shipping_method_id:
                defaultMethodId,

            name: '',

            code: '',

            amount: 0,

            min_order_amount:
                null,

            max_order_amount:
                null,

            free_shipping_threshold:
                null,

            is_active: true,

            priority: 100,

            zone_ids: [],
        })

    const submit = (
        event: FormEvent<HTMLFormElement>,
    ) => {
        event.preventDefault()

        form.post(
            route(
                'admin.shipping.rates.store',
            ),
            {
                preserveScroll: true,
            },
        )
    }

    return (
        <AdminLayout
            title="Create Shipping Rate"
            description="Create a delivery price and decide where it is available."
            actions={
                <Link
                    href={route(
                        'admin.shipping.rates.index',
                    )}
                    className="rounded-lg border border-neutral-300 bg-white px-4 py-2.5 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50"
                >
                    Back to Shipping Rates
                </Link>
            }
        >
            {shippingMethods.length ===
            0 ? (
                <div className="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-800">
                    No shipping methods are
                    currently available. A
                    shipping method must exist
                    before a shipping rate can
                    be created.
                </div>
            ) : null}

            <ShippingAdminNav />
            <ShippingRateForm
                form={form}
                options={options}
                submitLabel="Create Shipping Rate"
                preferZonesOnCreate
                onSubmit={submit}
            />
        </AdminLayout>
    )
}