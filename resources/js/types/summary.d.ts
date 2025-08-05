import { Report } from ".";

export interface SummaryProps {
    reportId: number;
    reportData: Report;
    summaryCalculation: SummaryCalculation;
}

export interface SummaryCalculation {
    aspects: AspectScore[];
    summary: OverallSummary;
}

export interface AspectScore {
    aspect_code: string;
    aspect_name: string;
    classification: 'SAFE' | 'WATCHLIST';
    total_score: number;
    weight: number;
}

export interface OverallSummary {
    final_classification: 'SAFE' | 'WATCHLIST';
    total_final_score: number;
    collectibility: number;
}

export interface FacilitiesTotals {
    total_limit: number;
    total_outstanding: number;
    total_principal_arrears: number;
    total_interest_arrears: number;
}