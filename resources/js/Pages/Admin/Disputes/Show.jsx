import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function DisputeShow({ dispute }) {
    const flash = usePage().props.flash?.status;
    const isOpen = dispute.status === 'open';

    const { data, setData, patch, processing, errors } = useForm({
        resolution_notes: '',
        resolution_outcome: 'resume_mission',
    });

    const submit = (e) => {
        e.preventDefault();

        if (
            !window.confirm(
                'Confirmez-vous la résolution de ce litige avec la décision choisie ?',
            )
        ) {
            return;
        }

        patch(route('admin.disputes.resolve', dispute.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Litige — {dispute.mission.title}
                </h2>
            }
        >
            <Head title="Détail litige" />

            <div className="py-12">
                <div className="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                    {flash === 'dispute-resolved' && (
                        <div className="rounded-lg bg-green-50 px-4 py-3 text-sm text-green-800">
                            Litige résolu. Le statut de la mission a été mis à
                            jour pour le client et le freelance.
                        </div>
                    )}

                    <div className="rounded-lg bg-white p-6 shadow-sm">
                        <p className="text-sm text-gray-500">Statut litige</p>
                        <p className="mt-1 font-semibold">{dispute.status_label}</p>
                        <p className="mt-4 text-sm text-gray-500">
                            Statut mission (visible par les parties)
                        </p>
                        <p className="font-semibold text-indigo-700">
                            {dispute.mission.status_label}
                        </p>
                        <p className="mt-4 whitespace-pre-wrap text-gray-700">
                            {dispute.reason}
                        </p>
                        <div className="mt-4 grid gap-2 text-sm text-gray-600">
                            <span>
                                Client : {dispute.mission.client.name} (
                                {dispute.mission.client.email})
                            </span>
                            {dispute.mission.freelancer && (
                                <span>
                                    Freelance : {dispute.mission.freelancer.name}{' '}
                                    ({dispute.mission.freelancer.email})
                                </span>
                            )}
                        </div>
                    </div>

                    {!isOpen && dispute.resolution_notes && (
                        <div className="rounded-lg border border-green-200 bg-green-50 p-6">
                            <h3 className="font-semibold text-green-900">
                                Résolution
                            </h3>
                            <p className="mt-2 text-sm text-green-800">
                                {dispute.resolution_notes}
                            </p>
                            <p className="mt-2 text-xs text-green-700">
                                Par {dispute.resolved_by?.name ?? 'Staff'} le{' '}
                                {dispute.resolved_at}
                            </p>
                        </div>
                    )}

                    {isOpen && (
                        <form
                            onSubmit={submit}
                            className="space-y-4 rounded-lg bg-white p-6 shadow-sm"
                        >
                            <h3 className="text-lg font-semibold">
                                Régler le litige
                            </h3>

                            <div>
                                <InputLabel
                                    htmlFor="resolution_outcome"
                                    value="Décision"
                                />
                                <select
                                    id="resolution_outcome"
                                    value={data.resolution_outcome}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    onChange={(e) =>
                                        setData(
                                            'resolution_outcome',
                                            e.target.value,
                                        )
                                    }
                                >
                                    <option value="resume_mission">
                                        Reprendre la mission (en cours)
                                    </option>
                                    <option value="close_mission">
                                        Clôturer la mission
                                    </option>
                                </select>
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="resolution_notes"
                                    value="Notes de résolution"
                                />
                                <textarea
                                    id="resolution_notes"
                                    rows={5}
                                    value={data.resolution_notes}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    onChange={(e) =>
                                        setData(
                                            'resolution_notes',
                                            e.target.value,
                                        )
                                    }
                                    required
                                />
                                <InputError
                                    message={errors.resolution_notes}
                                    className="mt-2"
                                />
                            </div>

                            <PrimaryButton disabled={processing}>
                                Valider la résolution
                            </PrimaryButton>
                        </form>
                    )}

                    <Link
                        href={route('admin.disputes.index')}
                        className="text-sm text-gray-600 underline"
                    >
                        Retour aux litiges
                    </Link>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
