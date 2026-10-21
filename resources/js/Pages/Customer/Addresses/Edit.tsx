import { Link, useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'

import AddressForm from '@/Components/Customer/AddressForm'
import CustomerLayout from '@/Layouts/Customer/CustomerLayout'
import type { CustomerAddress, CustomerAddressFormData } from '@/types/customer-address'

interface Props {
    address: CustomerAddress
}

export default function Edit({ address }: Props) {
    const form = useForm<CustomerAddressFormData>({
        label: address.label ?? '',
        recipient_name: address.recipient_name,
        phone: address.phone,
        address_line_1: address.address_line_1,
        address_line_2: address.address_line_2 ?? '',
        city: address.city,
        state_or_region: address.state_or_region ?? '',
        postal_code: address.postal_code ?? '',
        country_code: address.country_code,
        is_default: address.is_default,
    })

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        form.put(route('customer.addresses.update', address.id), {
            preserveScroll: true,
        })
    }

    return (
        <CustomerLayout
            title="Edit Delivery Address"
            description="Update the delivery details saved to your account."
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
                    submitLabel="Save changes"
                    showDefaultOption={false}
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
