<?php

namespace App\Http\Requests\Api;

use App\Domain\Users\Enums\UserRole;
use App\Models\Country;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class StoreStaffUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower((string) $this->input('email')),
            'role' => strtolower((string) $this->input('role')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', Rule::in([UserRole::Admin->value, UserRole::Secretary->value])],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
        ];
    }

    public function countryId(): int
    {
        return (int) ($this->input('country_id') ?? Country::query()->value('id') ?? 1);
    }
}
