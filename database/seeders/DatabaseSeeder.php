<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            DivisionSeeder::class,
            // SuperAdminSeeder::class,
            UserRoleSeeder::class, // Add this new seeder
            BorrowerSeeder::class,
            BorrowerDetailSeeder::class,
            BorrowerFacilitySeeder::class,
            TemplateSeeder::class,
            AspectSeeder::class,
            AspectVersionSeeder::class,
            QuestionSeeder::class,
            QuestionVersionSeeder::class,
            QuestionOptionSeeder::class,
            PeriodSeeder::class,
            VisibilityRuleSeeder::class,
            WatchlistSeeder::class,
        ]);
    }
}
