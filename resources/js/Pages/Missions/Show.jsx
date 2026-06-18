import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
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
        'payment-initiated': 'Paiement initié. Confirmez sur votre téléphone.',
        'proposal-rejected': 'Proposition refusée.',
        'dispute-opened':
            'Litige ouvert. Un administrateur ou secrétaire va traiter votre demande.',
        'mission-closed':
            'Mission clôturée. Vous pouvez maintenant laisser un avis à votre partenaire.',
        'review-submitted': 'Merci, votre avis a été enregistré.',
        'mission-updated': 'Mission mise à jour avec succès.',
    };

    const text = messages[status] ?? status;

    return (
        <div className="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {text}
        </div>
    );
}

function ProposalCard({
    proposal,
    isOwner,
    payableProposalId = null,
    missionOpen = true,
    awaitingPayment = false,
}) {
    const isPayable = payableProposalId === proposal.id;
    const canPay =
        isOwner &&
        proposal.status === 'pending' &&
        (missionOpen || (awaitingPayment && isPayable));
    const showReject =
        isOwner && proposal.status === 'pending' && (missionOpen || awaitingPayment);
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

            {canPay && (
                <div className="mt-4 flex gap-3">
                    <Link
                        href={route('proposals.payment.checkout', proposal.id)}
                        className={`rounded-md px-4 py-2 text-sm font-medium text-white ${
                            awaitingPayment
                                ? 'bg-[#FF6600] hover:bg-[#e55a00]'
                                : 'bg-green-600 hover:bg-green-700'
                        }`}
                    >
                        {awaitingPayment && isPayable
                            ? 'Continuer le paiement'
                            : 'Accepter et payer'}
                    </Link>
                    {showReject && (
                        <Link
                            href={route('proposals.reject', proposal.id)}
                            method="patch"
                            as="button"
                            className="rounded-md border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50"
                        >
                            Refuser
                        </Link>
                    )}
                </div>
            )}
        </div>
    );
}

