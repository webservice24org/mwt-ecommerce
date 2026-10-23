interface ProductDescriptionProps {
    description: string | null
}

export default function ProductDescription({ description }: ProductDescriptionProps) {
    if (!description?.trim()) {
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
                    'mt-6 min-w-0 max-w-none break-words text-base leading-7 text-neutral-700',
                    '[overflow-wrap:anywhere]',

                    // Paragraphs
                    '[&_p]:my-4',
                    '[&_p:first-child]:mt-0',
                    '[&_p:last-child]:mb-0',

                    // Headings
                    '[&_h1]:mb-4 [&_h1]:mt-8',
                    '[&_h1]:text-3xl [&_h1]:font-bold',
                    '[&_h1]:leading-tight [&_h1]:tracking-tight',
                    '[&_h1]:text-neutral-950',

                    '[&_h2]:mb-4 [&_h2]:mt-8',
                    '[&_h2]:text-2xl [&_h2]:font-bold',
                    '[&_h2]:leading-tight [&_h2]:tracking-tight',
                    '[&_h2]:text-neutral-950',

                    '[&_h3]:mb-3 [&_h3]:mt-7',
                    '[&_h3]:text-xl [&_h3]:font-semibold',
                    '[&_h3]:leading-snug',
                    '[&_h3]:text-neutral-950',

                    '[&_h4]:mb-3 [&_h4]:mt-6',
                    '[&_h4]:text-lg [&_h4]:font-semibold',
                    '[&_h4]:text-neutral-950',

                    '[&_h5]:mb-2 [&_h5]:mt-5',
                    '[&_h5]:text-base [&_h5]:font-semibold',
                    '[&_h5]:text-neutral-950',

                    '[&_h6]:mb-2 [&_h6]:mt-5',
                    '[&_h6]:text-sm [&_h6]:font-semibold',
                    '[&_h6]:uppercase [&_h6]:tracking-wide',
                    '[&_h6]:text-neutral-700',

                    // Strong / emphasis
                    '[&_strong]:font-semibold',
                    '[&_strong]:text-neutral-950',
                    '[&_b]:font-semibold',
                    '[&_b]:text-neutral-950',
                    '[&_em]:italic',
                    '[&_i]:italic',
                    '[&_u]:underline',
                    '[&_s]:line-through',

                    // Subscript / superscript
                    '[&_sub]:text-xs',
                    '[&_sup]:text-xs',

                    // Lists
                    '[&_ul]:my-4',
                    '[&_ul]:list-disc',
                    '[&_ul]:space-y-1',
                    '[&_ul]:pl-6',

                    '[&_ol]:my-4',
                    '[&_ol]:list-decimal',
                    '[&_ol]:space-y-1',
                    '[&_ol]:pl-6',

                    '[&_li]:pl-1',
                    '[&_li>p]:my-1',

                    // Nested lists
                    '[&_li_ul]:my-2',
                    '[&_li_ol]:my-2',

                    // Links
                    '[&_a]:break-words',
                    '[&_a]:font-medium',
                    '[&_a]:text-blue-700',
                    '[&_a]:underline',
                    '[&_a]:decoration-blue-300',
                    '[&_a]:underline-offset-2',
                    '[&_a:hover]:text-blue-800',
                    '[&_a:hover]:decoration-blue-500',

                    // Blockquotes
                    '[&_blockquote]:my-6',
                    '[&_blockquote]:border-l-4',
                    '[&_blockquote]:border-neutral-300',
                    '[&_blockquote]:bg-neutral-50',
                    '[&_blockquote]:px-5',
                    '[&_blockquote]:py-3',
                    '[&_blockquote]:italic',
                    '[&_blockquote]:text-neutral-600',

                    // Horizontal rule
                    '[&_hr]:my-8',
                    '[&_hr]:border-0',
                    '[&_hr]:border-t',
                    '[&_hr]:border-neutral-200',

                    // Inline code
                    '[&_code]:break-words',
                    '[&_code]:rounded',
                    '[&_code]:bg-neutral-100',
                    '[&_code]:px-1.5',
                    '[&_code]:py-0.5',
                    '[&_code]:font-mono',
                    '[&_code]:text-[0.875em]',
                    '[&_code]:text-neutral-900',

                    // Code blocks
                    '[&_pre]:my-6',
                    '[&_pre]:max-w-full',
                    '[&_pre]:overflow-x-auto',
                    '[&_pre]:rounded-lg',
                    '[&_pre]:bg-neutral-950',
                    '[&_pre]:p-4',
                    '[&_pre]:text-sm',
                    '[&_pre]:leading-6',
                    '[&_pre]:text-neutral-100',

                    '[&_pre_code]:bg-transparent',
                    '[&_pre_code]:p-0',
                    '[&_pre_code]:text-inherit',

                    // Images
                    '[&_img]:my-6',
                    '[&_img]:mx-auto',
                    '[&_img]:block',
                    '[&_img]:h-auto',
                    '[&_img]:max-w-full',
                    '[&_img]:rounded-lg',

                    // Figure / captions
                    '[&_figure]:my-6',
                    '[&_figure]:max-w-full',

                    '[&_figcaption]:mt-2',
                    '[&_figcaption]:text-center',
                    '[&_figcaption]:text-sm',
                    '[&_figcaption]:text-neutral-500',

                    // Native video
                    '[&_video]:my-6',
                    '[&_video]:h-auto',
                    '[&_video]:max-w-full',
                    '[&_video]:rounded-lg',

                    // Embedded video / iframe
                    '[&_iframe]:my-6',
                    '[&_iframe]:aspect-video',
                    '[&_iframe]:h-auto',
                    '[&_iframe]:max-w-full',
                    '[&_iframe]:w-full',
                    '[&_iframe]:rounded-lg',
                    '[&_iframe]:border-0',

                    // Tables
                    '[&_table]:my-6',
                    '[&_table]:block',
                    '[&_table]:w-full',
                    '[&_table]:max-w-full',
                    '[&_table]:overflow-x-auto',
                    '[&_table]:border-collapse',
                    '[&_table]:text-sm',

                    '[&_thead]:bg-neutral-50',

                    '[&_th]:border',
                    '[&_th]:border-neutral-200',
                    '[&_th]:px-4',
                    '[&_th]:py-3',
                    '[&_th]:text-left',
                    '[&_th]:font-semibold',
                    '[&_th]:text-neutral-900',

                    '[&_td]:border',
                    '[&_td]:border-neutral-200',
                    '[&_td]:px-4',
                    '[&_td]:py-3',
                    '[&_td]:align-top',

                    // Preserve empty editor blocks reasonably
                    '[&_p:empty]:min-h-[1em]',
                ].join(' ')}
                dangerouslySetInnerHTML={{
                    __html: description,
                }}
            />
        </section>
    )
}
