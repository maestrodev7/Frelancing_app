import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdateFreelancerProfileForm from './Partials/UpdateFreelancerProfileForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({
    mustVerifyEmail,
    status,
    countries,
    freelancerProfile,
}) {
    const user = usePage().props.auth.user;
    const isFreelancer = user.role === 'freelancer';

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Profil
                </h2>
            }
        >
            <Head title="Profil" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <UpdateProfileInformationForm
                            mustVerifyEmail={mustVerifyEmail}
                            status={status}
                            countries={countries}
                            className="max-w-xl"
                        />
                    </div>

                    {isFreelancer && (
                        <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                            <UpdateFreelancerProfileForm
                                profile={freelancerProfile}
                                className="max-w-3xl"
                            />
                        </div>
                    )}

                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <UpdatePasswordForm className="max-w-xl" />
                    </div>

                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <DeleteUserForm className="max-w-xl" />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
