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
    },

    getters: {
        /**
         * Menghitung jumlah total dari sebuah kolom numerik di semua fasilitas.
         * @param state - State Pinia.
         * @returns {function(keyof FacilityState): number} - Fungsi yang menerima nama kolom dan mengembalikan totalnya.
         */
        getTotalByKey: (state) => {
            return (key: keyof FacilityState): number => {
                return state.facilitiesBorrower.reduce((total, facility) => {
                    const value = facility[key];
                    return total + (typeof value === 'number' ? value : 0);
                }, 0);
            };
        },

        // Getters spesifik untuk kemudahan akses di komponen
        totalLimit(): number {
            return this.getTotalByKey('limit');
        },
        totalOutstanding(): number {
            return this.getTotalByKey('outstanding');
        },
        totalPrincipalArrears(): number {
            return this.getTotalByKey('principalArrears');
        },
        totalInterestArrears(): number {
            return this.getTotalByKey('interestArrears');
        },
        totalPdo(): number {
            return this.getTotalByKey('pdo');
        },

        /**
         * Menghitung jumlah fasilitas yang ada.
         */
        totalFacilities(): number {
            return this.facilitiesBorrower.length;
        },
    },
});
