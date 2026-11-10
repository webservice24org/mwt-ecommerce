import { Link, useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'

import ShippingZoneForm from '@/Components/Admin/Shipping/ShippingZoneForm'
import AdminLayout from '@/Layouts/Admin/AdminLayout'
import ShippingAdminNav from '@/Components/Admin/Shipping/ShippingAdminNav'
import type {
    ShippingZoneEditItem,
    ShippingZoneFormData,
    ShippingZoneRuleOption,
} from '@/types/shipping'

type Props = {
    zone: ShippingZoneEditItem
    ruleTypes: ShippingZoneRuleOption[]
}

export default function Edit({
    zone,
    ruleTypes,
}: Props) {
    const form = useForm<ShippingZoneFormData>(
        `EditShippingZone:${zone.id}`,
        {
            name: zone.name,
            code: zone.code,
            is_active: zone.is_active,
            is_catch_all:
                zone.is_catch_all,
            priority: zone.priority,

            rules: zone.rules.map(
                (rule) => ({
                    type: rule.type,
                    value: rule.value,
                }),
            ),
        },
    )

    const submit = (
        event: FormEvent<HTMLFormElement>,
    ) => {
        event.preventDefault()

        form.put(
            route(
                'admin.shipping.zones.update',
                zone.id,
            ),
            {
                preserveScroll: true,
            },
        )
    }

    return (
        <AdminLayout
            title="Edit Shipping Zone"
            description={`Update ${zone.name} and its address matching rules.`}
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
                submitLabel="Save Changes"
                onSubmit={submit}
            />
        </AdminLayout>
    )
}