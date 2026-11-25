import AdminBrandImage from '@/Components/Admin/AdminBrandImage'
import { adminLoginBackground, normalizeAdminBranding } from '@/lib/adminBranding'
import type { AdminBranding, BackgroundMode, BrandingImageKey } from '@/lib/adminBranding'
import { router, usePage } from '@inertiajs/react'
import { useEffect, useRef, useState } from 'react'
import type { FormEvent } from 'react'

const fields: { key: BrandingImageKey; label: string; help: string; limit: number }[] = [
    { key: 'login_logo', label: 'Login logo', help: 'Displayed above the Admin Login title.', limit: 2 },
    { key: 'sidebar_logo', label: 'Sidebar logo', help: 'Replaces MWT Admin and Ecommerce Control. Links to the dashboard.', limit: 2 },
    { key: 'background_image', label: 'Login background image', help: 'Fills the login page using cover sizing. The image may be cropped on mobile.', limit: 5 },
]
const control = 'mt-2 block w-full min-w-0 rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100 disabled:opacity-50'

export default function AdminBrandingEditor({ canUpdate }: { canUpdate: boolean }) {
    const saved = normalizeAdminBranding(usePage().props.adminBranding)
    const [draft, setDraft] = useState<AdminBranding>(saved)
    const [processing, setProcessing] = useState(false)
    const [uploading, setUploading] = useState<BrandingImageKey | null>(null)
    const [uploadError, setUploadError] = useState('')
    const [errors, setErrors] = useState<Record<string, string>>({})
    const pending = useRef<AbortController | null>(null)
    useEffect(() => () => pending.current?.abort(), [])
    const disabled = !canUpdate || processing || uploading !== null
    const dirty = JSON.stringify(normalizeAdminBranding(draft)) !== JSON.stringify(saved)
    const mode = draft.background_mode ?? 'default'

    function changeMode(value: BackgroundMode) {
        setDraft(current => ({ ...current, background_mode: value, ...(value === 'color' ? { background_color: current.background_color ?? '#f1f5f9' } : {}) }))
    }

    function removeImage(key: BrandingImageKey) {
        setDraft(current => {
            const next = { ...current }
            delete next[key]
            if (key === 'background_image' && next.background_mode === 'image') next.background_mode = 'default'
            return next
        })
    }

    async function upload(key: BrandingImageKey, file: File, limit: number) {
        if (disabled || pending.current) return
        setUploadError('')
        if (file.size > limit * 1024 * 1024 || (file.type && !['image/png', 'image/jpeg', 'image/webp'].includes(file.type))) {
            setUploadError(`Choose a PNG, JPEG or WebP file up to ${limit} MB.`)
            return
        }
        const controller = new AbortController()
        pending.current = controller
        setUploading(key)
        try {
            const token = document.cookie.split('; ').find(part => part.startsWith('XSRF-TOKEN='))?.slice(11)
            const headers: Record<string, string> = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            if (token) headers['X-XSRF-TOKEN'] = decodeURIComponent(token)
            const body = new FormData()
            body.append('image', file)
            const response = await fetch(route('admin.website-settings.theme-settings.admin-branding.upload', { kind: key }), {
                method: 'POST', headers, body, credentials: 'same-origin', signal: controller.signal,
            })
            if (!response.ok) {
                if (response.status === 422) {
                    const payload = await response.json() as { errors?: { image?: string[] } }
                    throw new Error(payload.errors?.image?.[0] ?? 'Invalid image.')
                }
                throw new Error('Upload failed. Check your session or server upload limit and try again.')
            }
            const payload = await response.json() as { image?: { url?: string } }
            const url = normalizeAdminBranding({ [key]: payload.image?.url })[key]
            if (!url) throw new Error('The server returned an invalid image reference.')
            if (!controller.signal.aborted) setDraft(current => ({ ...current, [key]: url, ...(key === 'background_image' ? { background_mode: 'image' as const } : {}) }))
        } catch (cause) {
            if (!controller.signal.aborted) setUploadError(cause instanceof Error ? cause.message : 'Upload failed.')
        } finally {
            pending.current = null
            if (!controller.signal.aborted) setUploading(null)
        }
    }

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault()
        if (disabled || !dirty) return
        setErrors({})
        router.put(route('admin.website-settings.theme-settings.admin-branding.update'), { admin_branding: normalizeAdminBranding(draft) }, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
            onError: setErrors,
            onSuccess: page => setDraft(normalizeAdminBranding(page.props.adminBranding)),
        })
    }

    return (
        <form id="admin-branding" onSubmit={submit} className="min-w-0 space-y-6 p-4 sm:p-6">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div><h2 className="text-xl font-semibold">Admin Branding</h2><p className="mt-1 text-sm text-neutral-500">Customize the admin login and sidebar. Changes take effect after saving.</p></div>
                <button type="submit" disabled={disabled || !dirty || (mode === 'image' && !draft.background_image)} className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">{processing ? 'Saving…' : 'Save changes'}</button>
            </div>
            {!canUpdate && <p role="status" className="text-sm text-neutral-500">You have read-only access.</p>}
            {Object.keys(errors).length > 0 && <div role="alert" className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{Object.entries(errors).map(([key, message]) => <p key={key}>{key.replace(/^admin_branding\.?/, '').replace(/_/g, ' ')}: {message}</p>)}</div>}
            {uploadError && <p role="alert" className="text-sm text-red-600 dark:text-red-400">{uploadError}</p>}
            <div className="grid min-w-0 items-start gap-6 xl:grid-cols-[minmax(0,1fr)_300px]">
                <fieldset disabled={disabled} className="min-w-0 space-y-5">
                    <section className="space-y-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-800">
                        <h3 className="font-semibold">Login background</h3>
                        <label className="block text-sm font-medium">Background type
                            <select className={control} value={mode} onChange={event => changeMode(event.target.value as BackgroundMode)}>
                                <option value="default">Default (follows appearance)</option>
                                <option value="color">Solid color</option>
                                <option value="image">Full-page image</option>
                            </select>
                        </label>
                        <label className="block text-sm font-medium">Background color / image fallback
                            <span className="mt-2 flex items-center gap-3"><input type="color" value={draft.background_color ?? '#f1f5f9'} onChange={event => setDraft(current => ({ ...current, background_color: event.target.value }))} className="h-10 w-14 rounded border border-neutral-300 p-1" /><span className="text-xs text-neutral-500">{draft.background_color ?? 'Default'}</span></span>
                        </label>
                        <p className="text-xs text-neutral-500">Custom backgrounds stay the same in Light and Dark mode. The login form follows the selected appearance.</p>
                        {mode === 'image' && !draft.background_image && <p role="status" className="text-sm text-amber-700 dark:text-amber-300">Upload an image before saving.</p>}
                    </section>
                    {fields.map(({ key, label, help, limit }) => <section key={key} className="space-y-3 rounded-xl border border-neutral-200 p-4 dark:border-neutral-800">
                        <h3 className="font-semibold">{label}</h3>
                        <p className="text-sm text-neutral-500">{help}</p>
                        {draft[key] && <AdminBrandImage url={draft[key]} alt={`${label} preview`} className="max-h-28 max-w-full rounded object-contain" fallback={<p className="text-sm text-amber-700 dark:text-amber-300">Image unavailable. Replace or remove it.</p>} />}
                        <label className="block text-sm font-medium">{draft[key] ? 'Replace image' : 'Upload image'}
                            <input type="file" accept="image/png,image/jpeg,image/webp" className={control} onChange={event => { const file = event.target.files?.[0]; event.target.value = ''; if (file) void upload(key, file, limit) }} />
                        </label>
                        <p className="text-xs text-neutral-500">PNG, JPEG, WebP · Max {limit} MB · {limit === 5 ? '4096 × 4096' : '2400 × 2400'} pixels</p>
                        {draft[key] && <button type="button" onClick={() => removeImage(key)} className="text-sm font-medium text-red-600 underline dark:text-red-400">Remove image</button>}
                    </section>)}
                    <div className="flex flex-wrap gap-3">
                        <button type="button" disabled={!Object.keys(draft).length} onClick={() => setDraft({})} className="rounded-lg border border-neutral-300 px-3 py-2 text-sm disabled:opacity-50">Reset all to defaults</button>
                        <button type="button" disabled={!dirty} onClick={() => setDraft(saved)} className="text-sm underline disabled:opacity-50">Discard unsaved changes</button>
                    </div>
                </fieldset>
                <section className="min-w-0 space-y-4 xl:sticky xl:top-6">
                    <h3 className="font-semibold">Live preview</h3>
                    <div className="flex min-h-72 items-center justify-center rounded-xl border border-neutral-200 bg-neutral-100 p-5 dark:border-neutral-800 dark:bg-neutral-950" style={adminLoginBackground(draft)}>
                        <div className="w-full max-w-56 space-y-3 rounded-2xl bg-white p-4 shadow-sm dark:bg-neutral-900">
                            <AdminBrandImage url={draft.login_logo} alt="Login logo preview" className="mx-auto max-h-16 max-w-full object-contain" />
                            <p className="font-semibold">Admin Login</p><div className="h-7 rounded border border-neutral-300 dark:border-neutral-700" /><div className="h-7 rounded border border-neutral-300 dark:border-neutral-700" /><div className="rounded-lg bg-indigo-600 p-2 text-center text-xs text-white">Sign in</div>
                        </div>
                    </div>
                    <div className="rounded-xl p-4" style={{ background: 'var(--mwt-admin-gradient)' }} data-mwt-theme-fixed="true">
                        <AdminBrandImage url={draft.sidebar_logo} alt="Sidebar logo preview" className="max-h-12 max-w-full object-contain" fallback={<p className="font-bold text-white">MWT Admin</p>} />
                        <p className="mt-4 text-xs text-indigo-100">Dashboard · Products · Orders</p>
                    </div>
                    <p role="status" className="text-xs text-neutral-500">{uploading ? 'Uploading image…' : dirty ? 'Unsaved changes. Save to publish your branding.' : 'All changes saved.'}</p>
                    <p className="text-xs text-neutral-500">The fixed developer credit is not editable here.</p>
                </section>
            </div>
        </form>
    )
}
