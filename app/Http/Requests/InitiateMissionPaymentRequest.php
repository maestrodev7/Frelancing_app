<?php

namespace App\Http\Requests;

use App\Domain\Payments\Enums\PaymentMethod;
use App\Models\Proposal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitiateMissionPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $proposal = $this->route('proposal');
        if ($proposal instanceof Proposal) {
            $proposal->loadMissing('mission');
        }

        return $this->user()?->isClient()
            && $proposal instanceof Proposal
            && $proposal->mission->user_id === $this->user()->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'payer_phone' => [
                Rule::requiredIf(fn () => $this->input('payment_method') !== PaymentMethod::Card->value),
                'nullable',
                'string',
                'max:30',
            ],
        ];
    }
}
