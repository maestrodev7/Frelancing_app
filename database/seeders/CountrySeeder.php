<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * Seed the application's countries.
     */
    public function run(): void
    {
        collect([
            ['name' => 'Cameroun', 'code' => 'CM'],
            ['name' => 'France', 'code' => 'FR'],
            ['name' => 'Maroc', 'code' => 'MA'],
            ['name' => 'Senegal', 'code' => 'SN'],
            ['name' => "Cote d'Ivoire", 'code' => 'CI'],
            ['name' => 'Canada', 'code' => 'CA'],
        ])->each(fn (array $country) => Country::query()->firstOrCreate(
            ['code' => $country['code']],
            ['name' => $country['name']],
        ));
    }
}
