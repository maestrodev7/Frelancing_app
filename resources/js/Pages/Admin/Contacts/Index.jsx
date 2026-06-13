import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, usePage } from '@inertiajs/react';

export default function ContactsIndex({ messages = [] }) {
    const flash = usePage().props.flash?.status;
    const newCount = messages.filter((m) => m.status === 'new').length;

    const markAsRead = (id) => {
        router.patch(route('admin.contacts.read', id));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Messages de contact
                    {newCount > 0 && (
                        <span className="ml-2 rounded-full bg-red-100 px-2 py-0.5 text-sm font-medium text-red-700">
                            {newCount} nouveau{newCount > 1 ? 'x' : ''}
                        </span>
                    )}
                </h2>
            }
        >
            <Head title="Messages contact" />

            <div className="py-12">
                <div className="mx-auto max-w-5xl space-y-4 sm:px-6 lg:px-8">
                    {flash === 'contact-read' && (
                        <div className="rounded-lg bg-green-50 px-4 py-3 text-sm text-green-800">
                            Message marqué comme lu.
                        </div>
                    )}

                    {messages.length === 0 ? (
                        <p className="rounded-lg bg-white p-6 text-gray-600 shadow-sm">
                            Aucun message pour le moment.
                        </p>
                    ) : (
                        messages.map((message) => (
                            <div
                                key={message.id}
                                className={`rounded-lg bg-white p-6 shadow-sm ${
                                    message.status === 'new'
                                        ? 'ring-2 ring-[#043873]/20'
                                        : ''
                                }`}
                            >
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <h3 className="font-semibold text-gray-900">
                                            {message.subject}
                                        </h3>
                                        <p className="mt-1 text-sm text-gray-600">
                                            {message.name} · {message.email}
                                            {message.phone && ` · ${message.phone}`}
                                        </p>
                                        <p className="mt-1 text-xs text-gray-400">
                                            {message.created_at}
                                        </p>
                                    </div>
                                    <span
                                        className={`rounded-full px-3 py-1 text-xs font-medium ${
                                            message.status === 'new'
                                                ? 'bg-blue-100 text-blue-800'
                                                : 'bg-gray-100 text-gray-600'
                                        }`}
                                    >
                                        {message.status_label}
                                    </span>
                                </div>
                                <p className="mt-4 whitespace-pre-wrap text-sm text-gray-700">
                                    {message.message}
                                </p>
                                {message.status === 'new' && (
                                    <button
                                        type="button"
                                        onClick={() => markAsRead(message.id)}
                                        className="mt-4 text-sm font-medium text-[#043873] hover:underline"
                                    >
                                        Marquer comme lu
                                    </button>
                                )}
                                {message.read_by && (
                                    <p className="mt-2 text-xs text-gray-400">
                                        Lu par {message.read_by} le {message.read_at}
                                    </p>
                                )}
                            </div>
                        ))
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
