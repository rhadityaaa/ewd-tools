<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VisibilityRule;
use App\Models\QuestionVersion;
use App\Models\AspectVersion;
use App\Models\TemplateVersion;

class VisibilityRuleSeeder extends Seeder
{
    public function run(): void
    {
        $questionVersions = QuestionVersion::take(20)->get();
        $aspectVersions = AspectVersion::take(10)->get();
        $templateVersions = TemplateVersion::take(5)->get();
        
        // Create visibility rules for question versions
        foreach ($questionVersions as $questionVersion) {
            if (rand(1, 3) == 1) { // 33% chance
                VisibilityRule::firstOrCreate(
                    [
                        'entity_type' => QuestionVersion::class,
                        'entity_id' => $questionVersion->id,
                    ],
                    [
                        'description' => 'Show only for high-risk borrowers',
                        'source_type' => 'borrower_detail',
                        'source_field' => 'collectibility',
                        'operator' => '>=',
                        'value' => '3',
                    ]
                );
            }
        }
        
        // Create visibility rules for aspect versions
        foreach ($aspectVersions as $aspectVersion) {
            if (rand(1, 4) == 1) { // 25% chance
                VisibilityRule::firstOrCreate(
                    [
                        'entity_type' => AspectVersion::class,
                        'entity_id' => $aspectVersion->id,
                    ],
                    [
                        'description' => 'Show only for corporate borrowers',
                        'source_type' => 'borrower_detail',
                        'source_field' => 'borrower_business',
                        'operator' => '=',
                        'value' => 'Large Enterprise',
                    ]
                );
            }
        }
        
        // Create visibility rules for template versions
        foreach ($templateVersions as $templateVersion) {
            if (rand(1, 2) == 1) { // 50% chance
                VisibilityRule::firstOrCreate(
                    [
                        'entity_type' => TemplateVersion::class,
                        'entity_id' => $templateVersion->id,
                    ],
                    [
                        'description' => 'Show only for specific divisions',
                        'source_type' => 'borrower_detail', // Changed from 'borrower' to 'borrower_detail'
                        'source_field' => 'division_id',
                        'operator' => 'in',
                        'value' => '1,2,3',
                    ]
                );
            }
        }
        
        // Additional examples using other allowed source types
        if ($questionVersions->count() > 0) {
            VisibilityRule::firstOrCreate(
                [
                    'entity_type' => QuestionVersion::class,
                    'entity_id' => $questionVersions->first()->id,
                ],
                [
                    'description' => 'Show only for facilities with high outstanding',
                    'source_type' => 'borrower_facility',
                    'source_field' => 'outstanding',
                    'operator' => '>',
                    'value' => '1000000000', // 1 billion
                ]
            );
        }
        
        if ($aspectVersions->count() > 0) {
            VisibilityRule::firstOrCreate(
                [
                    'entity_type' => AspectVersion::class,
                    'entity_id' => $aspectVersions->first()->id,
                ],
                [
                    'description' => 'Show based on previous answer',
                    'source_type' => 'answer',
                    'source_field' => 'question_option_id',
                    'operator' => '=',
                    'value' => '1',
                ]
            );
        }
    }
}
