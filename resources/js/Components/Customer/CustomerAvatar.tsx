import { useState } from 'react'

export function customerInitials(name: string): string {
    const words = name.trim().split(/\s+/u).filter(Boolean)
    const first = words[0]
    const last = words[words.length - 1]

    if (!first) return '?'

    // Preserve Unicode code points, including non-Latin names.
    const initials = words.length > 1
        ? `${Array.from(first)[0] ?? ''}${Array.from(last ?? '')[0] ?? ''}`
        : Array.from(first).slice(0, 2).join('')

    return initials.toLocaleUpperCase() || '?'
}

interface Props {
    name: string
    src?: string | null
    className?: string
}

export default function CustomerAvatar({ name, src, className = 'h-12 w-12 text-base' }: Props) {
    const [failedSrc, setFailedSrc] = useState<string | null>(null)
    const showImage = Boolean(src && failedSrc !== src)

    return (
        <span className={`relative inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-neutral-900 font-semibold text-white ${className}`}>
            {showImage && src ? (
                <img
                    src={src}
                    alt={`${name}'s profile photo`}
                    className="h-full w-full object-cover"
                    onError={() => setFailedSrc(src)}
                />
            ) : (
                <span aria-label={`${name || 'Customer'} initials`}>{customerInitials(name)}</span>
            )}
        </span>
    )
}
