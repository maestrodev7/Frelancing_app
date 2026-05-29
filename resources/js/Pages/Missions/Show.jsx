import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

function FlashMessage() {
    const status = usePage().props.flash?.status;

    if (!status) {
        return null;
    }

    const messages = {
        'proposal-sent': 'Votre proposition a été envoyée avec succès.',
        'proposal-accepted': 'Proposition acceptée. La mission est en cours.',
        'proposal-rejected': 'Proposition refusée.',
    };

    const text = messages[status] ?? status;

    return (
        <div className="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {text}
        </div>
    );
}

function ProposalCard({ proposal, isOwner }) {
    return (
        <div className="rounded-lg border border-gray-200 p-5">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h4 className="font-semibold text-gray-900">
                        {proposal.freelancer?.name ?? 'Freelance'}
                    </h4>
                    <p className="text-sm text-gray-500">
                        {proposal.freelancer?.email}
                    </p>
                </div>
                <span
                    className={`rounded-full px-3 py-1 text-xs font-medium ${
                        proposal.status === 'accepted'
                            ? 'bg-green-100 text-green-800'
                            : proposal.status === 'rejected'
                              ? 'bg-red-100 text-red-800'
                              : 'bg-yellow-100 text-yellow-800'
                    }`}
                >
                    {proposal.status_label}
                </span>
            </div>

            <p className="mt-4 whitespace-pre-wrap text-sm text-gray-700">
                {proposal.cover_letter}
            </p>

            <div className="mt-4 grid gap-2 text-sm text-gray-600 sm:grid-cols-2">
                <span>Type : {proposal.pricing_type_label}</span>
                {proposal.amount_fixed != null && (
                    <span>Montant forfait : {proposal.amount_fixed}</span>
                )}
                {proposal.hourly_rate != null && (
                    <span>
                        Taux horaire : {proposal.hourly_rate}
                        {proposal.estimated_hours != null &&
                            ` (${proposal.estimated_hours} h estimées)`}
                    </span>
                )}
                <span>Délai : {proposal.delivery_days} jour(s)</span>
                <span>Soumis le : {proposal.submitted_at}</span>
            </div>

            {isOwner && proposal.status === 'pending' && (
                <div className="mt-4 flex gap-3">
                    <Link
                        href={route('proposals.accept', proposal.id)}
                        method="patch"
                        as="button"
                        className="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                    >
                        Accepter
                    </Link>
                    <Link
                        href={route('proposals.reject', proposal.id)}
                        method="patch"
                        as="button"
                        className="rounded-md border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50"
                    >
                        Refuser
                    </Link>
                </div>
            )}
        </div>
    );
}

