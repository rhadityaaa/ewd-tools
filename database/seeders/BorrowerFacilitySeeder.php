<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BorrowerFacility;
use App\Models\Borrower;
use Carbon\Carbon;

class BorrowerFacilitySeeder extends Seeder
{
    public function run(): void
    {
        $facilityTypes = [
            'Term Loan', 'Working Capital Loan', 'Overdraft', 'Letter of Credit',
            'Bank Guarantee', 'Trade Finance', 'Mortgage Loan', 'Investment Loan'
        ];
        
        // Get all borrowers
        $borrowers = Borrower::all();
        
        foreach ($borrowers as $borrower) {
            // Each borrower can have 1-3 facilities
            $facilityCount = rand(1, 3);
            
            for ($i = 0; $i < $facilityCount; $i++) {
                // Limit values to fit decimal(12,2) - max 9,999,999,999.99
                // Using realistic banking amounts: 10M to 5B (in cents, then divide by 100)
                $limitCents = rand(1000000000, 500000000000); // 10M to 5B in cents
                $limit = $limitCents / 100; // Convert to proper decimal
                
                $outstanding = $limit * (rand(20, 95) / 100); // 20-95% utilization
                $interestRate = rand(600, 1500) / 100; // 6% to 15%
                
                // Some facilities have arrears
                $hasArrears = rand(1, 10) <= 2; // 20% chance
                $principalArrears = $hasArrears ? $outstanding * (rand(1, 10) / 100) : 0;
                $interestArrears = $hasArrears ? $principalArrears * (rand(5, 20) / 100) : 0;
                $pdoDays = $hasArrears ? rand(1, 180) : 0;
                
                // Use firstOrCreate to prevent duplicates
                BorrowerFacility::firstOrCreate(
                    [
                        'borrower_id' => $borrower->id,
                        'facility_name' => $facilityTypes[array_rand($facilityTypes)] . ' - ' . ($i + 1),
                    ],
                    [
                        'limit' => round($limit, 2),
                        'outstanding' => round($outstanding, 2),
                        'interest_rate' => $interestRate,
                        'principal_arrears' => round($principalArrears, 2),
                        'interest_arrears' => round($interestArrears, 2),
                        'pdo_days' => $pdoDays,
                        'maturity_date' => Carbon::now()->addMonths(rand(12, 60))->format('Y-m-d'),
                    ]
                );
            }
        }
    }
}
