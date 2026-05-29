<?php

namespace App\Http\Requests;

use App\Domain\Missions\Enums\MissionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isClient() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'currency' => strtoupper((string) $this->input('currency', 'XAF')),
            'type' => strtolower((string) $this->input('type')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:10000'],
            'type' => ['required', Rule::enum(MissionType::class)],
            'budget_min' => ['nullable', 'numeric', 'min:0'],
            'budget_max' => ['nullable', 'numeric', 'min:0', 'gte:budget_min'],
            'hourly_cap' => [
                Rule::requiredIf($this->input('type') === MissionType::Hourly->value),
                'nullable',
                'numeric',
                'min:0',
            ],
            'currency' => ['required', 'string', 'size:3'],
            'start_expected_at' => ['nullable', 'date'],
            'deadline_at' => ['nullable', 'date', 'after_or_equal:start_expected_at'],
        ];
    }
}
