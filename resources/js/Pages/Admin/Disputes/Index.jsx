import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function DisputesIndex({ disputes = [] }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Litiges
                </h2>
            }
        >
            <Head title="Litiges" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                    {disputes.length === 0 ? (
                        <p className="rounded-lg bg-white p-6 text-gray-600 shadow-sm">
                            Aucun litige pour le moment.
                        </p>
                    ) : (
                        disputes.map((dispute) => (
                            <Link
                                key={dispute.id}
                                href={route('admin.disputes.show', dispute.id)}
                                className="block rounded-lg bg-white p-6 shadow-sm hover:shadow-md"
                            >
                                <div className="flex justify-between">
                                    <h3 className="font-semibold text-gray-900">
                                        {dispute.mission.title}
                                    </h3>
                                    <span
                                        className={`rounded-full px-3 py-1 text-xs font-medium ${
                                            dispute.status === 'open'
                                                ? 'bg-red-100 text-red-800'
                                                : 'bg-green-100 text-green-800'
                                        }`}
                                    >
                                        {dispute.status_label}
                                    </span>
                                </div>
                                <p className="mt-2 text-sm text-gray-600">
                                    {dispute.reason}
                                </p>
                                <p className="mt-2 text-xs text-gray-500">
                                    Client : {dispute.mission.client_name} ·
                                    Ouvert par {dispute.opened_by}
                                </p>
                            </Link>
                        ))
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
