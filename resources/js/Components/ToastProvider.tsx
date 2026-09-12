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
