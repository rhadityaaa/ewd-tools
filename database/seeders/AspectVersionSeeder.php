<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AspectVersion;
use App\Models\Aspect;
use Carbon\Carbon;

class AspectVersionSeeder extends Seeder
{
    public function run(): void
    {
        $aspectData = [
            'FIN' => 'Financial Performance',
            'MAN' => 'Management Quality',
            'MKT' => 'Market Position',
            'OPR' => 'Operational Efficiency',
            'LIQ' => 'Liquidity Position',
            'COL' => 'Collateral Quality',
            'IND' => 'Industry Analysis',
            'GOV' => 'Corporate Governance',
            'ENV' => 'Environmental Risk',
            'REG' => 'Regulatory Compliance',
        ];
        
        foreach ($aspectData as $code => $name) {
            $aspect = Aspect::where('code', $code)->first();
            
            if ($aspect) {
                // Create 1-2 versions for each aspect
                for ($version = 1; $version <= rand(1, 2); $version++) {
                    AspectVersion::firstOrCreate(
                        [
                            'aspect_id' => $aspect->id,
                            'version_number' => $version
                        ],
                        [
                            'name' => $name . " v{$version}",
                            'description' => "Version {$version} of {$name} assessment criteria",
                            'effective_from' => Carbon::now()->subMonths(rand(1, 6)),
                        ]
                    );
                }
            }
        }
    }
}
