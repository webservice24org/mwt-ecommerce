import AppFlashToaster from '@/Components/AppFlashToaster'
import { PropsWithChildren } from 'react'

export default function AppLayout({ children }: PropsWithChildren) {
    return (
        <>
            <AppFlashToaster />
            {children}
        </>
    )
}
