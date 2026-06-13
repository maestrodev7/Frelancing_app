import WhatsAppFloat from '@/Components/Landing/WhatsAppFloat';
import { cm } from '@/constants/cameroonTheme';
import { Link, usePage } from '@inertiajs/react';

function CameroonStripe() {
    return (
        <div className="flex h-1 w-full">
            <span className="flex-1" style={{ backgroundColor: cm.green }} />
            <span className="flex-1" style={{ backgroundColor: cm.red }} />
            <span className="flex-1" style={{ backgroundColor: cm.yellow }} />
        </div>
    );
}

export default function LandingLayout({ children }) {
    const { landing, auth } = usePage().props;
    const appName = landing?.app_name ?? 'SkillAfrika';

    return (
        <div
            className="min-h-screen font-sans"
            style={{ backgroundColor: '#fff', color: cm.text }}
        >
            <CameroonStripe />
            <header
                className="sticky top-0 z-40 border-b bg-white/95 backdrop-blur"
                style={{ borderColor: `${cm.green}20` }}
            >
                <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                    <Link href="/" className="flex items-center gap-2">
                        <span
                            className="flex h-9 w-9 items-center justify-center rounded-lg text-sm font-bold"
                            style={{ backgroundColor: cm.green, color: cm.yellow }}
                        >
                            CM
                        </span>
                        <span
                            className="text-lg font-bold"
                            style={{ color: cm.greenDark }}
                        >
                            {appName}
                        </span>
                    </Link>

                    <nav className="hidden items-center gap-8 md:flex">
                        <a
                            href="/#fonctionnalites"
                            className="text-sm font-medium hover:opacity-80"
                            style={{ color: cm.textMuted }}
                        >
                            Fonctionnalités
                        </a>
                        <a
                            href="/#comment-ca-marche"
                            className="text-sm font-medium hover:opacity-80"
                            style={{ color: cm.textMuted }}
                        >
                            Comment ça marche
                        </a>
                        <Link
                            href={route('contact')}
                            className="text-sm font-medium hover:opacity-80"
                            style={{ color: cm.textMuted }}
                        >
                            Contact
                        </Link>
                    </nav>

                    <div className="flex items-center gap-3">
                        {auth?.user ? (
                            <Link
                                href={route('dashboard')}
                                className="rounded-lg px-4 py-2 text-sm font-semibold text-white"
                                style={{ backgroundColor: cm.green }}
                            >
                                Mon espace
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={route('login')}
                                    className="hidden text-sm font-medium hover:underline sm:inline"
                                    style={{ color: cm.greenDark }}
                                >
                                    Connexion
                                </Link>
                                <Link
                                    href={route('register')}
                                    className="rounded-lg px-4 py-2 text-sm font-semibold"
                                    style={{
                                        backgroundColor: cm.yellow,
                                        color: cm.greenDark,
                                    }}
                                >
                                    S&apos;inscrire
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </header>

            <main>{children}</main>

            <footer style={{ backgroundColor: cm.greenDark }} className="text-white">
                <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                    <div className="grid gap-8 md:grid-cols-3">
                        <div>
                            <p className="text-lg font-bold">{appName}</p>
                            <p className="mt-3 text-sm opacity-80">
                                La plateforme qui connecte porteurs de projet et
                                freelances camerounais — Douala, Yaoundé, Bafoussam
                                et partout au pays.
                            </p>
                        </div>
                        <div>
                            <p className="font-semibold">Liens utiles</p>
                            <ul className="mt-3 space-y-2 text-sm opacity-80">
                                <li>
                                    <Link href={route('register')} className="hover:text-white">
                                        Créer un compte
                                    </Link>
                                </li>
                                <li>
                                    <Link href={route('login')} className="hover:text-white">
                                        Se connecter
                                    </Link>
                                </li>
                                <li>
                                    <Link href={route('contact')} className="hover:text-white">
                                        Nous contacter
                                    </Link>
                                </li>
                            </ul>
                        </div>
                        <div>
                            <p className="font-semibold">Assistant</p>
                            <p className="mt-3 text-sm opacity-80">
                                Une question ? Contactez notre équipe via WhatsApp
                                ou le formulaire de contact.
                            </p>
                        </div>
                    </div>
                    <p className="mt-10 border-t border-white/20 pt-6 text-center text-xs opacity-60">
                        © {new Date().getFullYear()} {appName}. Fièrement camerounais.
                    </p>
                </div>
            </footer>
            <CameroonStripe />

            <WhatsAppFloat />
        </div>
    );
}