export default function Show({
    mission,
    proposals = [],
    myProposal = null,
    assignedFreelancer = null,
    dispute = null,
    canApply = false,
    canOpenDispute = false,
    canCloseMission = false,
    canReview = false,
    myReview = null,
    receivedReviews = [],
    isOwner = false,
    canEdit = false,
    payableProposalId = null,
    pricingTypes = [],
}) {
    const missionOpen = mission.status === 'open';
    const awaitingPayment = mission.status === 'awaiting_payment';
    const { data, setData, post: postProposal, processing, errors, reset } = useForm({
        cover_letter: '',
        pricing_type: 'fixed',
        amount_fixed: '',
        hourly_rate: '',
        estimated_hours: '',
        delivery_days: '',
    });

    const { post: postClose } = useForm({});
    const disputeForm = useForm({ reason: '' });
    const reviewForm = useForm({ rating: 5, comment: '' });

    const submitProposal = (e) => {
        e.preventDefault();
        postProposal(route('missions.proposals.store', mission.id), {
            onSuccess: () => reset(),
        });
    };

    const submitDispute = (e) => {
        e.preventDefault();
        if (
            !window.confirm(
                'Confirmez-vous l’ouverture d’un litige sur cette mission ?',
            )
        ) {
            return;
        }
        disputeForm.post(route('missions.disputes.store', mission.id), {
            onSuccess: () => disputeForm.reset(),
        });
    };

    const submitReview = (e) => {
        e.preventDefault();
        reviewForm.post(route('missions.reviews.store', mission.id), {
            onSuccess: () => reviewForm.reset(),
        });
    };

    const closeMission = () => {
        if (
            !window.confirm(
                'Confirmez-vous la clôture de cette mission ? Les avis pourront ensuite être déposés.',
            )
        ) {
            return;
        }
        postClose(route('missions.close', mission.id));
    };

    const isHourly = data.pricing_type === 'hourly';

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        {mission.title}
                    </h2>
                    <div className="flex items-center gap-4">
                        {canEdit && (
                            <Link
                                href={route('missions.edit', mission.id)}
                                className="text-sm font-medium text-[#007A5E] underline"
                            >
                                Modifier la mission
                            </Link>
                        )}
                        <Link
                            href={route('missions.index')}
                            className="text-sm text-gray-600 underline"
                        >
                            Retour aux missions
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={mission.title} />

            <div className="py-12">
                <div className="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                    <FlashMessage />

                    {awaitingPayment && isOwner && (
                        <div className="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                            Cette mission est <strong>en attente de paiement</strong>.
                            Finalisez le paiement pour démarrer la collaboration avec le
                            freelance.
                        </div>
                    )}

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
                            {assignedFreelancer && (
                                <span>
                                    Freelance assigné : {assignedFreelancer.name}
                                </span>
                            )}
                        </div>

                        {dispute && (
                            <div
                                className={`mt-6 rounded-lg border p-4 ${
                                    dispute.status === 'open'
                                        ? 'border-red-200 bg-red-50'
                                        : 'border-green-200 bg-green-50'
                                }`}
                            >
                                <h3 className="font-semibold text-gray-900">
                                    Litige — {dispute.status_label}
                                </h3>
                                <p className="mt-2 text-sm text-gray-700">
                                    {dispute.reason}
                                </p>
                                {dispute.status === 'resolved' &&
                                    dispute.resolution_notes && (
                                        <p className="mt-2 text-sm text-gray-600">
                                            <strong>Résolution :</strong>{' '}
                                            {dispute.resolution_notes}
                                        </p>
                                    )}
                            </div>
                        )}

                        {isOwner && canCloseMission && (
                            <div className="mt-6">
                                <button
                                    type="button"
                                    onClick={closeMission}
                                    className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Clôturer la mission
                                </button>
                            </div>
                        )}

                        {isOwner && canOpenDispute && (
                            <form
                                onSubmit={submitDispute}
                                className="mt-6 space-y-3 rounded-lg border border-red-100 bg-red-50/50 p-4"
                            >
                                <h3 className="font-semibold text-red-900">
                                    Signaler un problème (litige)
                                </h3>
                                <p className="text-sm text-gray-600">
                                    En cas de conflit avec le freelance pendant
                                    la mission, ouvrez un litige. Le staff le
                                    traitera et le statut sera visible pour
                                    les deux parties.
                                </p>
                                <textarea
                                    rows={4}
                                    value={disputeForm.data.reason}
                                    className="block w-full rounded-md border-gray-300 shadow-sm"
                                    placeholder="Décrivez le problème…"
                                    onChange={(e) =>
                                        disputeForm.setData(
                                            'reason',
                                            e.target.value,
                                        )
                                    }
                                    required
                                />
                                <InputError
                                    message={disputeForm.errors.reason}
                                />
                                <button
                                    type="submit"
                                    disabled={disputeForm.processing}
                                    className="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                                >
                                    Ouvrir un litige
                                </button>
                            </form>
                        )}
                    </div>

                    {(myReview || receivedReviews.length > 0) && (
                        <div className="space-y-4 rounded-lg bg-white p-6 shadow-sm">
                            <h3 className="text-lg font-semibold text-gray-900">
                                Avis sur la mission
                            </h3>
                            {myReview && (
                                <div className="rounded border border-indigo-100 bg-indigo-50 p-4">
                                    <p className="text-sm font-medium text-gray-700">
                                        Votre avis
                                    </p>
                                    <p className="mt-1">
                                        {'★'.repeat(myReview.rating)}
                                        {'☆'.repeat(5 - myReview.rating)}
                                    </p>
                                    {myReview.comment && (
                                        <p className="mt-2 text-sm text-gray-600">
                                            {myReview.comment}
                                        </p>
                                    )}
                                </div>
                            )}
                            {receivedReviews.map((review, index) => (
                                <div
                                    key={index}
                                    className="rounded border border-gray-200 p-4"
                                >
                                    <p className="text-sm font-medium text-gray-700">
                                        Avis de {review.reviewer_name}
                                    </p>
                                    <p className="mt-1">
                                        {'★'.repeat(review.rating)}
                                        {'☆'.repeat(5 - review.rating)}
                                    </p>
                                    {review.comment && (
                                        <p className="mt-2 text-sm text-gray-600">
                                            {review.comment}
                                        </p>
                                    )}
                                </div>
                            ))}
                        </div>
                    )}

                    {canReview && (
                        <form
                            onSubmit={submitReview}
                            className="space-y-4 rounded-lg bg-white p-6 shadow-sm"
                        >
                            <h3 className="text-lg font-semibold text-gray-900">
                                Noter votre partenaire
                            </h3>
                            <div>
                                <InputLabel htmlFor="rating" value="Note (1 à 5)" />
                                <select
                                    id="rating"
                                    value={reviewForm.data.rating}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    onChange={(e) =>
                                        reviewForm.setData(
                                            'rating',
                                            Number(e.target.value),
                                        )
                                    }
                                >
                                    {[5, 4, 3, 2, 1].map((n) => (
                                        <option key={n} value={n}>
                                            {n} étoile{n > 1 ? 's' : ''}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={reviewForm.errors.rating} />
                            </div>
                            <div>
                                <InputLabel
                                    htmlFor="comment"
                                    value="Commentaire (optionnel)"
                                />
                                <textarea
                                    id="comment"
                                    rows={3}
                                    value={reviewForm.data.comment}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    onChange={(e) =>
                                        reviewForm.setData(
                                            'comment',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError message={reviewForm.errors.comment} />
                            </div>
                            <PrimaryButton disabled={reviewForm.processing}>
                                Publier mon avis
                            </PrimaryButton>
                        </form>
                    )}

                    {isOwner && (
                        <div id="propositions" className="space-y-4">
                            <div className="rounded-lg border border-indigo-100 bg-indigo-50 px-4 py-3">
                                <h3 className="text-lg font-semibold text-gray-900">
                                    Freelances ayant postulé ({proposals.length})
                                </h3>
                                <p className="mt-1 text-sm text-gray-600">
                                    Consultez chaque candidature, puis acceptez ou
                                    refusez la proposition qui vous convient.
                                </p>
                            </div>
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
                                        payableProposalId={payableProposalId}
                                        missionOpen={missionOpen}
                                        awaitingPayment={awaitingPayment}
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
