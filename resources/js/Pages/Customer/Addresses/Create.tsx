import { Link, useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'

import AddressForm from '@/Components/Customer/AddressForm'
import CustomerLayout from '@/Layouts/Customer/CustomerLayout'
import type { CustomerAddressFormData } from '@/types/customer-address'

interface Props {
    defaults: {
        country_code: string
    }
}

export default function Create({ defaults }: Props) {
    const form = useForm<CustomerAddressFormData>({
        label: '',
        recipient_name: '',
        phone: '',
        address_line_1: '',
        address_line_2: '',
        city: '',
        state_or_region: '',
        postal_code: '',
        country_code: defaults.country_code,
        is_default: false,
    })

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        form.post(route('customer.addresses.store'), {
            preserveScroll: true,
        })
    }

    return (
        <CustomerLayout
            title="Add Delivery Address"
            description="Save a delivery address for your future orders."
            actions={
                <Link
                    href={route('customer.addresses.index')}
                    className="inline-flex min-h-11 items-center justify-center rounded-xl border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50"
                >
                    Back to addresses
                </Link>
            }
        >
            <form onSubmit={submit} className="mx-auto max-w-3xl">
                <AddressForm
                    data={form.data}
                    errors={form.errors}
                    processing={form.processing}
                    submitLabel="Save address"
                    onChange={(field, value) => {
                        form.setData({
                            ...form.data,
                            [field]: value,
                        })
                    }}
                />
            </form>
        </CustomerLayout>
    )
}
