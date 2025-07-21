<?php

namespace App\Services;

use App\Models\Report;
use App\Models\ReportAspect;
use App\Models\ReportSummary;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SummaryCalculationService
{
    /**
     * Menghitung dan menyimpan summary laporan
     */
    public function calculateAndStoreSummary(int $reportId): array
    {
        try {
            $report = Report::with([
                'template.aspectVersions.questionVersions.questionOptions',
                'template.aspectVersions.aspect'
            ])->findOrFail($reportId);
        
        // Ambil jawaban aspek dari database
        // Gunakan Eloquent relationships instead of raw joins
        $reportAspects = ReportAspect::with([
            'questionVersion.aspectVersion.aspect',
            'selectedOption'
        ])
        ->where('report_id', $reportId)
        ->get()
        ->groupBy('questionVersion.aspectVersion.aspect.id');
        
        $aspectResults = [];
        $totalWeightedScore = 0;
        $totalMaxWeightedScore = 0;
        
        // Hitung skor untuk setiap aspek
        foreach ($reportAspects as $aspectId => $questions) {
            $aspectData = $questions->first();
            
            // Hitung total skor aspek (skor pertanyaan × bobot pertanyaan)
            $totalAspectScore = 0;
            $maxAspectScore = 0;
            
            foreach ($questions as $question) {
                $questionScore = $question->selected_score * $question->question_weight;
                $maxQuestionScore = $question->max_score * $question->question_weight;
                
                $totalAspectScore += $questionScore;
                $maxAspectScore += $maxQuestionScore;
            }
        
            // Hitung persentase aspek
            $aspectPercentage = $maxAspectScore > 0 ? ($totalAspectScore / $maxAspectScore) * 100 : 0;
            
            // Tentukan klasifikasi berdasarkan persentase
            $classification = $this->determineClassification($aspectPercentage);
            
            // Ambil bobot aspek dari template
            $aspectWeight = $this->getAspectWeightFromTemplate($report->template, $aspectId);
            
            // Hitung skor berbobot untuk summary keseluruhan
            $weightedScore = ($aspectPercentage / 100) * $aspectWeight;
            $totalWeightedScore += $weightedScore;
            $totalMaxWeightedScore += $aspectWeight;
        
            $aspectResults[] = [
                'aspect_id' => $aspectId,
                'aspect_code' => $aspectData->aspect_code,
                'aspect_name' => $aspectData->aspect_name,
                'total_score' => $totalAspectScore,
                'max_score' => $maxAspectScore,
                'percentage' => round($aspectPercentage, 2),
                'classification' => $classification,
                'weight' => $aspectWeight,
                'weighted_score' => round($weightedScore, 2)
            ];
        
            // Simpan atau update ReportAspect dengan klasifikasi
            // Dalam method calculateAndStoreSummary, ganti bagian yang menyimpan ReportAspect:
            
            // Simpan ReportAspect dengan total score per aspek
            ReportAspect::updateOrCreate(
                [
                    'report_id' => $reportId,
                    'aspect_version_id' => $aspectId
                ],
                [
                    'total_score' => $totalAspectScore,
                    'classification' => $classification
                ]
            );
        }
    
        // Hitung summary keseluruhan
        $overallPercentage = $totalMaxWeightedScore > 0 ? ($totalWeightedScore / $totalMaxWeightedScore) * 100 : 0;
        $riskLevel = $this->determineRiskLevel($overallPercentage);
        $finalClassification = $this->determineFinalClassification($aspectResults);
    
        // Simpan summary
        $summary = ReportSummary::updateOrCreate(
            ['report_id' => $reportId],
            [
                'final_classification' => $finalClassification,
                'indicative_collectibility' => $this->determineCollectibility($overallPercentage),
                'is_override' => false,
                'summary_generated_at' => now()
            ]
        );
    
        return [
            'report_id' => $reportId,
            'aspects' => $aspectResults,
            'summary' => [
                'total_weighted_score' => round($totalWeightedScore, 2),
                'max_weighted_score' => round($totalMaxWeightedScore, 2),
                'overall_percentage' => round($overallPercentage, 2),
                'risk_level' => $riskLevel,
                'final_classification' => $finalClassification,
                'collectibility' => $this->determineCollectibility($overallPercentage)
            ]
        ];
    } catch (\Exception $e) {
        Log::error('Summary calculation failed', [
            'report_id' => $reportId,
            'error' => $e->getMessage()
        ]);
        throw $e;
    }
}

    /**
     * Menentukan klasifikasi berdasarkan persentase skor
     */
    private function determineClassification(float $percentage): string
    {
        if ($percentage >= 80) {
            return 'SAFE';
        } elseif ($percentage >= 60) {
            return 'WARNING';
        } else {
            return 'CRITICAL';
        }
    }

    /**
     * Menentukan risk level berdasarkan persentase keseluruhan
     */
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

    /**
     * Menentukan klasifikasi final berdasarkan aspek-aspek
     */
    private function determineFinalClassification(array $aspects): string
    {
        $criticalCount = count(array_filter($aspects, fn($a) => $a['classification'] === 'CRITICAL'));
        $warningCount = count(array_filter($aspects, fn($a) => $a['classification'] === 'WARNING'));
        
        if ($criticalCount > 0) {
            return 'CRITICAL';
        } elseif ($warningCount > 0) {
            return 'WARNING';
        } else {
            return 'SAFE';
        }
    }

    /**
     * Menentukan kolektibilitas berdasarkan persentase
     */
    private function determineCollectibility(float $percentage): int
    {
        if ($percentage >= 90) return 0; // Current
        if ($percentage >= 80) return 1; // Special Mention
        if ($percentage >= 60) return 2; // Substandard
        if ($percentage >= 40) return 3; // Doubtful
        return 4; // Loss
    }

    /**
     * Mengambil bobot aspek dari template
     */
    private function getAspectWeightFromTemplate($template, int $aspectId): float
    {
        $aspectVersion = $template->aspectVersions->where('aspect_id', $aspectId)->first();
        return $aspectVersion ? $aspectVersion->pivot->weight : 0;
    }
}