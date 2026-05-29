import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Create({ missionTypes = [], currencies = [] }) {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        type: 'fixed',
        budget_min: '',
        budget_max: '',
        hourly_cap: '',
        currency: 'XAF',
        start_expected_at: '',
        deadline_at: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('missions.store'));
    };

    const isHourly = data.type === 'hourly';

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Publier une mission
                </h2>
            }
        >
            <Head title="Publier une mission" />

            <div className="py-12">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <form
                        onSubmit={submit}
                        className="space-y-6 rounded-lg bg-white p-8 shadow-sm"
                    >
                        <div>
                            <InputLabel htmlFor="title" value="Titre de la mission" />
                            <TextInput
                                id="title"
                                value={data.title}
                                className="mt-1 block w-full"
                                onChange={(e) => setData('title', e.target.value)}
                                required
                            />
                            <InputError message={errors.title} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="description" value="Description" />
                            <textarea
                                id="description"
                                rows={6}
                                value={data.description}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                onChange={(e) =>
                                    setData('description', e.target.value)
                                }
                                required
                            />
                            <InputError message={errors.description} className="mt-2" />
                        </div>

                        <div className="grid gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="type" value="Type de mission" />
                                <select
                                    id="type"
                                    value={data.type}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    onChange={(e) => setData('type', e.target.value)}
                                >
                                    {missionTypes.map((type) => (
                                        <option key={type.value} value={type.value}>
                                            {type.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.type} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="currency" value="Devise" />
                                <select
                                    id="currency"
                                    value={data.currency}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    onChange={(e) =>
                                        setData('currency', e.target.value)
                                    }
                                >
                                    {currencies.map((currency) => (
                                        <option key={currency} value={currency}>
                                            {currency}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.currency} className="mt-2" />
                            </div>
                        </div>

                        <div className="grid gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="budget_min" value="Budget minimum" />
                                <TextInput
                                    id="budget_min"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={data.budget_min}
                                    className="mt-1 block w-full"
                                    onChange={(e) =>
                                        setData('budget_min', e.target.value)
                                    }
                                />
                                <InputError message={errors.budget_min} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="budget_max" value="Budget maximum" />
                                <TextInput
                                    id="budget_max"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={data.budget_max}
                                    className="mt-1 block w-full"
                                    onChange={(e) =>
                                        setData('budget_max', e.target.value)
                                    }
                                />
                                <InputError message={errors.budget_max} className="mt-2" />
                            </div>
                        </div>

                        {isHourly && (
                            <div>
                                <InputLabel
                                    htmlFor="hourly_cap"
                                    value="Plafond horaire"
                                />
                                <TextInput
                                    id="hourly_cap"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={data.hourly_cap}
                                    className="mt-1 block w-full"
                                    onChange={(e) =>
                                        setData('hourly_cap', e.target.value)
                                    }
                                />
                                <InputError message={errors.hourly_cap} className="mt-2" />
                            </div>
                        )}

                        <div className="grid gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel
                                    htmlFor="start_expected_at"
                                    value="Début souhaité"
                                />
                                <TextInput
                                    id="start_expected_at"
                                    type="date"
                                    value={data.start_expected_at}
                                    className="mt-1 block w-full"
                                    onChange={(e) =>
                                        setData('start_expected_at', e.target.value)
                                    }
                                />
                                <InputError
                                    message={errors.start_expected_at}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel htmlFor="deadline_at" value="Date limite" />
                                <TextInput
                                    id="deadline_at"
                                    type="date"
                                    value={data.deadline_at}
                                    className="mt-1 block w-full"
                                    onChange={(e) =>
                                        setData('deadline_at', e.target.value)
                                    }
                                />
                                <InputError message={errors.deadline_at} className="mt-2" />
                            </div>
                        </div>

                        <div className="flex items-center justify-between">
                            <Link
                                href={route('missions.index')}
                                className="text-sm text-gray-600 underline"
                            >
                                Annuler
                            </Link>
                            <PrimaryButton disabled={processing}>
                                Publier la mission
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
