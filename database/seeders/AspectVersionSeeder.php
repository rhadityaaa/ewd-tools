<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AspectVersion;

class AspectVersionSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            AspectVersion::create([
                'aspect_id' => $i,
                'version_number' => $i,
                'name' => 'Versi ' . $i,
                'description' => 'Deskripsi versi ' . $i,
                'effective_form' => now(),
            ]);
        }
    }
}
