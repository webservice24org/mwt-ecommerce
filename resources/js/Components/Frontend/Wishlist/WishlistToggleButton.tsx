import { Heart } from 'lucide-react'
import { useState } from 'react'

interface Props {
    productId: number
    initialActive: boolean
    onChanged?: (active: boolean, count: number) => void
}

interface WishlistToggleResponse {
    active: boolean
    count: number
}

export default function WishlistToggleButton({ productId, initialActive, onChanged }: Props) {
    const [active, setActive] = useState(initialActive)

    const [processing, setProcessing] = useState(false)

    const toggle = async () => {
        if (processing) {
            return
        }

        setProcessing(true)

        try {
            const response = await fetch(`/wishlist/${productId}/toggle`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
            })

            if (!response.ok) {
                throw new Error('Unable to update wishlist.')
            }

            const result = (await response.json()) as WishlistToggleResponse

            setActive(result.active)

            onChanged?.(result.active, result.count)
        } finally {
            setProcessing(false)
        }
    }

    return (
        <button
            type="button"
            disabled={processing}
            aria-pressed={active}
            aria-label={active ? 'Remove from wishlist' : 'Add to wishlist'}
            onClick={() => {
                void toggle()
            }}
            className={[
                'inline-flex h-10 w-10 items-center justify-center rounded-full border bg-white transition',
                active
                    ? 'border-rose-200 text-rose-600'
                    : 'border-neutral-200 text-neutral-600 hover:text-rose-600',
                processing ? 'cursor-wait opacity-60' : '',
            ].join(' ')}
        >
            <Heart
                className={['h-5 w-5', active ? 'fill-current' : ''].join(' ')}
                aria-hidden="true"
            />
        </button>
    )
}

function csrfToken(): string {
    const element = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')

    return element?.content ?? ''
}
