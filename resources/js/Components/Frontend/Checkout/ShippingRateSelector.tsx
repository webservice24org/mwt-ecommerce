import { formatMoney } from '@/lib/money'

export interface ShippingRateQuote {
    shipping_method_id: number
    shipping_rate_id: number
    method_code: string
    method_name: string
    rate_code: string
    rate_name: string
    amount: number
    free: boolean
}

interface Props {
    rates: ShippingRateQuote[]
    value: number | null
    onChange: (shippingRateId: number) => void
}

export default function ShippingRateSelector({ rates, value, onChange }: Props) {
    return (
        <fieldset>
            <legend className="sr-only">Shipping method</legend>

            <div className="grid gap-3 sm:grid-cols-2">
                {rates.map((rate) => {
                    const selected = value === rate.shipping_rate_id

                    return (
                        <label
                            key={rate.shipping_rate_id}
                            className={[
                                'flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition',
                                selected
                                    ? 'border-neutral-950 bg-neutral-50 ring-1 ring-neutral-950'
                                    : 'border-neutral-200 bg-white hover:border-neutral-400',
                            ].join(' ')}
                        >
                            <input
                                type="radio"
                                name="shipping_rate_id"
                                value={rate.shipping_rate_id}
                                checked={selected}
                                onChange={() => onChange(rate.shipping_rate_id)}
                                className="mt-1 h-4 w-4 border-neutral-300 text-neutral-950 focus:ring-neutral-900"
                            />

                            <span className="min-w-0 flex-1">
                                <span className="block font-semibold text-neutral-950">
                                    {rate.method_name}
                                </span>

                                <span className="mt-1 block text-sm text-neutral-600">
                                    {rate.rate_name}
                                </span>

                                <span className="mt-3 block text-sm font-bold text-neutral-950">
                                    {rate.free ? 'Free delivery' : formatMoney(rate.amount)}
                                </span>
                            </span>
                        </label>
                    )
                })}
            </div>
        </fieldset>
    )
}
