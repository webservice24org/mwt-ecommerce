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
    ShippingRateEditItem,
    ShippingRateFormData,
    ShippingRateFormOptions,
    ShippingRateZoneOption,
} from '@/types/shipping'

type Props = {
    rate: ShippingRateEditItem

    shippingMethods?: ShippingMethodOption[]

    zones?: ShippingRateZoneOption[]
}

export default function Edit({
    rate,
    shippingMethods = [],
    zones = [],
}: Props) {
    const options: ShippingRateFormOptions = {
        shippingMethods,
        zones,
    }

    const form =
        useForm<ShippingRateFormData>(
            `EditShippingRate:${rate.id}`,
            {
                shipping_method_id:
                    rate.shipping_method_id,

                name:
                    rate.name,

                code:
                    rate.code,

                amount:
                    rate.amount,

                min_order_amount:
                    rate.min_order_amount,

                max_order_amount:
                    rate.max_order_amount,

                free_shipping_threshold:
                    rate.free_shipping_threshold,

                is_active:
                    rate.is_active,

                priority:
                    rate.priority,

                zone_ids:
                    rate.zone_ids ?? [],
            },
        )

    const submit = (
        event: FormEvent<HTMLFormElement>,
    ) => {
        event.preventDefault()

        form.put(
            route(
                'admin.shipping.rates.update',
                rate.id,
            ),
            {
                preserveScroll: true,
            },
        )
    }

    return (
        <AdminLayout
            title="Edit Shipping Rate"
            description={`Update ${rate.name}, delivery cost and zone coverage.`}
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
                    currently available.
                    Existing rate data can be
                    reviewed, but a valid
                    shipping method is required
                    before saving.
                </div>
            ) : null}

            <ShippingAdminNav />
            <ShippingRateForm
                form={form}
                options={options}
                submitLabel="Save Changes"
                onSubmit={submit}
            />
        </AdminLayout>
    )
}