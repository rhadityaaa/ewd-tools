<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VisibilityRule;

class VisibilityRuleSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            VisibilityRule::create([
                'entity_type' => 'App\\Models\\BorrowerDetail',
                'entity_id' => 1,
                'description' => 'Rule ' . $i,
                'source_type' => 'borrower_detail',
                'source_field' => 'field_' . $i,
                'operator' => '=',
                'value' => 'value_' . $i,
            ]);
        }
    }
}
