import LandingLayout from '@/Layouts/LandingLayout';
import { cm, landingImageAlts, landingImages } from '@/constants/cameroonTheme';
import { Head, Link } from '@inertiajs/react';

const features = [
    {
        title: 'Publiez vos missions',
        description:
            'Porteurs de projet : décrivez votre besoin, fixez votre budget en XAF ou devise, et recevez des propositions de freelances camerounais.',
        image: landingImages.featurePublish,
        alt: landingImageAlts.featurePublish,
    },
    {
        title: 'Trouvez des freelances locaux',
        description:
            'Développeurs, designers, rédacteurs, community managers — des talents noirs et africains prêts à collaborer à Douala, Yaoundé ou à distance.',
        image: landingImages.featureFreelancers,
        alt: landingImageAlts.featureFreelancers,
    },
    {
        title: 'Sécurisez vos collaborations',
        description:
            'Litiges gérés par notre équipe locale, avis mutuels après mission, et accompagnement WhatsApp en français.',
        image: landingImages.featureTrust,
        alt: landingImageAlts.featureTrust,
    },
];

const steps = [
    {
        number: '01',
        title: 'Créez votre compte',
        text: 'Inscrivez-vous en tant que porteur de projet ou freelance en quelques minutes.',
    },
    {
        number: '02',
        title: 'Publiez ou postulez',
        text: 'Les clients publient des missions ; les freelances envoient leurs propositions détaillées.',
    },
    {
        number: '03',
        title: 'Collaborez en confiance',
        text: 'Acceptez une proposition, suivez la mission, et notez votre partenaire à la clôture.',
    },
];

const stats = [
    { value: 'XAF', label: 'Budgets en franc CFA' },
    { value: '🇨🇲', label: '100 % Cameroun' },
    { value: '24/7', label: 'Support WhatsApp' },
];

