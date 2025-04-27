<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['name' => 'United States', 'code' => 'USA'],
            ['name' => 'Canada', 'code' => 'CAN'],
            ['name' => 'United Kingdom', 'code' => 'GBR'],
            // Add more entries as needed
        ];

        foreach ($countries as $country) {
            Country::create($country);
        }
    }
}
