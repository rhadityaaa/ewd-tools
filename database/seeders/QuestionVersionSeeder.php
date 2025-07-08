<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\QuestionVersion;

class QuestionVersionSeeder extends Seeder
{
    public function run(): void
    {
        $questionIds = \App\Models\Question::pluck('id');
        $aspectVersionIds = \App\Models\AspectVersion::pluck('id');

        foreach ($questionIds as $qId) {
            foreach ($aspectVersionIds as $aId) {
                for ($i = 1; $i <= 10; $i++) {
                    QuestionVersion::create([
                        'question_id' => $qId,
                        'aspect_version_id' => $aId,
                        'version_number' => $i,
                        'question_text' => 'Pertanyaan ' . $i,
                        'weight' => 10,
                        'max_score' => 100,
                        'min_score' => 0,
                        'is_mandatory' => false,
                        'effective_from' => now(),
                    ]);
                }
            }
        }
    }
}
