<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Aspect;

class AspectSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            Aspect::create([
                'code' => 'ASP' . $i,
            ]);
        }
    }
}
