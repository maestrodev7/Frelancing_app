import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function FreelancerIndex({ missions = [] }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Missions disponibles
                </h2>
            }
        >
            <Head title="Missions disponibles" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                    {missions.length === 0 ? (
                        <div className="rounded-lg bg-white p-8 text-center shadow-sm">
                            <p className="text-gray-600">
                                Aucune mission ouverte pour le moment.
                            </p>
                        </div>
                    ) : (
                        missions.map((mission) => (
                            <div
                                key={mission.id}
                                className="rounded-lg bg-white p-6 shadow-sm"
                            >
                                <h3 className="text-lg font-semibold text-gray-900">
                                    {mission.title}
                                </h3>
                                <p className="mt-2 text-sm text-gray-600">
                                    {mission.description}
                                </p>
                                <div className="mt-4 flex flex-wrap gap-4 text-sm text-gray-500">
                                    <span>Par {mission.client?.name}</span>
                                    <span>{mission.type_label}</span>
                                    {mission.budget_min != null && (
                                        <span>
                                            Budget : {mission.budget_min} –{' '}
                                            {mission.budget_max} {mission.currency}
                                        </span>
                                    )}
                                    {mission.deadline_at && (
                                        <span>Échéance : {mission.deadline_at}</span>
                                    )}
                                </div>
                                <Link
                                    href={route('missions.show', mission.id)}
                                    className="mt-4 inline-flex rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                                >
                                    Consulter et postuler
                                </Link>
                            </div>
                        ))
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
