<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BorrowerDetailSeeder extends Seeder
{
    public function run(): void
    {
        $economicSectors = [
            'Manufacturing', 'Agriculture', 'Mining', 'Construction', 'Trade',
            'Transportation', 'Finance', 'Services', 'Real Estate', 'Technology'
        ];
        
        $businessFields = [
            'Automotive', 'Food & Beverage', 'Textile', 'Chemical', 'Electronics',
            'Pharmaceutical', 'Retail', 'Hospitality', 'Education', 'Healthcare'
        ];
        
        $borrowerBusinesses = [
            'Large Enterprise', 'Medium Enterprise', 'Small Enterprise', 'Micro Enterprise'
        ];
        
        $borrowerGroups = [
            'Individual', 'Corporate Group', 'Government', 'Cooperative', 'Foundation'
        ];
        
        $purposes = [
            'Working Capital', 'Investment', 'Refinancing', 'Trade Finance', 'Property Purchase',
            'Equipment Purchase', 'Business Expansion', 'Debt Consolidation'
        ];
        
        // Get all borrower IDs
        $borrowerIds = DB::table('borrowers')->pluck('id');
        
        foreach ($borrowerIds as $borrowerId) {
            DB::table('borrower_details')->insert([
                'borrower_id' => $borrowerId,
                'borrower_group' => $borrowerGroups[array_rand($borrowerGroups)],
                'purpose' => $purposes[array_rand($purposes)],
                'economic_sector' => $economicSectors[array_rand($economicSectors)],
                'business_field' => $businessFields[array_rand($businessFields)],
                'borrower_business' => $borrowerBusinesses[array_rand($borrowerBusinesses)],
                'collectibility' => rand(1, 5), // 1=Lancar, 2=DPK, 3=Kurang Lancar, 4=Diragukan, 5=Macet
                'restructuring' => rand(0, 1) == 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