export default function Welcome({ canLogin, canRegister }) {
    return (
        <LandingLayout>
            <Head title="Accueil" />

            <section
                className="text-white"
                style={{
                    background: `linear-gradient(135deg, ${cm.green} 0%, ${cm.greenDark} 100%)`,
                }}
            >
                <div className="mx-auto grid max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:px-8 lg:py-24">
                    <div>
                        <span
                            className="inline-block rounded-full px-4 py-1 text-sm font-medium"
                            style={{
                                backgroundColor: `${cm.yellow}30`,
                                color: cm.yellow,
                            }}
                        >
                            🇨🇲 Plateforme freelance · Cameroun
                        </span>
                        <h1 className="mt-6 text-4xl font-bold leading-tight tracking-tight sm:text-5xl lg:text-6xl">
                            Connectez talents et projets,{' '}
                            <span style={{ color: cm.yellow }}>
                                fièrement camerounais
                            </span>
                        </h1>
                        <p className="mt-6 max-w-xl text-lg opacity-90">
                            Publiez vos missions depuis Douala, Yaoundé ou ailleurs.
                            Trouvez des freelances compétents qui vous ressemblent,
                            collaborez sereinement et faites grandir vos projets.
                        </p>
                        <div className="mt-8 flex flex-wrap gap-4">
                            {canRegister && (
                                <Link
                                    href={route('register')}
                                    className="rounded-lg px-6 py-3 text-sm font-bold"
                                    style={{
                                        backgroundColor: cm.yellow,
                                        color: cm.greenDark,
                                    }}
                                >
                                    Commencer gratuitement
                                </Link>
                            )}
                            <Link
                                href={route('contact')}
                                className="rounded-lg border border-white/40 px-6 py-3 text-sm font-semibold text-white hover:bg-white/10"
                            >
                                Parler à l&apos;assistant
                            </Link>
                        </div>
                    </div>
                    <div className="relative">
                        <div
                            className="absolute -inset-4 rounded-3xl blur-2xl"
                            style={{ backgroundColor: `${cm.yellow}25` }}
                        />
                        <img
                            src={landingImages.hero}
                            alt={landingImageAlts.hero}
                            loading="eager"
                            className="relative w-full rounded-2xl object-cover shadow-2xl ring-2 ring-white/20"
                        />
                    </div>
                </div>
            </section>

            <section
                className="border-b py-10"
                style={{
                    backgroundColor: cm.cream,
                    borderColor: `${cm.green}15`,
                }}
            >
                <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-center gap-8 px-4 text-center sm:gap-16">
                    {stats.map((stat) => (
                        <div key={stat.label}>
                            <p
                                className="text-2xl font-bold"
                                style={{ color: cm.greenDark }}
                            >
                                {stat.value}
                            </p>
                            <p className="text-sm" style={{ color: cm.textMuted }}>
                                {stat.label}
                            </p>
                        </div>
                    ))}
                </div>
            </section>

            <section id="fonctionnalites" className="py-20">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="mx-auto max-w-2xl text-center">
                        <h2
                            className="text-3xl font-bold sm:text-4xl"
                            style={{ color: cm.greenDark }}
                        >
                            Tout pour réussir vos missions
                        </h2>
                        <p className="mt-4" style={{ color: cm.textMuted }}>
                            Une plateforme pensée pour les entrepreneurs et freelances
                            camerounais : mobile, accessible, et accompagnée localement.
                        </p>
                    </div>

                    <div className="mt-16 space-y-24">
                        {features.map((feature, index) => (
                            <div
                                key={feature.title}
                                className={`grid items-center gap-10 lg:grid-cols-2 ${
                                    index % 2 === 1
                                        ? 'lg:[&>*:first-child]:order-2'
                                        : ''
                                }`}
                            >
                                <div
                                    className="rounded-2xl p-2"
                                    style={{
                                        backgroundColor:
                                            index % 2 === 0
                                                ? cm.greenLight
                                                : `${cm.yellow}30`,
                                    }}
                                >
                                    <img
                                        src={feature.image}
                                        alt={feature.alt}
                                        className="h-72 w-full rounded-xl object-cover object-center shadow-lg sm:h-80"
                                    />
                                </div>
                                <div>
                                    <h3
                                        className="text-2xl font-bold"
                                        style={{ color: cm.greenDark }}
                                    >
                                        {feature.title}
                                    </h3>
                                    <p
                                        className="mt-4 text-lg leading-relaxed"
                                        style={{ color: cm.textMuted }}
                                    >
                                        {feature.description}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            <section
                id="comment-ca-marche"
                className="py-20 text-white"
                style={{ backgroundColor: cm.greenDark }}
            >
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <h2 className="text-center text-3xl font-bold sm:text-4xl">
                        Comment ça marche ?
                    </h2>
                    <div className="mt-14 grid gap-8 md:grid-cols-3">
                        {steps.map((step) => (
                            <div
                                key={step.number}
                                className="rounded-2xl border border-white/10 bg-white/5 p-8 backdrop-blur"
                            >
                                <span
                                    className="text-4xl font-bold"
                                    style={{ color: cm.yellow }}
                                >
                                    {step.number}
                                </span>
                                <h3 className="mt-4 text-xl font-semibold">
                                    {step.title}
                                </h3>
                                <p className="mt-3 text-sm opacity-80">
                                    {step.text}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            <section className="py-20" style={{ backgroundColor: cm.cream }}>
                <div className="mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
                    <img
                        src={landingImages.showcase}
                        alt={landingImageAlts.showcase}
                        className="rounded-2xl object-cover shadow-xl ring-2 ring-[#007A5E]"
                    />
                    <div>
                        <h2
                            className="text-3xl font-bold"
                            style={{ color: cm.greenDark }}
                        >
                            Votre prochain projet commence ici
                        </h2>
                        <p
                            className="mt-4 text-lg"
                            style={{ color: cm.textMuted }}
                        >
                            Que vous soyez entrepreneur à Bafoussam, startup à Douala
                            ou freelance à Yaoundé, notre plateforme vous met en relation
                            avec les bonnes personnes.
                        </p>
                        <ul className="mt-6 space-y-3" style={{ color: cm.text }}>
                            <li className="flex items-center gap-2">
                                <span style={{ color: cm.green }}>✓</span>
                                Inscription gratuite porteur de projet & freelance
                            </li>
                            <li className="flex items-center gap-2">
                                <span style={{ color: cm.green }}>✓</span>
                                Gestion des litiges par notre équipe camerounaise
                            </li>
                            <li className="flex items-center gap-2">
                                <span style={{ color: cm.green }}>✓</span>
                                Assistant disponible sur WhatsApp
                            </li>
                        </ul>
                        {canRegister && (
                            <Link
                                href={route('register')}
                                className="mt-8 inline-block rounded-lg px-6 py-3 text-sm font-bold text-white"
                                style={{ backgroundColor: cm.green }}
                            >
                                Créer mon compte
                            </Link>
                        )}
                    </div>
                </div>
            </section>

            <section className="py-16" style={{ backgroundColor: cm.yellow }}>
                <div className="mx-auto max-w-3xl px-4 text-center sm:px-6">
                    <h2
                        className="text-3xl font-bold"
                        style={{ color: cm.greenDark }}
                    >
                        Prêt à lancer votre mission ?
                    </h2>
                    <p className="mt-4 opacity-80" style={{ color: cm.greenDark }}>
                        Rejoignez la communauté camerounaise ou contactez notre
                        assistant pour être guidé pas à pas.
                    </p>
                    <div className="mt-8 flex flex-wrap justify-center gap-4">
                        {canLogin && (
                            <Link
                                href={route('login')}
                                className="rounded-lg border-2 px-6 py-3 text-sm font-bold hover:text-white"
                                style={{
                                    borderColor: cm.greenDark,
                                    color: cm.greenDark,
                                }}
                            >
                                Se connecter
                            </Link>
                        )}
                        <Link
                            href={route('contact')}
                            className="rounded-lg px-6 py-3 text-sm font-bold text-white"
                            style={{ backgroundColor: cm.greenDark }}
                        >
                            Formulaire de contact
                        </Link>
                    </div>
                </div>
            </section>
        </LandingLayout>
    );
}
