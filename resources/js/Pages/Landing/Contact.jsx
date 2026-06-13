import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import LandingLayout from '@/Layouts/LandingLayout';
import { cm, landingImageAlts, landingImages } from '@/constants/cameroonTheme';
import { Head, useForm, usePage } from '@inertiajs/react';

export default function Contact() {
    const flash = usePage().props.flash?.status;
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        phone: '',
        subject: '',
        message: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('contact.store'), {
            onSuccess: () => reset(),
        });
    };

    return (
        <LandingLayout>
            <Head title="Contact" />

            <section className="py-16" style={{ backgroundColor: cm.cream }}>
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="grid items-start gap-12 lg:grid-cols-2">
                        <div>
                            <span
                                className="inline-block rounded-full px-3 py-1 text-xs font-semibold"
                                style={{
                                    backgroundColor: `${cm.green}15`,
                                    color: cm.green,
                                }}
                            >
                                🇨🇲 Support local
                            </span>
                            <h1
                                className="mt-4 text-4xl font-bold"
                                style={{ color: cm.greenDark }}
                            >
                                Contactez-nous
                            </h1>
                            <p
                                className="mt-4 text-lg"
                                style={{ color: cm.textMuted }}
                            >
                                Une question sur la plateforme, l&apos;inscription ou
                                une mission en cours ? Remplissez le formulaire —
                                un administrateur ou un assistant camerounais vous
                                répondra rapidement.
                            </p>
                            <img
                                src={landingImages.contact}
                                alt={landingImageAlts.contact}
                                className="mt-8 hidden rounded-2xl object-cover shadow-lg lg:block"
                            />
                        </div>

                        <div className="rounded-2xl bg-white p-8 shadow-xl ring-1 ring-gray-100">
                            {flash === 'contact-sent' && (
                                <div
                                    className="mb-6 rounded-lg px-4 py-3 text-sm"
                                    style={{
                                        backgroundColor: cm.greenLight,
                                        color: cm.greenDark,
                                    }}
                                >
                                    Message envoyé ! Notre équipe vous contactera bientôt.
                                </div>
                            )}

                            <form onSubmit={submit} className="space-y-5">
                                <div>
                                    <InputLabel htmlFor="name" value="Nom complet" />
                                    <TextInput
                                        id="name"
                                        value={data.name}
                                        className="mt-1 block w-full"
                                        onChange={(e) =>
                                            setData('name', e.target.value)
                                        }
                                        required
                                    />
                                    <InputError
                                        message={errors.name}
                                        className="mt-1"
                                    />
                                </div>

                                <div className="grid gap-5 sm:grid-cols-2">
                                    <div>
                                        <InputLabel htmlFor="email" value="Email" />
                                        <TextInput
                                            id="email"
                                            type="email"
                                            value={data.email}
                                            className="mt-1 block w-full"
                                            onChange={(e) =>
                                                setData('email', e.target.value)
                                            }
                                            required
                                        />
                                        <InputError
                                            message={errors.email}
                                            className="mt-1"
                                        />
                                    </div>
                                    <div>
                                        <InputLabel
                                            htmlFor="phone"
                                            value="Téléphone (optionnel)"
                                        />
                                        <TextInput
                                            id="phone"
                                            value={data.phone}
                                            placeholder="6XX XXX XXX"
                                            className="mt-1 block w-full"
                                            onChange={(e) =>
                                                setData('phone', e.target.value)
                                            }
                                        />
                                        <InputError
                                            message={errors.phone}
                                            className="mt-1"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <InputLabel htmlFor="subject" value="Sujet" />
                                    <TextInput
                                        id="subject"
                                        value={data.subject}
                                        className="mt-1 block w-full"
                                        onChange={(e) =>
                                            setData('subject', e.target.value)
                                        }
                                        required
                                    />
                                    <InputError
                                        message={errors.subject}
                                        className="mt-1"
                                    />
                                </div>

                                <div>
                                    <InputLabel htmlFor="message" value="Message" />
                                    <textarea
                                        id="message"
                                        rows={5}
                                        value={data.message}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                        style={
                                            {
                                                '--tw-ring-color': cm.green,
                                            }
                                        }
                                        onChange={(e) =>
                                            setData('message', e.target.value)
                                        }
                                        required
                                    />
                                    <InputError
                                        message={errors.message}
                                        className="mt-1"
                                    />
                                </div>

                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full rounded-lg px-6 py-3 text-sm font-bold text-white disabled:opacity-50"
                                    style={{ backgroundColor: cm.green }}
                                >
                                    Envoyer le message
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        </LandingLayout>
    );
}
