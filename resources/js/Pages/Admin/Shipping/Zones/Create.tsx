import { Link, useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'

import ShippingZoneForm from '@/Components/Admin/Shipping/ShippingZoneForm'
import AdminLayout from '@/Layouts/Admin/AdminLayout'
import ShippingAdminNav from '@/Components/Admin/Shipping/ShippingAdminNav'
import type {
    ShippingZoneFormData,
    ShippingZoneRuleOption,
} from '@/types/shipping'

type Props = {
    ruleTypes: ShippingZoneRuleOption[]
}

export default function Create({
    ruleTypes,
}: Props) {
    const form = useForm<ShippingZoneFormData>({
        name: '',
        code: '',
        is_active: true,
        is_catch_all: false,
        priority: 100,
        rules: [],
    })

    const submit = (
        event: FormEvent<HTMLFormElement>,
    ) => {
        event.preventDefault()

        form.post(
            route(
                'admin.shipping.zones.store',
            ),
            {
                preserveScroll: true,
            },
        )
    }

    return (
        <AdminLayout
            title="Create Shipping Zone"
            description="Define a delivery area that can later be assigned its own shipping rates."
            actions={
                <Link
                    href={route(
                        'admin.shipping.zones.index',
                    )}
                    className="rounded-lg border border-neutral-300 bg-white px-4 py-2.5 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50"
                >
                    Back to Shipping Zones
                </Link>
            }
        >
            <ShippingAdminNav />
            <ShippingZoneForm
                form={form}
                ruleTypes={ruleTypes}
                submitLabel="Create Shipping Zone"
                onSubmit={submit}
            />
        </AdminLayout>
    )
}