export default function Show({
    mission,
    proposals = [],
    myProposal = null,
    canApply = false,
    isOwner = false,
    pricingTypes = [],
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        cover_letter: '',
        pricing_type: 'fixed',
        amount_fixed: '',
        hourly_rate: '',
        estimated_hours: '',
        delivery_days: '',
    });

    const submitProposal = (e) => {
        e.preventDefault();
        post(route('missions.proposals.store', mission.id), {
            onSuccess: () => reset(),
        });
    };

    const isHourly = data.pricing_type === 'hourly';

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        {mission.title}
                    </h2>
                    <Link
                        href={route('missions.index')}
                        className="text-sm text-gray-600 underline"
                    >
                        Retour aux missions
                    </Link>
                </div>
            }
        >
            <Head title={mission.title} />

            <div className="py-12">
                <div className="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                    <FlashMessage />

                    <div className="rounded-lg bg-white p-8 shadow-sm">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <span className="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">
                                {mission.status_label}
                            </span>
                            <span className="text-sm text-gray-500">
                                {mission.type_label}
                            </span>
                        </div>

                        <p className="mt-6 whitespace-pre-wrap text-gray-700">
                            {mission.description}
                        </p>

                        <div className="mt-6 grid gap-3 text-sm text-gray-600 sm:grid-cols-2">
                            {mission.client && (
                                <span>Porteur de projet : {mission.client.name}</span>
                            )}
                            {mission.budget_min != null && (
                                <span>
                                    Budget : {mission.budget_min} – {mission.budget_max}{' '}
                                    {mission.currency}
                                </span>
                            )}
                            {mission.hourly_cap != null && (
                                <span>Plafond horaire : {mission.hourly_cap}</span>
                            )}
                            {mission.start_expected_at && (
                                <span>Début : {mission.start_expected_at}</span>
                            )}
                            {mission.deadline_at && (
                                <span>Échéance : {mission.deadline_at}</span>
                            )}
                        </div>
                    </div>

                    {isOwner && (
                        <div className="space-y-4">
                            <h3 className="text-lg font-semibold text-gray-900">
                                Propositions reçues ({proposals.length})
                            </h3>
                            {proposals.length === 0 ? (
                                <p className="rounded-lg bg-white p-6 text-sm text-gray-600 shadow-sm">
                                    Aucune proposition pour le moment.
                                </p>
                            ) : (
                                proposals.map((proposal) => (
                                    <ProposalCard
                                        key={proposal.id}
                                        proposal={proposal}
                                        isOwner={isOwner}
                                    />
                                ))
                            )}
                        </div>
                    )}

                    {myProposal && (
                        <div>
                            <h3 className="mb-4 text-lg font-semibold text-gray-900">
                                Votre proposition
                            </h3>
                            <ProposalCard proposal={myProposal} isOwner={false} />
                        </div>
                    )}

                    {canApply && (
                        <form
                            onSubmit={submitProposal}
                            className="space-y-6 rounded-lg bg-white p-8 shadow-sm"
                        >
                            <h3 className="text-lg font-semibold text-gray-900">
                                Postuler à cette mission
                            </h3>

                            <div>
                                <InputLabel
                                    htmlFor="cover_letter"
                                    value="Lettre de motivation"
                                />
                                <textarea
                                    id="cover_letter"
                                    rows={5}
                                    value={data.cover_letter}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    onChange={(e) =>
                                        setData('cover_letter', e.target.value)
                                    }
                                    required
                                />
                                <InputError
                                    message={errors.cover_letter}
                                    className="mt-2"
                                />
                            </div>

                            <div className="grid gap-6 sm:grid-cols-2">
                                <div>
                                    <InputLabel
                                        htmlFor="pricing_type"
                                        value="Type de tarification"
                                    />
                                    <select
                                        id="pricing_type"
                                        value={data.pricing_type}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        onChange={(e) =>
                                            setData('pricing_type', e.target.value)
                                        }
                                    >
                                        {pricingTypes.map((type) => (
                                            <option
                                                key={type.value}
                                                value={type.value}
                                            >
                                                {type.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError
                                        message={errors.pricing_type}
                                        className="mt-2"
                                    />
                                </div>

                                <div>
                                    <InputLabel
                                        htmlFor="delivery_days"
                                        value="Délai de livraison (jours)"
                                    />
                                    <TextInput
                                        id="delivery_days"
                                        type="number"
                                        min="1"
                                        value={data.delivery_days}
                                        className="mt-1 block w-full"
                                        onChange={(e) =>
                                            setData('delivery_days', e.target.value)
                                        }
                                        required
                                    />
                                    <InputError
                                        message={errors.delivery_days}
                                        className="mt-2"
                                    />
                                </div>
                            </div>

                            {isHourly ? (
                                <div className="grid gap-6 sm:grid-cols-2">
                                    <div>
                                        <InputLabel
                                            htmlFor="hourly_rate"
                                            value="Taux horaire"
                                        />
                                        <TextInput
                                            id="hourly_rate"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            value={data.hourly_rate}
                                            className="mt-1 block w-full"
                                            onChange={(e) =>
                                                setData('hourly_rate', e.target.value)
                                            }
                                            required
                                        />
                                        <InputError
                                            message={errors.hourly_rate}
                                            className="mt-2"
                                        />
                                    </div>
                                    <div>
                                        <InputLabel
                                            htmlFor="estimated_hours"
                                            value="Heures estimées"
                                        />
                                        <TextInput
                                            id="estimated_hours"
                                            type="number"
                                            min="1"
                                            value={data.estimated_hours}
                                            className="mt-1 block w-full"
                                            onChange={(e) =>
                                                setData(
                                                    'estimated_hours',
                                                    e.target.value,
                                                )
                                            }
                                            required
                                        />
                                        <InputError
                                            message={errors.estimated_hours}
                                            className="mt-2"
                                        />
                                    </div>
                                </div>
                            ) : (
                                <div>
                                    <InputLabel
                                        htmlFor="amount_fixed"
                                        value="Montant forfaitaire"
                                    />
                                    <TextInput
                                        id="amount_fixed"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={data.amount_fixed}
                                        className="mt-1 block w-full"
                                        onChange={(e) =>
                                            setData('amount_fixed', e.target.value)
                                        }
                                        required
                                    />
                                    <InputError
                                        message={errors.amount_fixed}
                                        className="mt-2"
                                    />
                                </div>
                            )}

                            <PrimaryButton disabled={processing}>
                                Envoyer ma proposition
                            </PrimaryButton>
                        </form>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
