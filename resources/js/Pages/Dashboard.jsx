import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Dashboard() {
    const user = usePage().props.auth.user;
    const isClient = user?.role === 'client';

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Tableau de bord
                </h2>
            }
        >
            <Head title="Tableau de bord" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <p className="text-lg">
                                Bienvenue, {user?.name} !
                            </p>
                            <p className="mt-2 text-sm text-gray-600">
                                {isClient
                                    ? 'Publiez des missions et consultez les propositions des freelances.'
                                    : 'Parcourez les missions ouvertes et envoyez vos propositions.'}
                            </p>
                            <div className="mt-6 flex flex-wrap gap-3">
                                <Link href={route('missions.index')}>
                                    <PrimaryButton>
                                        {isClient
                                            ? 'Voir mes missions'
                                            : 'Voir les missions'}
                                    </PrimaryButton>
                                </Link>
                                {isClient && (
                                    <Link href={route('missions.create')}>
                                        <span className="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                            Publier une mission
                                        </span>
                                    </Link>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
