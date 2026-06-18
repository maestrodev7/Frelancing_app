import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PaymentMethodPicker from '@/Components/Payments/PaymentMethodPicker';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Payment({
    proposal,
    paymentMethods = [],
    clientPhone = '',
    amountLimits = null,
}) {
    const { data, setData, post, processing, errors } = useForm({
        payment_method: 'orange_money',
        payer_phone: clientPhone ?? '',
    });

    const isCard = data.payment_method === 'card';

    const submit = (e) => {
        e.preventDefault();
        post(route('proposals.payment.store', proposal.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Paiement sécurisé
                </h2>
            }
        >
            <Head title="Paiement mission" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="mb-6 rounded-lg border border-[#007A5E]/20 bg-[#E8F5E9] p-4 text-sm text-[#004D40]">
                        Vous allez payer <strong>{proposal.freelancer_name}</strong> pour la
                        mission « {proposal.mission.title} ». Les fonds sont bloqués en
                        sécurité jusqu&apos;à la clôture de la mission.
                    </div>

                    {amountLimits && (
                        <p className="mb-4 text-xs text-gray-500">
                            Montants acceptés par Kratos Pay (Mobile Money) :{' '}
                            {Number(amountLimits.min).toLocaleString('fr-FR')} –{' '}
                            {Number(amountLimits.max).toLocaleString('fr-FR')} XAF
                            {proposal.currency !== 'XAF' && proposal.amount_xaf != null && (
                                <>
                                    {' '}
                                    — votre proposition : {proposal.amount}{' '}
                                    {proposal.currency} (≈{' '}
                                    {Number(proposal.amount_xaf).toLocaleString('fr-FR')}{' '}
                                    XAF)
                                </>
                            )}
                        </p>
                    )}

                    {amountLimits && !amountLimits.valid && (
                        <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                            {amountLimits.message}
                        </div>
                    )}

                    {(errors.amount || errors.payment) && (
                        <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                            {errors.amount || errors.payment}
                        </div>
                    )}

                    <div className="rounded-lg bg-white p-8 shadow-sm">
                        <div className="mb-6 space-y-2 border-b pb-6">
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-600">Montant mission</span>
                                <span className="font-medium">
                                    {proposal.amount} {proposal.currency}
                                </span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-600">Frais plateforme</span>
                                <span>{proposal.platform_fee} {proposal.currency}</span>
                            </div>
                            <div className="flex justify-between text-lg font-bold text-[#007A5E]">
                                <span>Total à payer</span>
                                <span>
                                    {proposal.total} {proposal.currency}
                                </span>
                            </div>
                        </div>

                        <form onSubmit={submit} className="space-y-5">
                            <PaymentMethodPicker
                                methods={paymentMethods}
                                value={data.payment_method}
                                onChange={(value) => setData('payment_method', value)}
                                error={errors.payment_method}
                            />

                            {!isCard && (
                                <div>
                                    <InputLabel
                                        htmlFor="payer_phone"
                                        value="Numéro Mobile Money"
                                    />
                                    <TextInput
                                        id="payer_phone"
                                        value={data.payer_phone}
                                        placeholder="6XX XXX XXX"
                                        className="mt-1 block w-full"
                                        onChange={(e) =>
                                            setData('payer_phone', e.target.value)
                                        }
                                        required
                                    />
                                    <p className="mt-1 text-xs text-gray-500">
                                        {data.payment_method === 'orange_money'
                                            ? 'Orange Money : confirmez sur votre téléphone.'
                                            : 'MTN MoMo : confirmez sur votre téléphone.'}
                                    </p>
                                    <InputError
                                        message={errors.payer_phone}
                                        className="mt-1"
                                    />
                                </div>
                            )}

                            {isCard && (
                                <p className="rounded-lg bg-blue-50 p-3 text-sm text-blue-900">
                                    Une session de paiement carte sera ouverte via Kratos Pay
                                    après validation.
                                </p>
                            )}

                            <PrimaryButton
                                disabled={
                                    processing ||
                                    (amountLimits && !amountLimits.valid)
                                }
                                className="w-full justify-center"
                            >
                                Payer et engager le freelance
                            </PrimaryButton>
                        </form>

                        <Link
                            href={route('missions.show', proposal.mission.id)}
                            className="mt-4 block text-center text-sm text-gray-500 underline"
                        >
                            Retour à la mission
                        </Link>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
