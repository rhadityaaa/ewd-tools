<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\QuestionOption;
use App\Models\QuestionVersion;
use Carbon\Carbon;

class QuestionOptionSeeder extends Seeder
{
    public function run(): void
    {
        $optionTemplates = [
            [
                ['text' => 'Excellent', 'score' => 100],
                ['text' => 'Good', 'score' => 80],
                ['text' => 'Fair', 'score' => 60],
                ['text' => 'Poor', 'score' => 40],
                ['text' => 'Very Poor', 'score' => 20],
            ],
            [
                ['text' => 'Very High', 'score' => 100],
                ['text' => 'High', 'score' => 80],
                ['text' => 'Medium', 'score' => 60],
                ['text' => 'Low', 'score' => 40],
                ['text' => 'Very Low', 'score' => 20],
            ],
            [
                ['text' => 'Strongly Agree', 'score' => 100],
                ['text' => 'Agree', 'score' => 80],
                ['text' => 'Neutral', 'score' => 60],
                ['text' => 'Disagree', 'score' => 40],
                ['text' => 'Strongly Disagree', 'score' => 20],
            ],
            [
                ['text' => 'Above 20%', 'score' => 100],
                ['text' => '15-20%', 'score' => 80],
                ['text' => '10-15%', 'score' => 60],
                ['text' => '5-10%', 'score' => 40],
                ['text' => 'Below 5%', 'score' => 20],
            ],
        ];
        
        $questionVersions = QuestionVersion::all();
        
        foreach ($questionVersions as $questionVersion) {
            $template = $optionTemplates[array_rand($optionTemplates)];
            
            foreach ($template as $option) {
                QuestionOption::create([
                    'question_version_id' => $questionVersion->id,
                    'option_text' => $option['text'],
                    'score' => $option['score'],
                    'effective_from' => Carbon::now()->subMonths(rand(1, 6)),
                ]);
            }
        }
    }
}
