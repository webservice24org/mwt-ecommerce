import { Toaster } from 'react-hot-toast'

export default function ToastProvider() {
    return (
        <Toaster
            position="top-right"
            reverseOrder={false}
            gutter={10}
            toastOptions={{
                duration: 4000,
                style: {
                    maxWidth: '420px',
                    background: 'var(--mwt-surface)',
                    color: 'var(--mwt-text)',
                    border: '1px solid var(--mwt-border)',
                },
                success: {
                    duration: 3500,
                },
                error: {
                    duration: 5000,
                },
            }}
        />
    )
}
