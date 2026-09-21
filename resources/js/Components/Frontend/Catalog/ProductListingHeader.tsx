interface ProductListingHeaderProps {
    title: string
    total: number
    description?: string | null
}

export default function ProductListingHeader({
    title,
    total,
    description,
}: ProductListingHeaderProps) {
    return (
        <header className="min-w-0">
            <h1 className="break-words text-2xl font-semibold tracking-tight text-neutral-950 sm:text-3xl">
                {title}
            </h1>

            {description && (
                <p className="mt-3 max-w-3xl break-words text-sm leading-6 text-neutral-600 sm:text-base">
                    {description}
                </p>
            )}

            <p className="mt-3 text-sm text-neutral-500" aria-live="polite">
                {total === 1 ? '1 product' : `${total} products`}
            </p>
        </header>
    )
}
