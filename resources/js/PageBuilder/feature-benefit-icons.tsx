import {
    Award,
    BadgeCheck,
    Box,
    Clock,
    CreditCard,
    Gift,
    Headphones,
    HeartHandshake,
    Leaf,
    Lock,
    PackageCheck,
    RefreshCcw,
    ShieldCheck,
    ShoppingBag,
    Sparkles,
    Star,
    Store,
    Truck,
    Zap,
} from 'lucide-react'

interface FeatureBenefitIconProps {
    name: string | null
    className?: string
    strokeWidth?: number
}

export default function FeatureBenefitIcon({
    name,
    className,
    strokeWidth,
}: FeatureBenefitIconProps) {
    const props = {
        'aria-hidden': true as const,
        className,
        strokeWidth,
    }

    switch (name) {
        case 'truck':
            return <Truck {...props} />

        case 'shield-check':
            return <ShieldCheck {...props} />

        case 'headphones':
            return <Headphones {...props} />

        case 'badge-check':
            return <BadgeCheck {...props} />

        case 'award':
            return <Award {...props} />

        case 'package-check':
            return <PackageCheck {...props} />

        case 'refresh-ccw':
            return <RefreshCcw {...props} />

        case 'credit-card':
            return <CreditCard {...props} />

        case 'lock':
            return <Lock {...props} />

        case 'clock':
            return <Clock {...props} />

        case 'gift':
            return <Gift {...props} />

        case 'heart-handshake':
            return <HeartHandshake {...props} />

        case 'shopping-bag':
            return <ShoppingBag {...props} />

        case 'store':
            return <Store {...props} />

        case 'box':
            return <Box {...props} />

        case 'star':
            return <Star {...props} />

        case 'sparkles':
            return <Sparkles {...props} />

        case 'leaf':
            return <Leaf {...props} />

        case 'zap':
            return <Zap {...props} />

        default:
            return null
    }
}
