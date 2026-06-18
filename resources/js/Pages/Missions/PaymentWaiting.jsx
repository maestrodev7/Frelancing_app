import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';

const POLL_INTERVAL_MS = 15000;

export default function PaymentWaiting({ payment, mission }) {
    const [current, setCurrent] = useState(payment);
    const [lastCheckedAt, setLastCheckedAt] = useState(null);
    const [pollError, setPollError] = useState(null);
    const pollingRef = useRef(false);

    const checkStatus = useCallback(async () => {
        if (pollingRef.current) {
            return;
        }

        pollingRef.current = true;
        setPollError(null);

        try {
            const response = await fetch(
                route('missions.payments.status', payment.id),
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                },
            );

            if (!response.ok) {
                throw new Error(`Vérification impossible (${response.status})`);
            }

            const data = await response.json();
            setCurrent(data);
            setLastCheckedAt(new Date());

            if (data.is_complete) {
                router.visit(route('missions.show', mission.id), {
                    preserveState: false,
                });
            }
        } catch (error) {
            setPollError(
                error instanceof Error
                    ? error.message
                    : 'Impossible de vérifier le statut pour le moment.',
            );
        } finally {
            pollingRef.current = false;
        }
    }, [mission.id, payment.id]);

    useEffect(() => {
        if (current.is_complete || current.is_failed) {
            return undefined;
        }

        checkStatus();

        const interval = setInterval(checkStatus, POLL_INTERVAL_MS);

        return () => clearInterval(interval);
    }, [checkStatus, current.is_complete, current.is_failed]);

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Confirmation du paiement
                </h2>
            }
        >
            <Head title="Paiement en cours" />

            <div className="py-12">
                <div className="mx-auto max-w-lg sm:px-6 lg:px-8">
                    <div className="rounded-lg bg-white p-8 text-center shadow-sm">
                        {!current.is_failed && !current.is_complete && (
                            <>
                                <div className="mx-auto mb-4 h-12 w-12 animate-spin rounded-full border-4 border-[#007A5E] border-t-transparent" />
                                <h3 className="text-lg font-semibold text-gray-900">
                                    Paiement en cours…
                                </h3>
                                <p className="mt-2 text-sm text-gray-600">
                                    {current.payment_method === 'Carte bancaire'
                                        ? 'Finalisez le paiement carte si une fenêtre s\'est ouverte.'
                                        : 'Confirmez la transaction sur votre téléphone (Orange Money ou MTN).'}
                                </p>
                                <p className="mt-4 text-xs text-gray-400">
                                    Réf. {current.kratos_reference}
                                </p>
                                <p className="mt-2 text-xs text-gray-500">
                                    Vérification automatique toutes les 15 secondes auprès de
                                    Kratos Pay.
                                </p>
                                {current.kratos_status && (
                                    <p className="mt-1 text-xs text-gray-500">
                                        Statut Kratos : {current.kratos_status}
                                    </p>
                                )}
                                {lastCheckedAt && (
                                    <p className="mt-1 text-xs text-gray-400">
                                        Dernière vérification :{' '}
                                        {lastCheckedAt.toLocaleTimeString('fr-FR')}
                                    </p>
                                )}
                                {pollError && (
                                    <p className="mt-3 text-xs text-amber-700">{pollError}</p>
                                )}
                                <button
                                    type="button"
                                    onClick={checkStatus}
                                    className="mt-4 text-sm font-medium text-[#007A5E] underline"
                                >
                                    Vérifier maintenant
                                </button>
                            </>
                        )}

                        {current.is_complete && (
                            <>
                                <div className="mb-4 text-4xl">✓</div>
                                <h3 className="text-lg font-semibold text-green-700">
                                    Paiement confirmé
                                </h3>
                                <p className="mt-2 text-sm text-gray-600">
                                    La mission avec {mission.title} démarre. Les fonds sont
                                    sécurisés.
                                </p>
                                <Link
                                    href={route('missions.show', mission.id)}
                                    className="mt-6 inline-block"
                                >
                                    <PrimaryButton>Voir la mission</PrimaryButton>
                                </Link>
                            </>
                        )}

                        {current.is_failed && (
                            <>
                                <h3 className="text-lg font-semibold text-red-700">
                                    Paiement échoué
                                </h3>
                                <p className="mt-2 text-sm text-gray-600">
                                    {current.failure_reason ??
                                        'La transaction n\'a pas abouti. Réessayez.'}
                                </p>
                                <Link
                                    href={route('missions.show', mission.id)}
                                    className="mt-6 inline-block text-sm underline"
                                >
                                    Retour à la mission
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
