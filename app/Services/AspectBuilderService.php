<?php

namespace App\Services;

use App\Models\Aspect;
use App\Models\AspectVersion;
use App\Models\Question;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AspectBuilderService
{
    public function getAllAspects(): Collection
    {
        $aspects = Aspect::with('latestAspectVersion')->latest()->get();

        return $aspects;
    }

    public function createAspectWithQuestions(array $data): Aspect
    {
        try {
            return DB::transaction(function () use ($data) {
            $aspect = Aspect::create(['code' => $data['code']]);

            $aspectVersion = $aspect->aspectVersions()->create([
                'version_number' => 1,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'effective_from' => now(),
            ]);

            if (!empty($data['questions'])) {
                $this->createQuestion($aspectVersion, $data['questions']);
            }

            return $aspect;
        });
        } catch (Exception $e) {
            Log::error('Failed to create aspect with questions', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to create aspect: ' . $e->getMessage());
        }
    }

    public function updateAspectWithQuestions(Aspect $aspect, array $data): Aspect
    {
        return DB::transaction(function () use ($aspect, $data) {
            $aspect->update(['code' => $data['code']]);

            $aspectVersion = $aspect->latestAspectVersion()->create([
                'version_number' => $aspect->aspectVersions()->count() + 1,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'effective_from' => now(),
            ]);

            if (!empty($data['questions'])) {
                $this->createQuestion($aspectVersion, $data['questions']);
            }

            return $aspect;
        });
    }

    private function createQuestion(AspectVersion $aspectVersion, array $questionData): void
    {
        foreach ($questionData as $data) {
            $question = Question::create([]);

            $questionVersion = $question->questionVersions()->create([
                'aspect_version_id' => $aspectVersion->id,
                'version_number' => 1,
                'question_text' => $data['question_text'],
                'weight' => $data['weight'],
                'max_score' => $data['max_score'],
                'min_score' => $data['min_score'],
                'is_mandatory' => $data['is_mandatory'],
                'effective_from' => now(),
            ]);
            
            if (!empty($data['options'])) {
                foreach ($data['options'] as $option) {
                    $questionVersion->questionOptions()->create([
                        'option_text' => $option['option_text'],
                        'score' => $option['score'],
                        'effective_from' => now(),
                    ]);
                }
            }

            if (!empty($data['visibility_rules'])) {
                $questionVersion->visibilityRules()->createMany($data['visibility_rules']);
            }
        }
    }
}