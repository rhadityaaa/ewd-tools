<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Period;

class PeriodSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            Period::create([
                'name' => 'Periode ' . $i,
                'start_date' => now()->addMonths($i - 1)->startOfMonth(),
                'end_date' => now()->addMonths($i - 1)->endOfMonth(),
                'created_by' => 1,
            ]);
        }
    }
}
