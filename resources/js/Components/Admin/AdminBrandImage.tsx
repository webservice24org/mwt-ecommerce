import { useState } from 'react'
import type { ReactNode } from 'react'

interface Props {
    url?: string
    className: string
    alt: string
    fallback?: ReactNode
}

export default function AdminBrandImage(props: Props) {
    return <ImageWithFallback key={props.url ?? ''} {...props} />
}

function ImageWithFallback({ url, className, alt, fallback = null }: Props) {
    const [failed, setFailed] = useState(false)
    if (!url || failed) return <>{fallback}</>
    return <img src={url} alt={alt} className={className} onError={() => setFailed(true)} />
}
