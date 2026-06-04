import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function AdminDashboard({ stats }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Administration
                </h2>
            }
        >
            <Head title="Administration" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="grid gap-4 sm:grid-cols-3">
                        <div className="rounded-lg bg-white p-6 shadow-sm">
                            <p className="text-sm text-gray-500">Utilisateurs</p>
                            <p className="mt-2 text-2xl font-semibold">
                                {stats.users_count}
                            </p>
                        </div>
                        <div className="rounded-lg bg-white p-6 shadow-sm">
                            <p className="text-sm text-gray-500">Litiges ouverts</p>
                            <p className="mt-2 text-2xl font-semibold text-red-600">
                                {stats.open_disputes_count}
                            </p>
                        </div>
                        <div className="rounded-lg bg-white p-6 shadow-sm">
                            <p className="text-sm text-gray-500">Missions en cours</p>
                            <p className="mt-2 text-2xl font-semibold">
                                {stats.missions_in_progress}
                            </p>
                        </div>
                    </div>

                    <div className="mt-6 flex gap-3">
                        <Link
                            href={route('admin.users.index')}
                            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                        >
                            Gérer les utilisateurs
                        </Link>
                        <Link
                            href={route('admin.disputes.index')}
                            className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Traiter les litiges
                        </Link>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
