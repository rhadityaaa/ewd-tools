import { Report, User } from ".";

export interface MonitoringNoteProps {
    report: Report;
    monitoringNote: MonitoringNote;
    actionItems: Record<string, ActionItem[]>;
    watchlist: Watchlist;
    isNawRequired: boolean;
}

export interface MonitoringNote {
    id: number;
    watchlist_id: number;
    watchlist_reason: string;
    account_strategy: string;
    created_by: User;
    updated_by: User;
    created_at: string;
    updated_at: string;
}

export interface ActionItem {
    id: number;
    monitoring_note_id: number;
    action_description: string;
    item_type: 'previous_period' | 'current_progress' | 'next_period';
    progress_notes: string;
    people_in_charge: string;
    notes: string;
    due_date: string;
    completion_date: string | null;
    status: 'pending' | 'in_progress' | 'completed' | 'overdue';
    created_at: string;
    updated_at: string;
}

export interface Watchlist {
    id: number;
    borrower_id: number;
    report_id: number;
    status: 'active' | 'resolved' | 'escalated';
    added_by: number;
    resolved_by?: number;
    resolved_at?: string;
    resolver_notes?: string;
    created_at: string;
    updated_at: string;
}

export interface ActionItemsByType {
    previous_period: ActionItem[];
    next_period: ActionItem[];
}

export interface MonitoringNoteFormData {
    watchlist_reason: string;
    account_strategy: string;
}

export interface ActionItemFormData {
    action_description: string;
    item_type: 'previous_period' | 'current_progress' | 'next_period';
    progress_notes: string;
    people_in_charge: string;
    notes: string;
    due_date: string;
    status: 'pending' | 'in_progress' | 'completed' | 'overdue';
}

export interface WatchlistFormData {
    status: 'active' | 'resolved' | 'escalated';
    resolver_notes: string;
}