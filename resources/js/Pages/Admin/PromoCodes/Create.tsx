import { Head } from '@inertiajs/react'

import AdminLayout from '@/Layouts/Admin/AdminLayout'

import PromoCodeForm from './PromoCodeForm'

type Props = {
    discountTypes: Array<{
        value: string
        label: string
    }>
    promotions: Array<{
        id: number
        name: string
    }>
}

export default function Create({
    discountTypes,
    promotions,
}: Props) {
    return (
        <AdminLayout title="Create Promo Code" description="Create a standalone code or inherit rules from a promotion.">
            <Head title="Create Promo Code" />

            <div className="mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
  
                <PromoCodeForm
                    discountTypes={discountTypes}
                    promotions={promotions}
                    submitLabel="Create promo code"
                    initialValues={{
                        promotion_id: '',
                        code: '',
                        name: '',
                        description: '',
                        discount_type: 'fixed',
                        discount_value: '',
                        maximum_discount: '',
                        minimum_subtotal: '',
                        usage_limit: '',
                        usage_limit_per_customer:
                            '',
                        starts_at: '',
                        ends_at: '',
                        is_active: true,
                        is_stackable: false,
                    }}
                    onSubmit={(form) =>
                        form.post(
                            route(
                                'admin.promo-codes.store',
                            ),
                        )
                    }
                />
            </div>
        </AdminLayout>
    )
}