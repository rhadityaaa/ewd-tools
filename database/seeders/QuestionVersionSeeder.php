<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\QuestionVersion;
use App\Models\Question;
use App\Models\AspectVersion;
use Carbon\Carbon;

class QuestionVersionSeeder extends Seeder
{
    public function run(): void
    {
        $questionTemplates = [
            'Financial Performance' => [
                'What is the current ratio trend over the last 3 years?',
                'How stable is the revenue growth?',
                'What is the debt-to-equity ratio?',
                'How is the cash flow from operations?',
                'What is the return on assets (ROA)?',
            ],
            'Management Quality' => [
                'How experienced is the management team?',
                'What is the management succession plan?',
                'How effective is the decision-making process?',
                'What is the track record of management?',
                'How transparent is management communication?',
            ],
            'Market Position' => [
                'What is the market share in the industry?',
                'How competitive is the market position?',
                'What is the customer concentration risk?',
                'How diversified is the customer base?',
                'What is the brand recognition level?',
            ],
            'Operational Efficiency' => [
                'What is the operational margin trend?',
                'How efficient is the production process?',
                'What is the asset turnover ratio?',
                'How effective is cost management?',
                'What is the capacity utilization rate?',
            ],
            'Liquidity Position' => [
                'What is the current liquidity ratio?',
                'How adequate are the cash reserves?',
                'What is the working capital management?',
                'How accessible are credit facilities?',
                'What is the cash conversion cycle?',
            ],
        ];
        
        $questions = Question::all();
        $aspectVersions = AspectVersion::with('aspect')->get();
        
        $questionIndex = 0;
        
        foreach ($aspectVersions as $aspectVersion) {
            $aspectName = $aspectVersion->name ?? 'General';
            
            // Find matching templates based on aspect name
            $templates = [];
            foreach ($questionTemplates as $key => $questionList) {
                if (str_contains($aspectName, $key) || str_contains($key, 'Financial')) {
                    $templates = $questionList;
                    break;
                }
            }
            
            if (empty($templates)) {
                $templates = $questionTemplates['Financial Performance'];
            }
            
            // Create 3-5 question versions for this aspect version
            $questionCount = rand(3, 5);
            
            for ($i = 0; $i < $questionCount && $questionIndex < count($questions); $i++) {
                $question = $questions[$questionIndex];
                
                QuestionVersion::firstOrCreate(
                    [
                        'question_id' => $question->id,
                        'aspect_version_id' => $aspectVersion->id,
                        'version_number' => 1,
                    ],
                    [
                        'question_text' => $templates[array_rand($templates)],
                        'weight' => rand(5, 20) / 10, // 0.5 to 2.0
                        'max_score' => rand(80, 100),
                        'min_score' => rand(0, 20),
                        'is_mandatory' => rand(0, 1) == 1,
                        'effective_from' => Carbon::now()->subMonths(rand(1, 6)),
                    ]
                );
                
                $questionIndex++;
            }
        }
    }
}
