import CustomerAvatar from '@/Components/Customer/CustomerAvatar'
import InputError from '@/Components/InputError'
import { useForm, usePage } from '@inertiajs/react'
import { useEffect, useRef, useState } from 'react'
import type { FormEvent } from 'react'

export default function UpdateAvatarForm() {
    const { auth, customerAvatar } = usePage().props
    const name = auth.user.name
    const avatarUrl = typeof customerAvatar === 'string' ? customerAvatar : null
    const fileInput = useRef<HTMLInputElement>(null)
    const [preview, setPreview] = useState<string | null>(null)
    const upload = useForm<{ avatar: File | null }>({ avatar: null })
    const remove = useForm({})
    const selectedFile = upload.data.avatar
    const busy = upload.processing || remove.processing

    useEffect(() => {
        if (!selectedFile) {
            setPreview(null)
            return
        }

        const objectUrl = URL.createObjectURL(selectedFile)
        setPreview(objectUrl)
        return () => URL.revokeObjectURL(objectUrl)
    }, [selectedFile])

    function resetSelection() {
        upload.reset('avatar')
        upload.clearErrors('avatar')
        if (fileInput.current) fileInput.current.value = ''
    }

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault()
        if (!selectedFile || busy) return
        upload.post(route('customer.avatar.store'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: resetSelection,
        })
    }

    return (
        <section aria-labelledby="avatar-heading">
            <h2 id="avatar-heading" className="text-lg font-semibold text-neutral-900">Profile photo</h2>
            <p className="mt-1 text-sm text-neutral-600">Personalize your account. If you remove your photo, your initials will be displayed instead.</p>
            <form onSubmit={submit} className="mt-5 flex flex-col items-start gap-5 sm:flex-row sm:items-center">
                <CustomerAvatar name={name} src={preview ?? avatarUrl} className="h-24 w-24 text-3xl" />
                <div className="min-w-0 flex-1 space-y-3">
                    <label htmlFor="profile-avatar" className="block text-sm font-medium text-neutral-800">Choose a profile photo</label>
                    <input
                        ref={fileInput}
                        id="profile-avatar"
                        type="file"
                        accept="image/png,image/jpeg,image/webp"
                        disabled={busy}
                        aria-describedby="avatar-help"
                        className="block w-full max-w-md text-sm text-neutral-600 file:mr-3 file:rounded-lg file:border-0 file:bg-neutral-100 file:px-4 file:py-2 file:font-medium file:text-neutral-900 hover:file:bg-neutral-200"
                        onChange={(event) => {
                            upload.clearErrors('avatar')
                            upload.setData('avatar', event.target.files?.[0] ?? null)
                        }}
                    />
                    <p id="avatar-help" className="text-xs text-neutral-500">PNG, JPG or WebP. Up to 1 MB and 2000 × 2000 pixels. Your photo is private to your account.</p>
                    <InputError message={upload.errors.avatar} />
                    {upload.progress && <p role="status" className="text-sm text-neutral-600">Uploading: {upload.progress.percentage ?? 0}%</p>}
                    <div className="flex flex-wrap gap-2">
                        <button type="submit" disabled={!selectedFile || busy} className="rounded-lg bg-neutral-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-neutral-700 disabled:opacity-50">
                            {upload.processing ? 'Uploading…' : 'Save photo'}
                        </button>
                        {selectedFile && (
                            <button type="button" disabled={busy} onClick={resetSelection} className="rounded-lg border border-neutral-300 px-4 py-2.5 text-sm font-medium text-neutral-700 disabled:opacity-50">Cancel selection</button>
                        )}
                        {avatarUrl && (
                            <button type="button" disabled={busy} onClick={() => remove.delete(route('customer.avatar.destroy'), { preserveScroll: true, onSuccess: resetSelection })} className="rounded-lg border border-red-200 px-4 py-2.5 text-sm font-medium text-red-700 hover:bg-red-50 disabled:opacity-50">{remove.processing ? 'Removing…' : 'Remove photo'}</button>
                        )}
                    </div>
                </div>
            </form>
        </section>
    )
}
