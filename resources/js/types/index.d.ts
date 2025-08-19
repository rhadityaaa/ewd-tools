import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Paginated<T> {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number;
        last_page: number;
        path: string;
        per_page: number;
        to: number;
        total: number;
    }
}

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    email: string;
    division_id: number;
    division: Division;
    role_id: number;
    role: Role;
    created_at: string;
    updated_at: string;
}

export interface Role {
    id: number;
    name: string;
}

export interface Division {
    id: number;
    code: string;
    name: string;
    created_at: string;
    updated_at: string;
}

export interface Period {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    status: 'draft' | 'active' | 'ended' | 'expired';
    created_by: number;
    created_by_user?: User;
    created_at: string;
    updated_at: string;
    is_active: boolean;
    is_expired: boolean;
    remaining_time: string;
}

export interface Borrower {
    id: number;
    name: string;
    division_id: number;
    division: Division;
    details: BorrowerDetail;
    facilities: BorrowerFacility[];
}

export interface BorrowerDetail {
    id: number;
    borrower_id: number;
    borrower: Borrower;
    borrower_group?: string;
    purpose: string;
    economic_sector: string;
    business_field: string;
    borrower_business: string;
    collectibility: number;
    restructuring: boolean;
    created_at?: string;
    updated_at?: string;
}

export interface BorrowerFacility {
    id: number;
    borrower_id: number;
    facility_name: string;
    limit: number;
    outstanding: number;
    interest_rate: number;
    principal_arrears: number;
    interest_arrears: number;
    pdo_days: number;
    maturity_date: string;
    created_at?: string;
    updated_at?: string;
}

// Template and related interfaces
export interface Template {
    id: number;
    name: string;
    latest_version?: TemplateVersion;
    created_at?: string;
    updated_at?: string;
}

export interface TemplateVersion {
    id: number;
    template_id: number;
    version_number: number;
    description?: string;
    effective_from: string;
    template?: Template;
    aspect_versions?: AspectVersion[];
    visibility_rules?: VisibilityRule[];
    created_at?: string;
    updated_at?: string;
}

// Aspect and related interfaces
export interface Aspect {
    id: number;
    code: string;
    latest_aspect_version?: AspectVersion;
    aspect_versions?: AspectVersion[];
    created_at?: string;
    updated_at?: string;
}

export interface AspectVersion {
    id: number;
    aspect_id: number;
    version_number: number;
    name: string;
    description?: string;
    effective_from: string;
    aspect?: Aspect;
    question_versions?: QuestionVersion[];
    visibility_rules?: VisibilityRule[];
    pivot?: {
        weight: number;
        created_at?: string;
        updated_at?: string;
    };
    created_at?: string;
    updated_at?: string;
}

// Question and related interfaces
export interface Question {
    id: number;
    aspect_id?: number;
    question_versions?: QuestionVersion[];
    created_at?: string;
    updated_at?: string;
}

export interface QuestionVersion {
    id: number;
    question_id: number;
    aspect_version_id: number;
    version_number: number;
    question_text: string;
    weight: number;
    max_score: number;
    min_score: number;
    is_mandatory: boolean;
    effective_from: string;
    question?: Question;
    aspect_version?: AspectVersion;
    question_options?: QuestionOption[];
    visibility_rules?: VisibilityRule[];
    created_at?: string;
    updated_at?: string;
}

export interface QuestionOption {
    id: number;
    question_version_id: number;
    option_text: string;
    score: number;
    effective_from: string;
    question_version?: QuestionVersion;
    created_at?: string;
    updated_at?: string;
}

// Visibility Rule interface
export interface VisibilityRule {
    id: number;
    entity_type: string;
    entity_id: number;
    description?: string;
    source_type: 'borrower_detail' | 'borrower_facility' | 'answer';
    source_field: string;
    operator: '=' | '!=' | '>' | '<' | '>=' | '<=' | 'in' | 'not_in' | 'contains' | 'not contains';
    value: string;
    created_at?: string;
    updated_at?: string;
}

// Form-related interfaces
export interface InformationBorrower {
    borrowerId: number | null;
    borrowerGroup: string;
    purpose: 'both' | 'kie' | 'kmke';
    economicSector: string;
    businessField: string;
    borrowerBusiness: string;
    collectibility: number;
    restructuring: boolean;
}

export interface FacilityData {
    id: number | null;
    name: string;
    limit: number;
    outstanding: number;
    interestRate: number;
    principalArrears: number;
    interestArrears: number;
    pdo: number;
    maturityDate: Date | string;
}

export interface AspectAnswer {
    questionId: number;
    questionText: string;
    aspectName: string;
    aspectCode: string;
    options: {
        id: number;
        option_text: string;
        score: number;
    }[];
    selectedOptionId: number | null;
    notes: string | null;
    isMandatory: boolean;
    maxScore: number;
    minScore: number;
    weight: number;
    visibility_rules?: VisibilityRule[];
}

export interface ReportMeta {
    template_id: number | null;
    period_id: number | null;
    created_by: number | null;
}

// Form submission interface
export interface FormSubmissionData {
    informationBorrower: InformationBorrower;
    facilitiesBorrower: FacilityData[];
    aspectsBorrower: AspectAnswer[];
    reportMeta: ReportMeta;
}

// API Response interface
export interface ApiResponse<T = any> {
    data: T;
    message?: string;
    errors?: Record<string, string[]>;
}

// Aspect Group interface for form rendering
export interface AspectGroup {
    id: string;
    name: string;
    description?: string;
    weight?: number;
    aspects: {
        id: string;
        question: string;
        value: any;
        notes: string;
        question_version_id: number;
        is_mandatory: boolean;
        weight: number;
        max_score: number;
        min_score: number;
        options: QuestionOption[];
        visibility_rules: VisibilityRule[];
    }[];
    template_visibility_rules?: VisibilityRule[];
}

// Report interfaces
export interface Report {
    id: number;
    borrower_id: number;
    template_id: number;
    period_id: number;
    borrower?: Borrower;
    template?: Template;
    period?: Period;
    summary?: ReportSummary;
    status: string;
    creator?: User;  // Add this line
    created_at?: string;
    updated_at?: string;
}

export interface ReportAspect {
    id: number;
    report_id: number;
    aspect_version_id: number;
    total_score: number;
    classification: 'safe' | 'watchlist';
    report?: Report;
    aspect_version?: AspectVersion;
    created_at?: string;
    updated_at?: string;
}

export interface Answer {
    id: number;
    report_id: number;
    question_version_id: number;
    question_option_id: number;
    notes?: string;
    report?: Report;
    question_version?: QuestionVersion;
    question_option?: QuestionOption;
    created_at?: string;
    updated_at?: string;
}

export interface ReportSummary {
    id: number;
    report_id: number;
    final_classification: string;
    indicative_collectibility: number;
    is_override: boolean;
    override_reason: string;
    business_notes: string;
    reviewer_notes: string;
}

// Legacy type alias
export type BreadcrumbItemType = BreadcrumbItem;