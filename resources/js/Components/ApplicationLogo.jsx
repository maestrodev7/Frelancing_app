import { cm } from '@/constants/cameroonTheme';
import { usePage } from '@inertiajs/react';

/** Logo marque — pas de branding framework */
export default function ApplicationLogo({
    className = '',
    showName = false,
    ...props
}) {
    const appName =
        usePage().props.landing?.app_name ??
        import.meta.env.VITE_APP_NAME ??
        'SkillAfrika';

    return (
        <span className={`inline-flex items-center gap-2 ${className}`} {...props}>
            <span
                className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-xs font-bold"
                style={{ backgroundColor: cm.green, color: cm.yellow }}
            >
                CM
            </span>
            {showName && (
                <span
                    className="text-lg font-bold"
                    style={{ color: cm.greenDark }}
                >
                    {appName}
                </span>
            )}
        </span>
    );
}
