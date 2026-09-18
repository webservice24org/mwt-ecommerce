interface ProductDescriptionProps {
    description: string | null
}

export default function ProductDescription({ description }: ProductDescriptionProps) {
    if (!description) {
        return null
    }

    return (
        <section
            className="border-t border-neutral-200 pt-8"
            aria-labelledby="product-description-heading"
        >
            <h2
                id="product-description-heading"
                className="text-2xl font-semibold tracking-tight text-neutral-950"
            >
                Product description
            </h2>

            <div
                className="mt-5 max-w-none text-base leading-7 text-neutral-700"
                dangerouslySetInnerHTML={{
                    __html: description,
                }}
            />
        </section>
    )
}
