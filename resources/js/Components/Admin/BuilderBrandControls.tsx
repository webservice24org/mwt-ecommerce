import { useEffect, useRef, useState } from 'react'

interface Props {
    section: 'header' | 'footer'
    mode?: 'text' | 'logo'
    url?: string | null
    disabled: boolean
    onChange: (mode: 'text' | 'logo', url: string | null) => void
}

export default function BuilderBrandControls({ section, mode, url, disabled, onChange }: Props) {
    const [uploading, setUploading] = useState(false)
    const [error, setError] = useState('')
    const request = useRef<AbortController | null>(null)
    const latest = useRef({ onChange, disabled })
    useEffect(() => { latest.current = { onChange, disabled } }, [onChange, disabled])
    useEffect(() => () => request.current?.abort(), [])

    async function upload(file: File) {
        setError('')
        if (file.size > 2 * 1024 * 1024 || !['image/png', 'image/jpeg', 'image/webp'].includes(file.type)) {
            setError('Choose a PNG, JPEG or WebP image up to 2 MB.')
            return
        }
        const controller = new AbortController()
        request.current = controller
        setUploading(true)
        try {
            const token = document.cookie.split('; ').find((part) => part.startsWith('XSRF-TOKEN='))?.slice(11)
            const headers: HeadersInit = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            if (token) headers['X-XSRF-TOKEN'] = decodeURIComponent(token)
            const body = new FormData()
            body.append('image', file)
            const response = await fetch(route(`admin.website-settings.${section}-builder.logo.store`), {
                method: 'POST', headers, body, credentials: 'same-origin', signal: controller.signal,
            })
            if (!response.ok) {
                if (response.status === 422) {
                    const payload = await response.json() as { errors?: { image?: string[] } }
                    throw new Error(payload.errors?.image?.[0] ?? 'Invalid logo image.')
                }
                throw new Error('Logo upload failed. Check your session and try again.')
            }
            const payload = await response.json() as { image: { url: string } }
            if (!controller.signal.aborted && !latest.current.disabled) latest.current.onChange('logo', payload.image.url)
        } catch (cause) {
            if (!controller.signal.aborted) setError(cause instanceof Error ? cause.message : 'Logo upload failed.')
        } finally {
            if (!controller.signal.aborted) setUploading(false)
        }
    }

    const inputClass = 'mt-2 block w-full min-w-0 rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 disabled:opacity-50'
    return (
        <div className="space-y-4 rounded-xl border border-neutral-200 p-4">
            <label className="block text-sm font-medium text-neutral-800">
                Brand display
                <select className={inputClass} value={mode ?? ''} disabled={disabled || uploading}
                    onChange={(event) => onChange(event.target.value === 'logo' ? 'logo' : 'text', url ?? null)}>
                    {!mode && <option value="" disabled>Existing design (unchanged)</option>}
                    <option value="text">Brand name only</option>
                    <option value="logo">Logo image only</option>
                </select>
            </label>
            <p className="text-xs text-neutral-500">Keep the brand name for image accessibility and fallback. Save the builder to publish your selection.</p>
            <label className="block text-sm font-medium text-neutral-800">
                Upload logo image
                <input type="file" accept="image/png,image/jpeg,image/webp" className={inputClass} disabled={disabled || uploading}
                    onChange={(event) => { const file = event.target.files?.[0]; event.target.value = ''; if (file) void upload(file) }} />
            </label>
            <p className="text-xs text-neutral-500">PNG, JPEG or WebP. Maximum 2 MB and 2400 × 2400 pixels. Transparent PNG or WebP recommended.</p>
            {uploading && <p role="status" className="text-sm text-indigo-600">Uploading logo…</p>}
            {error && <p role="alert" className="text-sm text-red-600">{error}</p>}
            <label className="block text-sm font-medium text-neutral-800">
                Logo image URL
                <input className={inputClass} type="text" maxLength={2048} value={url ?? ''} disabled={disabled || uploading}
                    placeholder="/storage/branding/logo.png" onChange={(event) => onChange(mode ?? 'logo', event.target.value || null)} />
            </label>
            {url && <button type="button" disabled={disabled || uploading} className="text-sm font-medium text-red-600" onClick={() => onChange('text', null)}>Remove logo from configuration</button>}
            {mode === 'logo' && !url && <p role="status" className="text-sm text-amber-700">Upload or enter a logo URL before saving.</p>}
        </div>
    )
}
