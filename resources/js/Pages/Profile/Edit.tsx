import CustomerLayout from '@/Layouts/Customer/CustomerLayout'
import type { PageProps } from '@/types'
import DeleteUserForm from './Partials/DeleteUserForm'
import UpdateAvatarForm from './Partials/UpdateAvatarForm'
import UpdatePasswordForm from './Partials/UpdatePasswordForm'
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm'

const cardClass = 'rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm sm:p-8'

export default function Edit({
    mustVerifyEmail,
    status,
}: PageProps<{ mustVerifyEmail: boolean; status?: string }>) {
    return (
        <CustomerLayout title="My Profile" description="Manage your personal information, profile photo, and account security.">
            <div className="space-y-6">
                <div className={cardClass}>
                    <UpdateAvatarForm />
                </div>
                <div className={cardClass}>
                    <UpdateProfileInformationForm mustVerifyEmail={mustVerifyEmail} status={status} className="max-w-xl" />
                </div>
                <div className={cardClass}>
                    <UpdatePasswordForm className="max-w-xl" />
                </div>
                <div className={cardClass}>
                    <DeleteUserForm className="max-w-xl" />
                </div>
            </div>
        </CustomerLayout>
    )
}
