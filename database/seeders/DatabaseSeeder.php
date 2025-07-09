<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            DivisionSeeder::class,
            BorrowerSeeder::class,
            SuperAdminSeeder::class,
            AspectSeeder::class,
            AspectVersionSeeder::class,
            QuestionSeeder::class,
            QuestionVersionSeeder::class,
            QuestionOptionSeeder::class,
            TemplateSeeder::class,
            VisibilityRuleSeeder::class,
            PeriodSeeder::class
        ]);
    }
}
