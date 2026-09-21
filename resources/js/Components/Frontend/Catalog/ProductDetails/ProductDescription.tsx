interface ProductDescriptionProps {
    description: string | null
}

export default function ProductDescription({ description }: ProductDescriptionProps) {
    if (!description) {
        return null
    }

    return (
        <section
            className="min-w-0 border-t border-neutral-200 pt-8"
            aria-labelledby="product-description-heading"
        >
            <h2
                id="product-description-heading"
                className="break-words text-xl font-semibold tracking-tight text-neutral-950 sm:text-2xl"
            >
                Product description
            </h2>

            <div
                className={[
                    'mt-5 min-w-0 max-w-none break-words text-base leading-7 text-neutral-700',
                    '[overflow-wrap:anywhere]',
                    '[&_img]:h-auto [&_img]:max-w-full',
                    '[&_video]:h-auto [&_video]:max-w-full',
                    '[&_iframe]:max-w-full',
                    '[&_pre]:max-w-full [&_pre]:overflow-x-auto',
                    '[&_table]:block [&_table]:max-w-full [&_table]:overflow-x-auto',
                    '[&_a]:break-all',
                ].join(' ')}
                dangerouslySetInnerHTML={{
                    __html: description,
                }}
            />
        </section>
    )
}
