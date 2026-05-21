<?php

namespace App\Http\Requests\Auth;

use App\Application\Auth\Data\RegisterClientData;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

class StoreClientRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'billing_address' => ['required', 'string', 'max:500'],
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'timezone' => ['required', 'string', 'timezone'],
        ];
    }

    public function toData(): RegisterClientData
    {
        $validated = $this->validated();

        return new RegisterClientData(
            name: $validated['name'],
            email: $validated['email'],
            phone: $validated['phone'],
            password: $validated['password'],
            billingAddress: $validated['billing_address'],
            countryId: $validated['country_id'],
            timezone: $validated['timezone'],
        );
    }
}
