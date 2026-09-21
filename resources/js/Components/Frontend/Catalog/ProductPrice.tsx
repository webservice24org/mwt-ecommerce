import { formatMoney } from '@/lib/money'
import type { StorefrontPricing, StorefrontProductListingPricing } from '@/types/storefront'

type ProductPriceProps =
    | {
          mode: 'listing'
          pricing: StorefrontProductListingPricing
          className?: string
      }
    | {
          mode: 'resolved'
          pricing: StorefrontPricing
          className?: string
      }

export default function ProductPrice(props: ProductPriceProps) {
    if (props.mode === 'listing') {
        return <ListingPrice pricing={props.pricing} className={props.className} />
    }

    return <ResolvedPrice pricing={props.pricing} className={props.className} />
}

function ListingPrice({
    pricing,
    className = '',
}: {
    pricing: StorefrontProductListingPricing
    className?: string
}) {
    if (pricing.min_price === null) {
        return <p className={`text-sm text-neutral-500 ${className}`}>Price unavailable</p>
    }

    if (pricing.varies && pricing.max_price !== null) {
        return (
            <p
                className={`min-w-0 break-words text-base font-semibold leading-6 text-neutral-950 ${className}`}
            >
                <span className="sr-only">Price range: </span>
                {formatMoney(pricing.min_price)}
                <span aria-hidden="true"> – </span>
                <span className="sr-only">to </span>
                {formatMoney(pricing.max_price)}
            </p>
        )
    }

    return (
        <ResolvedPrice
            pricing={{
                price: pricing.min_price,
                compare_at_price: pricing.compare_at_price,
                on_sale: pricing.on_sale,
            }}
            className={className}
        />
    )
}

function ResolvedPrice({
    pricing,
    className = '',
}: {
    pricing: StorefrontPricing
    className?: string
}) {
    if (pricing.price === null) {
        return <p className={`text-sm text-neutral-500 ${className}`}>Price unavailable</p>
    }

    return (
        <div className={`flex min-w-0 flex-wrap items-baseline gap-x-2 gap-y-1 ${className}`}>
            <span className="whitespace-nowrap text-base font-semibold text-neutral-950">
                {formatMoney(pricing.price)}
            </span>

            {pricing.on_sale && pricing.compare_at_price !== null && (
                <>
                    <span className="sr-only">Original price:</span>

                    <del className="whitespace-nowrap text-sm font-normal text-neutral-500">
                        {formatMoney(pricing.compare_at_price)}
                    </del>
                </>
            )}
        </div>
    )
}
