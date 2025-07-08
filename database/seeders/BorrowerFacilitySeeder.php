<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BorrowerFacility;

class BorrowerFacilitySeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            BorrowerFacility::create([
                'borrower_id' => 1,
                'facility_name' => 'Fasilitas ' . $i,
                'amount' => 1000000 * $i,
            ]);
        }
    }
}
