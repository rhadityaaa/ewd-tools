<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Period;
use Carbon\Carbon;

class PeriodSeeder extends Seeder
{
    public function run(): void
    {
        // Create periods for the last 2 years and next year
        $startDate = Carbon::now()->subYears(2)->startOfYear();
        
        for ($i = 0; $i < 36; $i++) { // 3 years * 12 months
            $periodStart = $startDate->copy()->addMonths($i);
            $periodEnd = $periodStart->copy()->endOfMonth();
            $periodName = $periodStart->format('F Y');
            
            Period::firstOrCreate(
                ['name' => $periodName],
                [
                    'start_date' => $periodStart,
                    'end_date' => $periodEnd,
                    'created_by' => 1, // Super admin
                ]
            );
        }
        
        // Create quarterly periods
        for ($year = 2022; $year <= 2025; $year++) {
            for ($quarter = 1; $quarter <= 4; $quarter++) {
                $quarterStart = Carbon::createFromDate($year, ($quarter - 1) * 3 + 1, 1)->startOfMonth();
                $quarterEnd = $quarterStart->copy()->addMonths(2)->endOfMonth();
                $quarterName = "Q{$quarter} {$year}";
                
                Period::firstOrCreate(
                    ['name' => $quarterName],
                    [
                        'start_date' => $quarterStart,
                        'end_date' => $quarterEnd,
                        'created_by' => 1,
                    ]
                );
            }
        }
        
        // Create annual periods
        for ($year = 2022; $year <= 2025; $year++) {
            $annualName = "Annual {$year}";
            
            Period::firstOrCreate(
                ['name' => $annualName],
                [
                    'start_date' => Carbon::createFromDate($year, 1, 1),
                    'end_date' => Carbon::createFromDate($year, 12, 31),
                    'created_by' => 1,
                ]
            );
        }
    }
}
