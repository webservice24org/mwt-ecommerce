export const featureBenefitIconOptions = [
    {
        value: 'truck',
        label: 'Delivery',
    },
    {
        value: 'shield-check',
        label: 'Protection',
    },
    {
        value: 'headphones',
        label: 'Support',
    },
    {
        value: 'badge-check',
        label: 'Verified',
    },
    {
        value: 'award',
        label: 'Quality',
    },
    {
        value: 'package-check',
        label: 'Order Ready',
    },
    {
        value: 'refresh-ccw',
        label: 'Easy Returns',
    },
    {
        value: 'credit-card',
        label: 'Payment',
    },
    {
        value: 'lock',
        label: 'Secure',
    },
    {
        value: 'clock',
        label: 'Fast Service',
    },
    {
        value: 'gift',
        label: 'Gift',
    },
    {
        value: 'heart-handshake',
        label: 'Care',
    },
    {
        value: 'shopping-bag',
        label: 'Shopping',
    },
    {
        value: 'store',
        label: 'Store',
    },
    {
        value: 'box',
        label: 'Package',
    },
    {
        value: 'star',
        label: 'Featured',
    },
    {
        value: 'sparkles',
        label: 'Premium',
    },
    {
        value: 'leaf',
        label: 'Eco Friendly',
    },
    {
        value: 'zap',
        label: 'Fast',
    },
] as const

export type FeatureBenefitIconName = (typeof featureBenefitIconOptions)[number]['value']

export function isFeatureBenefitIconName(value: string | null): value is FeatureBenefitIconName {
    if (!value) {
        return false
    }

    return featureBenefitIconOptions.some((option) => option.value === value)
}
