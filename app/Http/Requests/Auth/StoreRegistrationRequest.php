<?php

namespace App\Http\Requests\Auth;

use App\Application\Auth\Data\RegisterClientData;
use App\Application\Auth\Data\RegisterFreelancerData;
use App\Domain\Users\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class StoreRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $skills = collect($this->input('skills', []))
            ->filter(fn ($skill) => is_array($skill))
            ->map(fn (array $skill) => [
                'name' => trim((string) ($skill['name'] ?? '')),
                'level' => $skill['level'] ?? 3,
            ])
            ->values()
            ->all();

        $this->merge([
            'account_type' => strtolower((string) $this->input('account_type')),
            'currency' => strtoupper((string) $this->input('currency')),
            'email' => strtolower((string) $this->input('email')),
            'skills' => $skills,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'account_type' => [
                'required',
                Rule::in([UserRole::Client->value, UserRole::Freelancer->value]),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'timezone' => ['required', 'string', 'timezone'],
            'billing_address' => [
                Rule::requiredIf($this->accountType() === UserRole::Client),
                'nullable',
                'string',
                'max:500',
            ],
            'title' => [
                'exclude_unless:account_type,freelancer',
                Rule::requiredIf($this->accountType() === UserRole::Freelancer),
                'nullable',
                'string',
                'max:120',
            ],
            'bio' => [
                'exclude_unless:account_type,freelancer',
                Rule::requiredIf($this->accountType() === UserRole::Freelancer),
                'nullable',
                'string',
                'max:2000',
            ],
            'hourly_rate_default' => [
                'exclude_unless:account_type,freelancer',
                'nullable',
                'numeric',
                'min:0',
            ],
            'currency' => [
                'exclude_unless:account_type,freelancer',
                Rule::requiredIf($this->accountType() === UserRole::Freelancer),
                'nullable',
                'string',
                'size:3',
            ],
            'experience_years' => [
                'exclude_unless:account_type,freelancer',
                Rule::requiredIf($this->accountType() === UserRole::Freelancer),
                'nullable',
                'integer',
                'min:0',
                'max:60',
            ],
            'availability_status' => [
                'exclude_unless:account_type,freelancer',
                Rule::requiredIf($this->accountType() === UserRole::Freelancer),
                'nullable',
                Rule::in(['available', 'limited', 'unavailable']),
            ],
            'portfolio_url' => ['exclude_unless:account_type,freelancer', 'nullable', 'url', 'max:255'],
            'linkedin_url' => ['exclude_unless:account_type,freelancer', 'nullable', 'url', 'max:255'],
            'skills' => [
                'exclude_unless:account_type,freelancer',
                Rule::requiredIf($this->accountType() === UserRole::Freelancer),
                'nullable',
                'array',
                'min:1',
                'max:8',
            ],
            'skills.*.name' => ['exclude_unless:account_type,freelancer', 'required_with:skills', 'string', 'max:60'],
            'skills.*.level' => ['exclude_unless:account_type,freelancer', 'required_with:skills', 'integer', 'between:1,5'],
        ];
    }

    public function accountType(): UserRole
    {
        $role = $this->input('account_type');

        return $role === UserRole::Freelancer->value
            ? UserRole::Freelancer
            : UserRole::Client;
    }

    public function toClientData(): RegisterClientData
    {
        $validated = $this->validated();

        return new RegisterClientData(
            name: $validated['name'],
            email: $validated['email'],
            phone: $validated['phone'],
            password: $validated['password'],
            billingAddress: $validated['billing_address'],
            countryId: (int) $validated['country_id'],
            timezone: $validated['timezone'],
        );
    }

    public function toFreelancerData(): RegisterFreelancerData
    {
        $validated = $this->validated();

        return new RegisterFreelancerData(
            name: $validated['name'],
            email: $validated['email'],
            phone: $validated['phone'],
            password: $validated['password'],
            countryId: (int) $validated['country_id'],
            title: $validated['title'],
            bio: $validated['bio'],
            hourlyRateDefault: isset($validated['hourly_rate_default'])
                ? (string) $validated['hourly_rate_default']
                : null,
            currency: $validated['currency'],
            experienceYears: (int) $validated['experience_years'],
            availabilityStatus: $validated['availability_status'],
            timezone: $validated['timezone'],
            portfolioUrl: $validated['portfolio_url'] ?? null,
            linkedinUrl: $validated['linkedin_url'] ?? null,
            skills: collect($validated['skills'] ?? [])
                ->map(fn (array $skill): array => [
                    'name' => trim($skill['name']),
                    'level' => (int) $skill['level'],
                ])
                ->all(),
        );
    }
}
