<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Question;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        // Seeder ini hanya membuat 10 Question kosong, sesuai struktur tabel
        for ($i = 1; $i <= 10; $i++) {
            Question::create([]);
        }
    }
}
