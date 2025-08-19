import { BorrowerFacility } from "@/types";
import { FacilitiesTotals, SummaryProps } from "@/types/summary";
import { computed } from "vue";

export function useSummaryData(props: SummaryProps) {
    const borrowerInfo = computed(() => {
        const borrower = props.reportData?.borrower;
        const details = borrower?.details;

        return {
            name: borrower?.name || 'N/A',
            division: borrower?.division?.name || 'N/A',
            group: details?.borrower_group || 'N/A',
            purpose: details?.purpose || 'N/A',
            economicSector: details?.economic_sector || 'N/A',
            businessField: details?.business_field || 'N/A',
            borrowerBusiness: details?.borrower_business || 'N/A',
            collectibility: details?.collectibility || 1,
            restructuring: details?.restructuring || false 
        };
    });

    const borrowerFacilities = computed((): BorrowerFacility[] => {
        return props.reportData?.borrower?.facilities || [];
    });

    const facilitiesTotals = computed((): FacilitiesTotals => {
        const facilities = borrowerFacilities.value;

        const totals = facilities.reduce((acc, facility) => {
            const limit = Number(facility.limit) || 0;
            const outstanding = Number(facility.outstanding) || 0;
            const principalArrears = Number(facility.principal_arrears) || 0;
            const interestArrears = Number(facility.interest_arrears) || 0;
        
            return {
                total_limit: acc.total_limit + limit,
                total_outstanding: acc.total_outstanding + outstanding,
                total_principal_arrears: acc.total_principal_arrears + principalArrears,
                total_interest_arrears: acc.total_interest_arrears + interestArrears
            };
        }, {
            total_limit: 0,
            total_outstanding: 0,
            total_principal_arrears: 0,
            total_interest_arrears: 0
        });

        return {
            total_limit: totals.total_limit,
            total_outstanding: totals.total_outstanding,
            total_principal_arrears: totals.total_principal_arrears,
            total_interest_arrears: totals.total_interest_arrears
        }
    });

    const aspects = computed(() => {
        return props.summaryCalculation?.aspects || [];
    });

    const overallSummary = computed(() => {
        return props.summaryCalculation?.summary || null;
    });

    return {
        borrowerInfo,
        borrowerFacilities,
        facilitiesTotals,
        aspects,
        overallSummary
    };
}