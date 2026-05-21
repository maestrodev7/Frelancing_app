<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * Default countries for the client registration form (Central Africa).
     */
    public function run(): void
    {
        $countries = [
            ['name' => 'Cameroun', 'code' => 'CM'],
            ['name' => 'République démocratique du Congo', 'code' => 'CD'],
            ['name' => 'Congo', 'code' => 'CG'],
            ['name' => 'Gabon', 'code' => 'GA'],
            ['name' => "Côte d'Ivoire", 'code' => 'CI'],
        ];

        $codes = collect($countries)->pluck('code');

        collect($countries)->each(fn (array $country) => Country::query()->firstOrCreate(
            ['code' => $country['code']],
            ['name' => $country['name']],
        ));

        Country::query()
            ->whereNotIn('code', $codes)
            ->whereDoesntHave('users')
            ->delete();
    }
}
