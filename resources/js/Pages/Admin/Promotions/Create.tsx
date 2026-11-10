import { Head } from '@inertiajs/react'

import AdminLayout from '@/Layouts/Admin/AdminLayout'

import PromotionForm from './PromotionForm'

type Props = {
    discountTypes: Array<{
        value: string
        label: string
    }>
}

export default function Create({
    discountTypes,
}: Props) {
    return (
        <AdminLayout title="Create Promotion" description="Configure campaign discount rules and scheduling.">
            <Head title="Create Promotion" />

            <div className="mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
                

                <PromotionForm
                    discountTypes={discountTypes}
                    submitLabel="Create promotion"
                    initialValues={{
                        name: '',
                        slug: '',
                        description: '',
                        discount_type: 'fixed',
                        discount_value: '',
                        maximum_discount: '',
                        minimum_subtotal: '',
                        starts_at: '',
                        ends_at: '',
                        is_active: true,
                        is_stackable: false,
                    }}
                    onSubmit={(form) =>
                        form.post(
                            route(
                                'admin.promotions.store',
                            ),
                        )
                    }
                />
            </div>
        </AdminLayout>
    )
}