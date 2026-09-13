type SeoStatus = {
    label: string
    textClass: string
    barClass: string
    borderClass: string
    backgroundClass: string
}

type Props = {
    metaTitle: string
    metaDescription: string
    titleError?: string
    descriptionError?: string
    onTitleChange: (value: string) => void
    onDescriptionChange: (value: string) => void
}

function getTitleStatus(length: number): SeoStatus {
    if (length === 0) {
        return {
            label: 'Not set',
            textClass: 'text-neutral-500',
            barClass: 'bg-neutral-300',
            borderClass: 'border-neutral-300',
            backgroundClass: 'bg-neutral-50',
        }
    }

    if (length >= 50 && length <= 60) {
        return {
            label: 'Good',
            textClass: 'text-green-700',
            barClass: 'bg-green-500',
            borderClass: 'border-green-300',
            backgroundClass: 'bg-green-50',
        }
    }

    if (length <= 65) {
        return {
            label: length < 50 ? 'Too short' : 'A little long',
            textClass: 'text-amber-700',
            barClass: 'bg-amber-400',
            borderClass: 'border-amber-300',
            backgroundClass: 'bg-amber-50',
        }
    }

    return {
        label: 'Too long',
        textClass: 'text-red-700',
        barClass: 'bg-red-500',
        borderClass: 'border-red-300',
        backgroundClass: 'bg-red-50',
    }
}

function getDescriptionStatus(length: number): SeoStatus {
    if (length === 0) {
        return {
            label: 'Not set',
            textClass: 'text-neutral-500',
            barClass: 'bg-neutral-300',
            borderClass: 'border-neutral-300',
            backgroundClass: 'bg-neutral-50',
        }
    }

    if (length >= 120 && length <= 155) {
        return {
            label: 'Good',
            textClass: 'text-green-700',
            barClass: 'bg-green-500',
            borderClass: 'border-green-300',
            backgroundClass: 'bg-green-50',
        }
    }

    if (length <= 160) {
        return {
            label: length < 120 ? 'Too short' : 'A little long',
            textClass: 'text-amber-700',
            barClass: 'bg-amber-400',
            borderClass: 'border-amber-300',
            backgroundClass: 'bg-amber-50',
        }
    }

    return {
        label: 'Too long',
        textClass: 'text-red-700',
        barClass: 'bg-red-500',
        borderClass: 'border-red-300',
        backgroundClass: 'bg-red-50',
    }
}

export default function SeoFields({
    metaTitle,
    metaDescription,
    titleError,
    descriptionError,
    onTitleChange,
    onDescriptionChange,
}: Props) {
    const titleLength = metaTitle.length
    const descriptionLength = metaDescription.length

    const titleStatus = getTitleStatus(titleLength)
    const descriptionStatus = getDescriptionStatus(descriptionLength)

    return (
        <div className="space-y-6 rounded-xl border border-neutral-200 bg-white p-5">
            <div>
                <h3 className="text-sm font-semibold text-neutral-900">
                    Search Engine Optimization
                </h3>

                <p className="mt-1 text-xs text-neutral-500">
                    Optimize how this content may appear in search results.
                </p>
            </div>

            <div>
                <div className="mb-1.5 flex items-center justify-between gap-3">
                    <label htmlFor="meta_title" className="text-sm font-medium text-neutral-900">
                        SEO Title
                    </label>

                    <div className="flex items-center gap-2 text-xs">
                        <span className={`font-semibold ${titleStatus.textClass}`}>
                            {titleStatus.label}
                        </span>

                        <span className="text-neutral-500">{titleLength}/60</span>
                    </div>
                </div>

                <input
                    id="meta_title"
                    type="text"
                    value={metaTitle}
                    onChange={(event) => onTitleChange(event.target.value)}
                    maxLength={180}
                    placeholder="SEO title for search engines"
                    className={[
                        'w-full rounded-lg border px-3 py-2.5 text-sm outline-none transition',
                        'focus:ring-2 focus:ring-neutral-900/10',
                        titleStatus.borderClass,
                        titleStatus.backgroundClass,
                    ].join(' ')}
                />

                <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-neutral-100">
                    <div
                        className={[
                            'h-full rounded-full transition-all duration-200',
                            titleStatus.barClass,
                        ].join(' ')}
                        style={{
                            width: `${Math.min((titleLength / 65) * 100, 100)}%`,
                        }}
                    />
                </div>

                <div className="mt-1.5 flex items-center justify-between gap-3">
                    <p className="text-xs text-neutral-500">Recommended: 50–60 characters.</p>

                    {titleLength > 60 && (
                        <p className={`text-xs font-medium ${titleStatus.textClass}`}>
                            {titleLength - 60} over recommended
                        </p>
                    )}
                </div>

                {titleError && <p className="mt-1 text-sm text-red-600">{titleError}</p>}
            </div>

            <div>
                <div className="mb-1.5 flex items-center justify-between gap-3">
                    <label
                        htmlFor="meta_description"
                        className="text-sm font-medium text-neutral-900"
                    >
                        SEO Description
                    </label>

                    <div className="flex items-center gap-2 text-xs">
                        <span className={`font-semibold ${descriptionStatus.textClass}`}>
                            {descriptionStatus.label}
                        </span>

                        <span className="text-neutral-500">{descriptionLength}/155</span>
                    </div>
                </div>

                <textarea
                    id="meta_description"
                    rows={4}
                    value={metaDescription}
                    onChange={(event) => onDescriptionChange(event.target.value)}
                    maxLength={320}
                    placeholder="Write a concise description for search results..."
                    className={[
                        'w-full resize-y rounded-lg border px-3 py-2.5 text-sm outline-none transition',
                        'focus:ring-2 focus:ring-neutral-900/10',
                        descriptionStatus.borderClass,
                        descriptionStatus.backgroundClass,
                    ].join(' ')}
                />

                <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-neutral-100">
                    <div
                        className={[
                            'h-full rounded-full transition-all duration-200',
                            descriptionStatus.barClass,
                        ].join(' ')}
                        style={{
                            width: `${Math.min((descriptionLength / 160) * 100, 100)}%`,
                        }}
                    />
                </div>

                <div className="mt-1.5 flex items-center justify-between gap-3">
                    <p className="text-xs text-neutral-500">Recommended: 120–155 characters.</p>

                    {descriptionLength > 155 && (
                        <p className={`text-xs font-medium ${descriptionStatus.textClass}`}>
                            {descriptionLength - 155} over recommended
                        </p>
                    )}
                </div>

                {descriptionError && (
                    <p className="mt-1 text-sm text-red-600">{descriptionError}</p>
                )}
            </div>
        </div>
    )
}
