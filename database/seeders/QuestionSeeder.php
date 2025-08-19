<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Question;
use App\Models\Aspect;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $aspects = Aspect::all();
        
        foreach ($aspects as $aspect) {
            // Create 5-10 questions per aspect
            for ($i = 1; $i <= rand(5, 10); $i++) {
                Question::create([
                    // No fields needed - questions table only has id and timestamps
                ]);
            }
        }
    }
}
