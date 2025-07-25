import { defineStore } from 'pinia';

interface InformationState {
    borrowerId: number | null;
    borrowerGroup: string;
    purpose: 'both' | 'kie' | 'kmke';
    economicSector: string;
    businessField: string;
    borrowerBusiness: string;
    collectibility: number;
    restructuring: boolean;
}

interface FacilityState {
    id: number | null;
    name: string;
    limit: number;
    outstanding: number;
    interestRate: number;
    principalArrears: number;
    interestArrears: number;
    pdo: number;
    maturityDate: Date;
}

interface AspectState {
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
}

interface ReportMetaData {
    template_id: number | null;
    period_id: number | null;
    created_by: number | null;
}

interface FormState {
    activeStep: number;
    totalSteps: number;
    informationBorrower: InformationState;
    facilitiesBorrower: FacilityState[];
    aspectsBorrower: AspectState[];
    reportMeta: ReportMetaData;
    existingBorrowerId: number | null;
    existingBorrowerName: string;
}

const initialInformationBorrower = (): InformationState => ({
    borrowerId: null,
    borrowerGroup: '',
    purpose: 'kie',
    economicSector: '',
    businessField: '',
    borrowerBusiness: '',
    collectibility: 1,
    restructuring: false,
});

const initialFacilitiesBorrower = (): FacilityState => ({
    id: null,
    name: '',
    limit: 0,
    outstanding: 0,
    interestRate: 0,
    principalArrears: 0,
    interestArrears: 0,
    pdo: 0,
    maturityDate: new Date(),
});

const initialReportMeta = (): ReportMetaData => ({
    template_id: null,
    period_id: null,
    created_by: null,
});

export const useFormStore = defineStore('form', {
    state: (): FormState => ({
        activeStep: 1,
        totalSteps: 3,
        informationBorrower: initialInformationBorrower(),
        facilitiesBorrower: [initialFacilitiesBorrower()],
        aspectsBorrower: [],
        reportMeta: initialReportMeta(),
        existingBorrowerId: null,
        existingBorrowerName: '',
    }),
    actions: {
        nextStep() {
            if (this.activeStep < this.totalSteps) {
                this.activeStep++;
            }
        },
        prevStep() {
            if (this.activeStep > 1) {
                this.activeStep--;
            }
        },
        updateInformationBorrower(payload: InformationState) {
            this.informationBorrower = { ...this.informationBorrower, ...payload };
        },
        updateAspectsBorrower(aspects: AspectState[]) {
            this.aspectsBorrower = aspects;
        },
        updateAspectAnswer(questionId: number, selectedOptionId: number | null, notes: string | null) {
            const aspectIndex = this.aspectsBorrower.findIndex((aspect) => aspect.questionId === questionId);
            if (aspectIndex !== -1) {
                this.aspectsBorrower[aspectIndex].selectedOptionId = selectedOptionId;
                this.aspectsBorrower[aspectIndex].notes = notes;
            }
        },
        updateReportMeta(payload: Partial<ReportMetaData>) {
            this.reportMeta = { ...this.reportMeta, ...payload };
        },
        // Fungsi untuk menghitung total berdasarkan field tertentu untuk visibility rules
        getTotalByKey(field: string): number {
            if (!this.facilitiesBorrower || this.facilitiesBorrower.length === 0) {
                return 0;
            }

            // Handle prefixed fields untuk operasi khusus
            if (field.startsWith('total_') || field.startsWith('sum_')) {
                const actualField = field.startsWith('total_') ? field.substring(6) : field.substring(4);
                return this.facilitiesBorrower.reduce((total, facility) => {
                    const value = parseFloat(facility[actualField as keyof FacilityState] as string) || 0;
                    return total + value;
                }, 0);
            }

            if (field.startsWith('avg_')) {
                const actualField = field.substring(4);
                if (this.facilitiesBorrower.length === 0) return 0;
                const total = this.facilitiesBorrower.reduce((sum, facility) => {
                    const value = parseFloat(facility[actualField as keyof FacilityState] as string) || 0;
                    return sum + value;
                }, 0);
                return total / this.facilitiesBorrower.length;
            }

            if (field.startsWith('max_')) {
                const actualField = field.substring(4);
                return Math.max(...this.facilitiesBorrower.map(facility => {
                    return parseFloat(facility[actualField as keyof FacilityState] as string) || 0;
                }));
            }

            if (field.startsWith('min_')) {
                const actualField = field.substring(4);
                return Math.min(...this.facilitiesBorrower.map(facility => {
                    return parseFloat(facility[actualField as keyof FacilityState] as string) || 0;
                }));
            }

            if (field.startsWith('count_')) {
                const actualField = field.substring(6);
                return this.facilitiesBorrower.filter(facility => {
                    const value = facility[actualField as keyof FacilityState];
                    return value !== null && value !== undefined && value !== 0 && value !== '';
                }).length;
            }

            // Default: hitung total untuk field yang diminta
            return this.facilitiesBorrower.reduce((total, facility) => {
                const value = parseFloat(facility[field as keyof FacilityState] as string) || 0;
                return total + value;
            }, 0);
        },
    }
});
