import toast from 'react-hot-toast'

export const notify = {
    success(message: string) {
        return toast.success(message)
    },

    error(message: string) {
        return toast.error(message)
    },

    loading(message: string) {
        return toast.loading(message)
    },

    dismiss(toastId?: string) {
        toast.dismiss(toastId)
    },

    promise<T>(
        promise: Promise<T>,
        messages: {
            loading: string
            success: string
            error: string
        },
    ) {
        return toast.promise(promise, messages)
    },
}
