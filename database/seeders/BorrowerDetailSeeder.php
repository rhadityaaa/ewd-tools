<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BorrowerDetail;

class BorrowerDetailSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            BorrowerDetail::create([
                'borrower_id' => 1,
                'borrower_group' => null,
                'purpose' => 'Purpose ' . $i,
                'economic_sector' => 'Sector ' . $i,
                'business_field' => 'Field ' . $i,
                'collectibility' => 1,
                'restructuring' => false,
            ]);
        }
    }
}
