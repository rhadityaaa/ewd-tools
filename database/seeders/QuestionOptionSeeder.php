<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\QuestionOption;

class QuestionOptionSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil semua QuestionVersion yang ada
        $questionVersionIds = \App\Models\QuestionVersion::pluck('id');
        foreach ($questionVersionIds as $id) {
            for ($i = 1; $i <= 4; $i++) {
                \App\Models\QuestionOption::create([
                    'question_version_id' => $id,
                    'option_text' => 'Opsi ' . $i . ' untuk QuestionVersion ' . $id,
                    'score' => $i * 10,
                    'effective_from' => now(),
                ]);
            }
        }
    }
}
