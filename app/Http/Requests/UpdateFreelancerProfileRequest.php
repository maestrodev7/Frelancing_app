<?php

namespace App\Http\Requests;

use App\Domain\Users\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFreelancerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Freelancer;
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
            'currency' => strtoupper((string) $this->input('currency')),
            'skills' => $skills,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'bio' => ['required', 'string', 'max:2000'],
            'hourly_rate_default' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'experience_years' => ['required', 'integer', 'min:0', 'max:60'],
            'availability_status' => ['required', Rule::in(['available', 'limited', 'unavailable'])],
            'timezone' => ['required', 'string', 'timezone'],
            'portfolio_url' => ['nullable', 'url', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'skills' => ['required', 'array', 'min:1', 'max:8'],
            'skills.*.name' => ['required_with:skills', 'string', 'max:60'],
            'skills.*.level' => ['required_with:skills', 'integer', 'between:1,5'],
        ];
    }
}
