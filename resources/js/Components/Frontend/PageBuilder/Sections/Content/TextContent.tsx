import { useId } from 'react'

import {
    contentAlignmentClasses,
    contentBodyClasses,
    contentHeadingClasses,
    type ContentSectionConfig,
} from './content-config'

interface Props {
    config: ContentSectionConfig
}

export default function TextContent({ config }: Props) {
    const headingId = useId()

    if (!config.body) {
        return null
    }

    return (
        <section
            aria-labelledby={config.heading ? headingId : undefined}
            className="min-w-0 py-8 sm:py-10 lg:py-12"
        >
            <div
                className={[
                    'flex min-w-0 flex-col',
                    contentAlignmentClasses[config.alignment],
                ].join(' ')}
            >
                {config.heading && (
                    <h2 id={headingId} dir="auto" className={`max-w-4xl ${contentHeadingClasses}`}>
                        {config.heading}
                    </h2>
                )}

                <p
                    dir="auto"
                    className={[
                        `max-w-4xl ${contentBodyClasses}`,
                        config.heading ? 'mt-4 sm:mt-5' : '',
                    ].join(' ')}
                >
                    {config.body}
                </p>
            </div>
        </section>
    )
}
