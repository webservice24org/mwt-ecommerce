import { PageProps as AppPageProps } from '@/types'
import { usePage } from '@inertiajs/react'
import { useEffect } from 'react'
import { notify } from '@/lib/toast'

type Flash = {
    success?: string
    error?: string
}

type PageProps = AppPageProps & {
    flash?: Flash
}

export default function AppFlashToaster() {
    const { flash } = usePage<PageProps>().props

    useEffect(() => {
        if (flash?.success) {
            notify.success(flash.success)
        }

        if (flash?.error) {
            notify.error(flash.error)
        }
    }, [flash?.success, flash?.error])

    return null
}
