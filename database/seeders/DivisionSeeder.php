<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Division;

class DivisionSeeder extends Seeder
{
    public function run(): void
    {
        $divisions = [
            ['code' => 'CORP', 'name' => 'Corporate Banking'],
            ['code' => 'COMM', 'name' => 'Commercial Banking'],
            ['code' => 'SME', 'name' => 'Small Medium Enterprise'],
            ['code' => 'CONS', 'name' => 'Consumer Banking'],
            ['code' => 'MORT', 'name' => 'Mortgage Banking'],
            ['code' => 'TRAD', 'name' => 'Trade Finance'],
            ['code' => 'SYAR', 'name' => 'Syariah Banking'],
            ['code' => 'TREA', 'name' => 'Treasury'],
            ['code' => 'RISK', 'name' => 'Risk Management'],
            ['code' => 'COMP', 'name' => 'Compliance'],
        ];
        
        foreach ($divisions as $division) {
            Division::firstOrCreate(
                ['code' => $division['code']],
                ['name' => $division['name']]
            );
        }
    }
}
