<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Aspect;

class AspectSeeder extends Seeder
{
    public function run(): void
    {
        $aspects = [
            'FIN',
            'MAN', 
            'MKT',
            'OPR',
            'LIQ',
            'COL',
            'IND',
            'GOV',
            'ENV',
            'REG',
        ];
        
        foreach ($aspects as $code) {
            Aspect::firstOrCreate(
                ['code' => $code]
            );
        }
    }
}
