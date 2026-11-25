import { useState } from 'react'

export default function BrandLogoImage({ url, name, className }: { url: string; name: string; className: string }) {
    const [failed, setFailed] = useState(false)
    return failed ? (
        <span className="min-w-0 max-w-[96px] truncate font-bold text-white sm:max-w-[160px] lg:max-w-[220px]">{name}</span>
    ) : (
        <img src={url} alt={name} className={className} onError={() => setFailed(true)} />
    )
}
