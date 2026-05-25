import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Transition } from '@headlessui/react';
import { useForm } from '@inertiajs/react';

export default function UpdateFreelancerProfileForm({
    profile,
    className = '',
}) {
    const {
        data,
        setData,
        transform,
        patch,
        errors,
        processing,
        recentlySuccessful,
    } = useForm({
            title: profile?.title ?? '',
            bio: profile?.bio ?? '',
            hourly_rate_default: profile?.hourly_rate_default ?? '',
            currency: profile?.currency ?? 'XAF',
            experience_years: profile?.experience_years ?? '',
            availability_status: profile?.availability_status ?? 'available',
            timezone:
                profile?.timezone ??
                Intl.DateTimeFormat().resolvedOptions().timeZone ??
                'UTC',
            portfolio_url: profile?.portfolio_url ?? '',
            linkedin_url: profile?.linkedin_url ?? '',
            skills: profile?.skills?.length
                ? profile.skills
                : [{ name: '', level: 3 }],
        });

    const availabilityOptions = [
        { value: 'available', label: 'Disponible maintenant' },
        { value: 'limited', label: 'Disponible partiellement' },
        { value: 'unavailable', label: 'Indisponible pour le moment' },
    ];

    const currencyOptions = ['XAF', 'EUR', 'USD', 'GBP'];

    const updateSkill = (index, field, value) => {
        setData(
            'skills',
            data.skills.map((skill, skillIndex) =>
                skillIndex === index ? { ...skill, [field]: value } : skill,
            ),
        );
    };

    const addSkill = () => {
        if (data.skills.length >= 8) {
            return;
        }

        setData('skills', [...data.skills, { name: '', level: 3 }]);
    };

    const removeSkill = (index) => {
        setData(
            'skills',
            data.skills.filter((_, skillIndex) => skillIndex !== index),
        );
    };

    const submit = (e) => {
        e.preventDefault();

        transform((payload) => ({
            ...payload,
            skills: payload.skills.filter((skill) => skill.name.trim() !== ''),
        })).patch(route('profile.freelancer.update'), {
            preserveScroll: true,
        });
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-gray-900">
                    Profil freelance
                </h2>

                <p className="mt-1 text-sm text-gray-600">
                    Affinez votre positionnement, votre disponibilite et vos
                    competences pour rendre votre profil plus attractif.
                </p>
            </header>

            <form onSubmit={submit} className="mt-6 space-y-6">
                <div>
                    <InputLabel htmlFor="title" value="Titre professionnel" />
                    <TextInput
                        id="title"
                        value={data.title}
                        className="mt-1 block w-full"
                        onChange={(e) => setData('title', e.target.value)}
                        required
                    />
                    <InputError className="mt-2" message={errors.title} />
                </div>

                <div>
                    <InputLabel htmlFor="bio" value="Bio" />
                    <textarea
                        id="bio"
                        rows={5}
                        value={data.bio}
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        onChange={(e) => setData('bio', e.target.value)}
                        required
                    />
                    <InputError className="mt-2" message={errors.bio} />
                </div>

                <div className="grid gap-6 sm:grid-cols-2">
                    <div>
                        <InputLabel
                            htmlFor="experience_years"
                            value="Annees d'experience"
                        />
                        <TextInput
                            id="experience_years"
                            type="number"
                            min="0"
                            value={data.experience_years}
                            className="mt-1 block w-full"
                            onChange={(e) =>
                                setData('experience_years', e.target.value)
                            }
                            required
                        />
                        <InputError
                            className="mt-2"
                            message={errors.experience_years}
                        />
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="availability_status"
                            value="Disponibilite"
                        />
                        <select
                            id="availability_status"
                            value={data.availability_status}
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            onChange={(e) =>
                                setData('availability_status', e.target.value)
                            }
                            required
                        >
                            {availabilityOptions.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                        <InputError
                            className="mt-2"
                            message={errors.availability_status}
                        />
                    </div>
                </div>

                <div className="grid gap-6 sm:grid-cols-2">
                    <div>
                        <InputLabel
                            htmlFor="hourly_rate_default"
                            value="Taux horaire"
                        />
                        <TextInput
                            id="hourly_rate_default"
                            type="number"
                            min="0"
                            step="0.01"
                            value={data.hourly_rate_default}
                            className="mt-1 block w-full"
                            onChange={(e) =>
                                setData('hourly_rate_default', e.target.value)
                            }
                        />
                        <InputError
                            className="mt-2"
                            message={errors.hourly_rate_default}
                        />
                    </div>

                    <div>
                        <InputLabel htmlFor="currency" value="Devise" />
                        <select
                            id="currency"
                            value={data.currency}
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            onChange={(e) => setData('currency', e.target.value)}
                            required
                        >
                            {currencyOptions.map((currency) => (
                                <option key={currency} value={currency}>
                                    {currency}
                                </option>
                            ))}
                        </select>
                        <InputError className="mt-2" message={errors.currency} />
                    </div>
                </div>

                <div className="grid gap-6 sm:grid-cols-2">
                    <div>
                        <InputLabel htmlFor="timezone" value="Fuseau horaire" />
                        <TextInput
                            id="timezone"
                            value={data.timezone}
                            className="mt-1 block w-full"
                            onChange={(e) => setData('timezone', e.target.value)}
                            required
                        />
                        <InputError className="mt-2" message={errors.timezone} />
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="portfolio_url"
                            value="Lien portfolio"
                        />
                        <TextInput
                            id="portfolio_url"
                            type="url"
                            value={data.portfolio_url}
                            className="mt-1 block w-full"
                            onChange={(e) =>
                                setData('portfolio_url', e.target.value)
                            }
                        />
                        <InputError
                            className="mt-2"
                            message={errors.portfolio_url}
                        />
                    </div>
                </div>

                <div>
                    <InputLabel htmlFor="linkedin_url" value="Lien LinkedIn" />
                    <TextInput
                        id="linkedin_url"
                        type="url"
                        value={data.linkedin_url}
                        className="mt-1 block w-full"
                        onChange={(e) => setData('linkedin_url', e.target.value)}
                    />
                    <InputError className="mt-2" message={errors.linkedin_url} />
                </div>

                <div>
                    <div className="mb-4 flex items-center justify-between">
                        <div>
                            <h3 className="text-base font-medium text-gray-900">
                                Competences
                            </h3>
                            <p className="text-sm text-gray-600">
                                Maintenez une selection claire de vos expertises
                                principales.
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
                                key={`${index}-${skill.name}`}
                                className="rounded-xl border border-gray-200 p-4"
                            >
                                <div className="grid gap-4 sm:grid-cols-[1fr_180px_auto]">
                                    <div>
                                        <InputLabel
                                            htmlFor={`profile-skill-name-${index}`}
                                            value={`Competence ${index + 1}`}
                                        />
                                        <TextInput
                                            id={`profile-skill-name-${index}`}
                                            value={skill.name}
                                            className="mt-1 block w-full"
                                            onChange={(e) =>
                                                updateSkill(
                                                    index,
                                                    'name',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            className="mt-2"
                                            message={
                                                errors[`skills.${index}.name`]
                                            }
                                        />
                                    </div>

                                    <div>
                                        <InputLabel
                                            htmlFor={`profile-skill-level-${index}`}
                                            value="Niveau"
                                        />
                                        <select
                                            id={`profile-skill-level-${index}`}
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
                                            <option value={2}>2 - Junior</option>
                                            <option value={3}>
                                                3 - Confirme
                                            </option>
                                            <option value={4}>
                                                4 - Avance
                                            </option>
                                            <option value={5}>5 - Expert</option>
                                        </select>
                                        <InputError
                                            className="mt-2"
                                            message={
                                                errors[`skills.${index}.level`]
                                            }
                                        />
                                    </div>

                                    <div className="flex items-end">
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

                    <InputError className="mt-2" message={errors.skills} />
                </div>

                <div className="flex items-center gap-4">
                    <PrimaryButton disabled={processing}>Enregistrer</PrimaryButton>

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out"
                        leaveTo="opacity-0"
                    >
                        <p className="text-sm text-gray-600">Enregistre.</p>
                    </Transition>
                </div>
            </form>
        </section>
    );
}
