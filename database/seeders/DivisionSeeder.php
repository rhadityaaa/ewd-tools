<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('divisions')->insert([
            ['code' => 'HR', 'name' => 'Human Resources', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'FIN', 'name' => 'Finance', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'IT', 'name' => 'IT', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'MKT', 'name' => 'Marketing', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'OPS', 'name' => 'Operations', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'SAL', 'name' => 'Sales', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'CS', 'name' => 'Customer Service', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'LEG', 'name' => 'Legal', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'PRC', 'name' => 'Procurement', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'RND', 'name' => 'Research & Development', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
