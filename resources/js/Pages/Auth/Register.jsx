import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

export default function Register({ countries = [] }) {
    const [currentStep, setCurrentStep] = useState(0);
    const [stepErrors, setStepErrors] = useState({});
    const [formMessage, setFormMessage] = useState('');

    const { data, setData, transform, post, processing, errors, reset } =
        useForm({
            account_type: '',
            name: '',
            email: '',
            phone: '',
            billing_address: '',
            country_id: '',
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC',
            password: '',
            password_confirmation: '',
            title: '',
            bio: '',
            hourly_rate_default: '',
            currency: 'XAF',
            experience_years: '',
            availability_status: 'available',
            portfolio_url: '',
            linkedin_url: '',
            skills: [{ name: '', level: 3 }],
        });

    const steps = useMemo(() => {
        if (data.account_type === 'freelancer') {
            return [
                { key: 'account_type', label: 'Type de compte' },
                { key: 'basic_info', label: 'Informations du compte' },
                { key: 'freelancer_profile', label: 'Profil professionnel' },
                { key: 'skills', label: 'Compétences' },
            ];
        }

        if (data.account_type === 'client') {
            return [
                { key: 'account_type', label: 'Type de compte' },
                { key: 'client_details', label: 'Informations du compte' },
            ];
        }

        return [{ key: 'account_type', label: 'Type de compte' }];
    }, [data.account_type]);

    useEffect(() => {
        setCurrentStep((step) => Math.min(step, steps.length - 1));
    }, [steps.length]);

    const currentStepKey = steps[currentStep]?.key ?? 'account_type';
    const isLastStep = currentStep === steps.length - 1;
    const isFreelancer = data.account_type === 'freelancer';

    const accountTypes = [
        {
            value: 'client',
            title: 'Porteur de projet',
            description:
                'Publiez des missions, échangez avec des talents et pilotez vos collaborations.',
        },
        {
            value: 'freelancer',
            title: 'Freelance',
            description:
                'Présentez vos compétences, définissez votre positionnement et recevez des opportunités.',
        },
    ];

    const availabilityOptions = [
        { value: 'available', label: 'Disponible maintenant' },
        { value: 'limited', label: 'Disponible partiellement' },
        { value: 'unavailable', label: 'Indisponible pour le moment' },
    ];

    const currencyOptions = ['XAF', 'EUR', 'USD', 'GBP'];

    const stepIndexByKey = steps.reduce((accumulator, step, index) => {
        accumulator[step.key] = index;

        return accumulator;
    }, {});

    const fieldToStepKey = {
        account_type: 'account_type',
        name: isFreelancer ? 'basic_info' : 'client_details',
        email: isFreelancer ? 'basic_info' : 'client_details',
        phone: isFreelancer ? 'basic_info' : 'client_details',
        password: isFreelancer ? 'basic_info' : 'client_details',
        password_confirmation: isFreelancer ? 'basic_info' : 'client_details',
        country_id: isFreelancer ? 'basic_info' : 'client_details',
        billing_address: 'client_details',
        timezone: isFreelancer ? 'freelancer_profile' : 'client_details',
        title: 'freelancer_profile',
        bio: 'freelancer_profile',
        hourly_rate_default: 'freelancer_profile',
        currency: 'freelancer_profile',
        experience_years: 'freelancer_profile',
        availability_status: 'freelancer_profile',
        portfolio_url: 'freelancer_profile',
        linkedin_url: 'freelancer_profile',
        skills: 'skills',
    };

    const moveToFirstServerErrorStep = (serverErrors) => {
        const firstField = Object.keys(serverErrors)[0];

        if (!firstField) {
            return;
        }

        const normalizedField = firstField.startsWith('skills.')
            ? 'skills'
            : firstField;
        const stepKey = fieldToStepKey[normalizedField];
        const stepIndex = stepIndexByKey[stepKey];

        if (typeof stepIndex === 'number') {
            setCurrentStep(stepIndex);
        }
    };

    const updateSkill = (index, field, value) => {
        setFormMessage('');
        setData(
            'skills',
            data.skills.map((skill, skillIndex) =>
                skillIndex === index ? { ...skill, [field]: value } : skill,
            ),
        );
    };

    const addSkill = () => {
        setFormMessage('');

        if (data.skills.length >= 8) {
            return;
        }

        setData('skills', [...data.skills, { name: '', level: 3 }]);
    };

    const removeSkill = (index) => {
        setFormMessage('');
        setData(
            'skills',
            data.skills.filter((_, skillIndex) => skillIndex !== index),
        );
    };

    const selectAccountType = (accountType) => {
        setFormMessage('');
        setData('account_type', accountType);
        setStepErrors({});
        setCurrentStep(0);
    };

    const validateCurrentStep = () => {
        const nextErrors = {};

        const requireField = (field, message) => {
            if (!String(data[field] ?? '').trim()) {
                nextErrors[field] = message;
            }
        };

        if (currentStepKey === 'account_type') {
            requireField('account_type', 'Choisissez un type de compte.');
        }

        if (currentStepKey === 'basic_info') {
            requireField('name', 'Le nom est requis.');
            requireField('email', "L'email est requis.");
            requireField('phone', 'Le téléphone est requis.');
            requireField('country_id', 'Le pays est requis.');
            requireField('password', 'Le mot de passe est requis.');
            requireField(
                'password_confirmation',
                'La confirmation du mot de passe est requise.',
            );

            if (
                data.password &&
                data.password_confirmation &&
                data.password !== data.password_confirmation
            ) {
                nextErrors.password_confirmation =
                    'Les mots de passe ne correspondent pas.';
            }
        }

        if (currentStepKey === 'client_details') {
            requireField('name', 'Le nom est requis.');
            requireField('email', "L'email est requis.");
            requireField('phone', 'Le téléphone est requis.');
            requireField('billing_address', "L'adresse de facturation est requise.");
            requireField('country_id', 'Le pays est requis.');
            requireField('timezone', 'Le fuseau horaire est requis.');
            requireField('password', 'Le mot de passe est requis.');
            requireField(
                'password_confirmation',
                'La confirmation du mot de passe est requise.',
            );

            if (
                data.password &&
                data.password_confirmation &&
                data.password !== data.password_confirmation
            ) {
                nextErrors.password_confirmation =
                    'Les mots de passe ne correspondent pas.';
            }
        }

        if (currentStepKey === 'freelancer_profile') {
            requireField('title', 'Le titre professionnel est requis.');
            requireField('bio', 'La biographie est requise.');
            requireField('timezone', 'Le fuseau horaire est requis.');
            requireField('currency', 'La devise est requise.');
            requireField(
                'experience_years',
                "Le nombre d'années d'expérience est requis.",
            );
            requireField(
                'availability_status',
                'Le statut de disponibilité est requis.',
            );
        }

        if (currentStepKey === 'skills') {
            const usableSkills = data.skills.filter((skill) =>
                String(skill.name ?? '').trim(),
            );

            if (usableSkills.length === 0) {
                nextErrors.skills =
                    'Ajoutez au moins une compétence pour créer un profil attractif.';
            }
        }

        setStepErrors(nextErrors);

        return Object.keys(nextErrors).length === 0;
    };

    const goToNextStep = () => {
        if (!validateCurrentStep()) {
            return;
        }

        setFormMessage('');
        setCurrentStep((step) => Math.min(step + 1, steps.length - 1));
    };

    const submit = (e) => {
        e.preventDefault();

        if (!validateCurrentStep()) {
            return;
        }

        setFormMessage('');

        transform((payload) => ({
            ...payload,
            skills: payload.skills.filter((skill) => skill.name.trim() !== ''),
        }));

        post(route('register'), {
            onError: (serverErrors) => {
                setFormMessage(
                    "Certaines informations ne sont pas valides. Corrigez les champs indiqués.",
                );
                moveToFirstServerErrorStep(serverErrors);
            },
            onSuccess: () => {
                setFormMessage('Compte créé avec succès. Redirection...');
                router.visit(route('dashboard'));
            },
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Créer un compte" />

            <div className="mb-6">
                <h1 className="text-xl font-semibold text-gray-900">
                    Créer votre compte
                </h1>
                <p className="mt-2 text-sm text-gray-600">
                    Commencez par choisir votre type de compte, puis complétez
                    les informations utiles pour démarrer rapidement sur la
                    plateforme.
                </p>
            </div>

            {formMessage && (
                <div className="mb-4 rounded-lg border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-700">
                    {formMessage}
                </div>
            )}

            <div className="mb-6 flex flex-wrap gap-2">
                {steps.map((step, index) => (
                    <div
                        key={step.key}
                        className={`rounded-full px-3 py-1 text-xs font-medium ${
                            index === currentStep
                                ? 'bg-indigo-600 text-white'
                                : 'bg-gray-100 text-gray-600'
                        }`}
                    >
                        {index + 1}. {step.label}
                    </div>
                ))}
            </div>

            <form onSubmit={submit}>
                {currentStepKey === 'account_type' && (
                    <div>
                        <p className="text-sm font-medium text-gray-700">
                            Choisissez le type de compte qui correspond le mieux
                            à votre usage.
                        </p>

                        <div className="mt-4 grid gap-4 sm:grid-cols-2">
                            {accountTypes.map((accountType) => {
                                const isSelected =
                                    data.account_type === accountType.value;

                                return (
                                    <button
                                        key={accountType.value}
                                        type="button"
                                        onClick={() =>
                                            selectAccountType(accountType.value)
                                        }
                                        className={`rounded-xl border p-4 text-left transition ${
                                            isSelected
                                                ? 'border-indigo-600 bg-indigo-50'
                                                : 'border-gray-200 hover:border-indigo-300'
                                        }`}
                                    >
                                        <div className="text-base font-semibold text-gray-900">
                                            {accountType.title}
                                        </div>
                                        <p className="mt-2 text-sm text-gray-600">
                                            {accountType.description}
                                        </p>
                                    </button>
                                );
                            })}
                        </div>

                        <InputError
                            message={
                                stepErrors.account_type || errors.account_type
                            }
                            className="mt-3"
                        />
                    </div>
                )}

                {currentStepKey === 'client_details' && (
                    <div className="space-y-4">
                        <div>
                            <InputLabel htmlFor="name" value="Nom complet" />
                            <TextInput
                                id="name"
                                name="name"
                                value={data.name}
                                className="mt-1 block w-full"
                                autoComplete="name"
                                isFocused={true}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                            />
                            <InputError
                                message={stepErrors.name || errors.name}
                                className="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="email" value="Email" />
                            <TextInput
                                id="email"
                                type="email"
                                name="email"
                                value={data.email}
                                className="mt-1 block w-full"
                                autoComplete="username"
                                onChange={(e) => setData('email', e.target.value)}
                                required
                            />
                            <InputError
                                message={stepErrors.email || errors.email}
                                className="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="phone" value="Téléphone" />
                            <TextInput
                                id="phone"
                                type="tel"
                                name="phone"
                                value={data.phone}
                                className="mt-1 block w-full"
                                autoComplete="tel"
                                onChange={(e) => setData('phone', e.target.value)}
                                required
                            />
                            <InputError
                                message={stepErrors.phone || errors.phone}
                                className="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel
                                htmlFor="billing_address"
                                value="Adresse de facturation"
                            />
                            <TextInput
                                id="billing_address"
                                name="billing_address"
                                value={data.billing_address}
                                className="mt-1 block w-full"
                                autoComplete="street-address"
                                onChange={(e) =>
                                    setData('billing_address', e.target.value)
                                }
                                required
                            />
                            <InputError
                                message={
                                    stepErrors.billing_address ||
                                    errors.billing_address
                                }
                                className="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="country_id" value="Pays" />
                            <select
                                id="country_id"
                                name="country_id"
                                value={data.country_id}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                onChange={(e) =>
                                    setData('country_id', e.target.value)
                                }
                                required
                            >
                                <option value="">Sélectionner un pays</option>
                                {countries.map((country) => (
                                    <option key={country.id} value={country.id}>
                                        {country.name}
                                    </option>
                                ))}
                            </select>
                            <InputError
                                message={stepErrors.country_id || errors.country_id}
                                className="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="timezone" value="Fuseau horaire" />
                            <TextInput
                                id="timezone"
                                name="timezone"
                                value={data.timezone}
                                className="mt-1 block w-full"
                                onChange={(e) =>
                                    setData('timezone', e.target.value)
                                }
                                required
                            />
                            <InputError
                                message={stepErrors.timezone || errors.timezone}
                                className="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="password" value="Mot de passe" />
                            <TextInput
                                id="password"
                                type="password"
                                name="password"
                                value={data.password}
                                className="mt-1 block w-full"
                                autoComplete="new-password"
                                onChange={(e) =>
                                    setData('password', e.target.value)
                                }
                                required
                            />
                            <InputError
                                message={stepErrors.password || errors.password}
                                className="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel
                                htmlFor="password_confirmation"
                                value="Confirmer le mot de passe"
                            />
                            <TextInput
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                value={data.password_confirmation}
                                className="mt-1 block w-full"
                                autoComplete="new-password"
                                onChange={(e) =>
                                    setData(
                                        'password_confirmation',
                                        e.target.value,
                                    )
                                }
                                required
                            />
                            <InputError
                                message={
                                    stepErrors.password_confirmation ||
                                    errors.password_confirmation
                                }
                                className="mt-2"
                            />
                        </div>
                    </div>
                )}

                {currentStepKey === 'basic_info' && (
                    <div className="space-y-4">
                        <div>
                            <InputLabel htmlFor="name" value="Nom complet" />
                            <TextInput
                                id="name"
                                name="name"
                                value={data.name}
                                className="mt-1 block w-full"
                                autoComplete="name"
                                isFocused={true}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                            />
                            <InputError
                                message={stepErrors.name || errors.name}
                                className="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="email" value="Email" />
                            <TextInput
                                id="email"
                                type="email"
                                name="email"
                                value={data.email}
                                className="mt-1 block w-full"
                                autoComplete="username"
                                onChange={(e) => setData('email', e.target.value)}
                                required
                            />
                            <InputError
                                message={stepErrors.email || errors.email}
                                className="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="phone" value="Téléphone" />
                            <TextInput
                                id="phone"
                                type="tel"
                                name="phone"
                                value={data.phone}
                                className="mt-1 block w-full"
                                autoComplete="tel"
                                onChange={(e) => setData('phone', e.target.value)}
                                required
                            />
                            <InputError
                                message={stepErrors.phone || errors.phone}
                                className="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="country_id" value="Pays" />
                            <select
                                id="country_id"
                                name="country_id"
                                value={data.country_id}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                onChange={(e) =>
                                    setData('country_id', e.target.value)
                                }
                                required
                            >
                                <option value="">Sélectionner un pays</option>
                                {countries.map((country) => (
                                    <option key={country.id} value={country.id}>
                                        {country.name}
                                    </option>
                                ))}
                            </select>
                            <InputError
                                message={stepErrors.country_id || errors.country_id}
                                className="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="password" value="Mot de passe" />
                            <TextInput
                                id="password"
                                type="password"
                                name="password"
                                value={data.password}
                                className="mt-1 block w-full"
                                autoComplete="new-password"
                                onChange={(e) =>
                                    setData('password', e.target.value)
                                }
                                required
                            />
                            <InputError
                                message={stepErrors.password || errors.password}
                                className="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel
                                htmlFor="password_confirmation"
                                value="Confirmer le mot de passe"
                            />
                            <TextInput
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                value={data.password_confirmation}
                                className="mt-1 block w-full"
                                autoComplete="new-password"
                                onChange={(e) =>
                                    setData(
                                        'password_confirmation',
                                        e.target.value,
                                    )
                                }
                                required
                            />
                            <InputError
                                message={
                                    stepErrors.password_confirmation ||
                                    errors.password_confirmation
                                }
                                className="mt-2"
                            />
                        </div>
                    </div>
                )}

                {currentStepKey === 'freelancer_profile' && (
                    <div className="space-y-4">
                        <div>
                            <InputLabel
                                htmlFor="title"
                                value="Titre professionnel"
                            />
                            <TextInput
                                id="title"
                                name="title"
                                value={data.title}
                                className="mt-1 block w-full"
                                placeholder="Ex: Développeur Laravel & React"
                                onChange={(e) => setData('title', e.target.value)}
                                required
                            />
                            <InputError
                                message={stepErrors.title || errors.title}
                                className="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="bio" value="Bio" />
                            <textarea
                                id="bio"
                                name="bio"
                                value={data.bio}
                                rows={5}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Présentez votre spécialité, votre valeur ajoutée et le type de missions que vous recherchez."
                                onChange={(e) => setData('bio', e.target.value)}
                                required
                            />
                            <InputError
                                message={stepErrors.bio || errors.bio}
                                className="mt-2"
                            />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel
                                    htmlFor="experience_years"
                                    value="Années d'expérience"
                                />
                                <TextInput
                                    id="experience_years"
                                    type="number"
                                    min="0"
                                    name="experience_years"
                                    value={data.experience_years}
                                    className="mt-1 block w-full"
                                    onChange={(e) =>
                                        setData(
                                            'experience_years',
                                            e.target.value,
                                        )
                                    }
                                    required
                                />
                                <InputError
                                    message={
                                        stepErrors.experience_years ||
                                        errors.experience_years
                                    }
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="availability_status"
                                    value="Disponibilité"
                                />
                                <select
                                    id="availability_status"
                                    name="availability_status"
                                    value={data.availability_status}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    onChange={(e) =>
                                        setData(
                                            'availability_status',
                                            e.target.value,
                                        )
                                    }
                                    required
                                >
                                    {availabilityOptions.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    message={
                                        stepErrors.availability_status ||
                                        errors.availability_status
                                    }
                                    className="mt-2"
                                />
                            </div>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel
                                    htmlFor="hourly_rate_default"
                                    value="Taux horaire par défaut"
                                />
                                <TextInput
                                    id="hourly_rate_default"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    name="hourly_rate_default"
                                    value={data.hourly_rate_default}
                                    className="mt-1 block w-full"
                                    placeholder="Optionnel"
                                    onChange={(e) =>
                                        setData(
                                            'hourly_rate_default',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={
                                        stepErrors.hourly_rate_default ||
                                        errors.hourly_rate_default
                                    }
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel htmlFor="currency" value="Devise" />
                                <select
                                    id="currency"
                                    name="currency"
                                    value={data.currency}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    onChange={(e) =>
                                        setData('currency', e.target.value)
                                    }
                                    required
                                >
                                    {currencyOptions.map((currency) => (
                                        <option key={currency} value={currency}>
                                            {currency}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    message={stepErrors.currency || errors.currency}
                                    className="mt-2"
                                />
                            </div>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel
                                    htmlFor="timezone"
                                    value="Fuseau horaire"
                                />
                                <TextInput
                                    id="timezone"
                                    name="timezone"
                                    value={data.timezone}
                                    className="mt-1 block w-full"
                                    onChange={(e) =>
                                        setData('timezone', e.target.value)
                                    }
                                    required
                                />
                                <InputError
                                    message={stepErrors.timezone || errors.timezone}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="portfolio_url"
                                    value="Lien portfolio"
                                />
                                <TextInput
                                    id="portfolio_url"
                                    type="url"
                                    name="portfolio_url"
                                    value={data.portfolio_url}
                                    className="mt-1 block w-full"
                                    placeholder="Optionnel"
                                    onChange={(e) =>
                                        setData('portfolio_url', e.target.value)
                                    }
                                />
                                <InputError
                                    message={
                                        stepErrors.portfolio_url ||
                                        errors.portfolio_url
                                    }
                                    className="mt-2"
                                />
                            </div>
                        </div>

                        <div>
                            <InputLabel
                                htmlFor="linkedin_url"
                                value="Lien LinkedIn"
                            />
                            <TextInput
                                id="linkedin_url"
                                type="url"
                                name="linkedin_url"
                                value={data.linkedin_url}
                                className="mt-1 block w-full"
                                placeholder="Optionnel"
                                onChange={(e) =>
                                    setData('linkedin_url', e.target.value)
                                }
                            />
                            <InputError
                                message={
                                    stepErrors.linkedin_url || errors.linkedin_url
                                }
                                className="mt-2"
                            />
                        </div>

                        <div className="rounded-lg bg-gray-50 p-3 text-sm text-gray-600">
                            Vous pourrez toujours enrichir votre profil plus
                            tard depuis votre espace personnel.
                        </div>
                    </div>
                )}

                {currentStepKey === 'skills' && (
                    <div>
                        <div className="mb-4 flex items-center justify-between">
                            <div>
                                <h2 className="text-base font-semibold text-gray-900">
                                    Mettez en avant vos compétences
                                </h2>
                                <p className="mt-1 text-sm text-gray-600">
                                    Ajoutez les compétences qui aideront un
                                    porteur de projet à comprendre rapidement
                                    votre positionnement.
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={addSkill}
                                className="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                            >
                                Ajouter
                            </button>
                        </div>

                        <div className="space-y-4">
                            {data.skills.map((skill, index) => (
                                <div
                                    key={index}
                                    className="rounded-xl border border-gray-200 p-4"
                                >
                                    <div className="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_220px_auto] lg:gap-6">
                                        <div>
                                            <InputLabel
                                                htmlFor={`skill-name-${index}`}
                                                value={`Compétence ${index + 1}`}
                                            />
                                            <TextInput
                                                id={`skill-name-${index}`}
                                                value={skill.name}
                                                className="mt-1 block w-full"
                                                placeholder="Ex: Laravel"
                                                onChange={(e) =>
                                                    updateSkill(
                                                        index,
                                                        'name',
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={
                                                    errors[`skills.${index}.name`]
                                                }
                                                className="mt-2"
                                            />
                                        </div>

                                        <div>
                                            <InputLabel
                                                htmlFor={`skill-level-${index}`}
                                                value="Niveau"
                                            />
                                            <select
                                                id={`skill-level-${index}`}
                                                value={skill.level}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                onChange={(e) =>
                                                    updateSkill(
                                                        index,
                                                        'level',
                                                        Number(e.target.value),
                                                    )
                                                }
                                            >
                                                <option value={1}>
                                                    1 - Notions
                                                </option>
                                                <option value={2}>
                                                    2 - Junior
                                                </option>
                                                <option value={3}>
                                                    3 - Confirmé
                                                </option>
                                                <option value={4}>
                                                    4 - Avancé
                                                </option>
                                                <option value={5}>
                                                    5 - Expert
                                                </option>
                                            </select>
                                            <InputError
                                                message={
                                                    errors[
                                                        `skills.${index}.level`
                                                    ]
                                                }
                                                className="mt-2"
                                            />
                                        </div>

                                        <div className="flex items-end lg:justify-end">
                                            <button
                                                type="button"
                                                onClick={() => removeSkill(index)}
                                                className="rounded-md border border-red-200 px-3 py-2 text-sm text-red-600 hover:bg-red-50"
                                                disabled={data.skills.length === 1}
                                            >
                                                Retirer
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>

                        <InputError
                            message={stepErrors.skills || errors.skills}
                            className="mt-3"
                        />
                    </div>
                )}

                <div className="mt-6 flex items-center justify-between">
                    <Link
                        href={route('login')}
                        className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Déjà inscrit ?
                    </Link>

                    <div className="flex items-center gap-3">
                        {currentStep > 0 && (
                            <button
                                type="button"
                                onClick={() => setCurrentStep(currentStep - 1)}
                                className="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                            >
                                Retour
                            </button>
                        )}

                        {!isLastStep ? (
                            <button
                                type="button"
                                onClick={goToNextStep}
                                className="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
                            >
                                Continuer
                            </button>
                        ) : (
                            <PrimaryButton className="ms-0" disabled={processing}>
                                {isFreelancer
                                    ? 'Créer mon profil freelance'
                                    : 'Créer mon compte'}
                            </PrimaryButton>
                        )}
                    </div>
                </div>
            </form>
        </GuestLayout>
    );
}
