import { Head } from '@inertiajs/react'

import AdminLayout from '@/Layouts/Admin/AdminLayout'

import PromotionForm from './PromotionForm'

type Promotion = {
    id: number
    name: string
    slug: string
    description: string | null
    discount_type: string
    discount_value: number
    maximum_discount: number | null
    minimum_subtotal: number | null
    starts_at: string | null
    ends_at: string | null
    is_active: boolean
    is_stackable: boolean
}

type Props = {
    promotion: Promotion
    discountTypes: Array<{
        value: string
        label: string
    }>
}

const localDateTime = (
    value: string | null,
): string => {
    if (!value) {
        return ''
    }

    return value.slice(0, 16)
}

export default function Edit({
    promotion,
    discountTypes,
}: Props) {
    return (
        <AdminLayout title={`Edit ${promotion.name}`} description="Update campaign rules and availability.">
            <Head title={`Edit ${promotion.name}`} />

            <div className="mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
                

                <PromotionForm
                    discountTypes={discountTypes}
                    submitLabel="Save changes"
                    initialValues={{
                        name: promotion.name,
                        slug: promotion.slug,
                        description:
                            promotion.description ?? '',
                        discount_type:
                            promotion.discount_type,
                        discount_value:
                            String(
                                promotion.discount_value,
                            ),
                        maximum_discount:
                            promotion.maximum_discount ===
                            null
                                ? ''
                                : String(
                                      promotion.maximum_discount,
                                  ),
                        minimum_subtotal:
                            promotion.minimum_subtotal ===
                            null
                                ? ''
                                : String(
                                      promotion.minimum_subtotal,
                                  ),
                        starts_at:
                            localDateTime(
                                promotion.starts_at,
                            ),
                        ends_at:
                            localDateTime(
                                promotion.ends_at,
                            ),
                        is_active:
                            promotion.is_active,
                        is_stackable:
                            promotion.is_stackable,
                    }}
                    onSubmit={(form) =>
                        form.put(
                            route(
                                'admin.promotions.update',
                                promotion.id,
                            ),
                        )
                    }
                />
            </div>
        </AdminLayout>
    )
}