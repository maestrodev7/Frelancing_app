<?php

namespace App\Http\Requests;

use App\Domain\Missions\Enums\PricingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isFreelancer() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'pricing_type' => strtolower((string) $this->input('pricing_type')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cover_letter' => ['required', 'string', 'max:5000'],
            'pricing_type' => ['required', Rule::enum(PricingType::class)],
            'amount_fixed' => [
                Rule::requiredIf($this->input('pricing_type') === PricingType::Fixed->value),
                'nullable',
                'numeric',
                'min:0',
            ],
            'hourly_rate' => [
                Rule::requiredIf($this->input('pricing_type') === PricingType::Hourly->value),
                'nullable',
                'numeric',
                'min:0',
            ],
            'estimated_hours' => [
                Rule::requiredIf($this->input('pricing_type') === PricingType::Hourly->value),
                'nullable',
                'integer',
                'min:1',
            ],
            'delivery_days' => ['required', 'integer', 'min:1', 'max:365'],
        ];
    }
}
