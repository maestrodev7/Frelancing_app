import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function UsersIndex({ users = [] }) {
    const flash = usePage().props.flash?.status;
    const [pendingUser, setPendingUser] = useState(null);

    const confirmToggle = (user, newStatus) => {
        const action =
            newStatus === 'active' ? 'activer' : 'désactiver';
        const confirmed = window.confirm(
            `Confirmez-vous vouloir ${action} le compte de ${user.name} (${user.email}) ?`,
        );

        if (!confirmed) {
            return;
        }

        router.patch(route('admin.users.status', user.id), {
            status: newStatus,
            confirm: true,
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Utilisateurs du système
                </h2>
            }
        >
            <Head title="Utilisateurs" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {flash === 'user-status-updated' && (
                        <div className="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-800">
                            Statut utilisateur mis à jour.
                        </div>
                    )}

                    <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Nom
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Email
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Rôle
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Statut
                                    </th>
                                    <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {users.map((user) => (
                                    <tr key={user.id}>
                                        <td className="px-4 py-3 text-sm text-gray-900">
                                            {user.name}
                                        </td>
                                        <td className="px-4 py-3 text-sm text-gray-600">
                                            {user.email}
                                        </td>
                                        <td className="px-4 py-3 text-sm text-gray-600">
                                            {user.role_label}
                                        </td>
                                        <td className="px-4 py-3">
                                            <span
                                                className={`rounded-full px-2 py-1 text-xs font-medium ${
                                                    user.status === 'active'
                                                        ? 'bg-green-100 text-green-800'
                                                        : 'bg-red-100 text-red-800'
                                                }`}
                                            >
                                                {user.status_label}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-right text-sm">
                                            {user.status === 'active' ? (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        confirmToggle(
                                                            user,
                                                            'suspended',
                                                        )
                                                    }
                                                    className="text-red-600 hover:underline"
                                                >
                                                    Désactiver
                                                </button>
                                            ) : (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        confirmToggle(
                                                            user,
                                                            'active',
                                                        )
                                                    }
                                                    className="text-green-600 hover:underline"
                                                >
                                                    Activer
                                                </button>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
