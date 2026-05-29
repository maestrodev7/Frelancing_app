import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function ClientIndex({ missions = [] }) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Mes missions
                    </h2>
                    <Link href={route('missions.create')}>
                        <PrimaryButton>Publier une mission</PrimaryButton>
                    </Link>
                </div>
            }
        >
            <Head title="Mes missions" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                    {missions.length === 0 ? (
                        <div className="rounded-lg bg-white p-8 text-center shadow-sm">
                            <p className="text-gray-600">
                                Vous n&apos;avez pas encore publié de mission.
                            </p>
                            <Link
                                href={route('missions.create')}
                                className="mt-4 inline-block text-indigo-600 underline"
                            >
                                Créer votre première mission
                            </Link>
                        </div>
                    ) : (
                        missions.map((mission) => (
                            <Link
                                key={mission.id}
                                href={route('missions.show', mission.id)}
                                className="block rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md"
                            >
                                <div className="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <h3 className="text-lg font-semibold text-gray-900">
                                            {mission.title}
                                        </h3>
                                        <p className="mt-2 text-sm text-gray-600">
                                            {mission.description}
                                        </p>
                                    </div>
                                    <span className="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">
                                        {mission.status_label}
                                    </span>
                                </div>
                                <div className="mt-4 flex flex-wrap gap-4 text-sm text-gray-500">
                                    <span>{mission.type_label}</span>
                                    {mission.budget_min != null && (
                                        <span>
                                            Budget : {mission.budget_min} –{' '}
                                            {mission.budget_max} {mission.currency}
                                        </span>
                                    )}
                                    <span>
                                        {mission.proposals_count ?? 0} proposition(s)
                                    </span>
                                    {(mission.pending_proposals_count ?? 0) > 0 && (
                                        <span className="font-medium text-indigo-600">
                                            {mission.pending_proposals_count} en attente
                                        </span>
                                    )}
                                </div>
                            </Link>
                        ))
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
