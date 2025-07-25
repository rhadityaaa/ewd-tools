<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\Report;
use App\Models\ReportAspect;
use App\Models\ReportSummary;
use App\Models\AspectVersion;
use App\Models\QuestionVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SummaryCalculationService
{
    public function calculateAndStoreSummary(int $reportId): array
    {
        try {
            DB::beginTransaction();
            
            // Perbaiki eager loading untuk memuat questionOption
            $report = Report::with([
                'template.latestVersion', 
                'answers.questionVersion.aspectVersion.aspect',
                'answers.questionOption'
            ])->findOrFail($reportId);
            
            // Hitung scoring per aspek
            $aspectScores = $this->calculateAspectScores($report);
            
            // Hitung summary keseluruhan
            $overallSummary = $this->calculateOverallSummary($aspectScores, $report);
            
            // Simpan hasil ke database
            $this->storeCalculationResults($report, $aspectScores, $overallSummary);
            
            DB::commit();
            
            return [
                'aspects' => $aspectScores,
                'summary' => $overallSummary
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Summary calculation failed', [
                'report_id' => $reportId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    private function calculateAspectScores(Report $report): array
    {
        $aspectScores = [];
        
        // Group answers by aspect - perbaiki akses relasi
        $answersByAspect = $report->answers->groupBy(function($answer) {
            return $answer->questionVersion->aspectVersion->aspect->code;
        });
        
        foreach ($answersByAspect as $aspectCode => $answers) {
            $firstAnswer = $answers->first();
            $aspect = $firstAnswer->questionVersion->aspectVersion->aspect;
            
            // Hitung total score untuk aspek ini
            $totalScore = 0;
            $maxScore = 0;
            
            foreach ($answers as $answer) {
                $questionWeight = $answer->questionVersion->weight;
                $optionScore = $answer->questionOption->score ?? 0;
                $questionMaxScore = $answer->questionVersion->max_score; // Perbaiki: hapus komentar
                
                $totalScore += ($optionScore * $questionWeight / 100);
                $maxScore += ($questionMaxScore * $questionWeight / 100);
            }
            
            // Hitung persentase
            $percentage = $maxScore > 0 ? round(($totalScore / $maxScore) * 100, 2) : 0;
            
            // Dapatkan bobot aspek dari template (sudah dalam format persentase)
            $aspectWeight = $this->getAspectWeightFromTemplate($report, $firstAnswer->questionVersion->aspectVersion->id);
            
            // Hitung weighted score - bobot sudah dalam persentase
            $weightedScore = round(($percentage * $aspectWeight) / 100, 2);
            
            $classification = $this->determineClassification($percentage);
            
            $aspectScores[] = [
                'aspect_code' => $aspectCode,
                'aspect_name' => $aspect->name,
                'total_score' => $totalScore,
                'max_score' => $maxScore,
                'percentage' => $percentage,
                'weight' => $aspectWeight,
                'weighted_score' => $weightedScore,
                'classification' => $classification,
                'aspect_version_id' => $firstAnswer->questionVersion->aspectVersion->id
            ];
        }
        
        return $aspectScores;
    }
    
    private function calculateOverallSummary(array $aspectScores, Report $report): array
    {
        $totalWeightedScore = array_sum(array_column($aspectScores, 'weighted_score'));
        $totalWeight = array_sum(array_column($aspectScores, 'weight'));
        
        // Overall percentage adalah total weighted score (karena total bobot = 100%)
        $overallPercentage = round($totalWeightedScore, 2);
        
        $finalClassification = $this->determineClassification($overallPercentage);
        $riskLevel = $this->determineRiskLevel($overallPercentage);
        $collectibility = $this->determineCollectibility($overallPercentage);
        
        return [
            'total_weighted_score' => $totalWeightedScore,
            'max_weighted_score' => $totalWeight,
            'overall_percentage' => $overallPercentage,
            'final_classification' => $finalClassification,
            'risk_level' => $riskLevel,
            'collectibility' => $collectibility
        ];
    }
    
    private function getAspectWeightFromTemplate(Report $report, int $aspectVersionId): float
    {
        // Ambil bobot aspek dari template version
        $templateVersion = $report->template->latestVersion;
        $aspectVersion = $templateVersion->aspectVersions->where('id', $aspectVersionId)->first();
        
        return $aspectVersion ? $aspectVersion->pivot->weight : 0;
    }
    
    private function determineClassification(float $percentage): string
    {
        if ($percentage >= 80) {
            return 'SAFE';
        } else {
            return 'WARNING';
        } 
    }
    
    private function determineCollectibility(float $percentage): int
    {
        if ($percentage >= 90) return 0; // Current
        elseif ($percentage >= 80) return 1; // Special Mention
        elseif ($percentage >= 60) return 2; // Substandard
        elseif ($percentage >= 40) return 3; // Doubtful
        else return 4; // Loss
    }
    
    private function determineRiskLevel(float $percentage): string
    {
        if ($percentage >= 80) {
            return 'low';
        } elseif ($percentage >= 60) {
            return 'medium';
        } else {
            return 'high';
        }
    }
    
    private function storeCalculationResults(Report $report, array $aspectScores, array $overallSummary): void
    {
        // Simpan atau update report aspects
        foreach ($aspectScores as $aspectScore) {
            ReportAspect::updateOrCreate(
                [
                    'report_id' => $report->id,
                    'aspect_version_id' => $aspectScore['aspect_version_id']
                ],
                [
                    'total_score' => $aspectScore['total_score'],
                    'classification' => strtolower($aspectScore['classification'])
                ]
            );
        }
        
        // Simpan atau update report summary
        ReportSummary::updateOrCreate(
            ['report_id' => $report->id],
            [
                'final_classification' => strtolower($overallSummary['final_classification']),
                'indicative_collectibility' => $overallSummary['collectibility'],
                'override' => false,
                'override_reason' => '',
                'business_notes' => '',
                'reviewer_notes' => ''
            ]
        );
    }
}