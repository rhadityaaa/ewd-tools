<script setup lang="ts">
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useToast } from 'vue-toastification';
import { Building2, CreditCard, Calendar, Percent, DollarSign, User, MapPin, Briefcase, TrendingUp, AlertTriangle, CheckCircle, Info } from 'lucide-vue-next';

const toast = useToast();

const props = defineProps({
    reportId: {
        type: Number,
        required: true,
    },
    reportData: {
        type: Object,
        required: true,
    },
    summaryCalculation: {
        type: Object,
        required: true,
    }
});

const form = useForm({
    borrower: props.reportData.borrower?.name || 'N/A',
    period: props.reportData.period?.name || 'N/A',
    collectibility: props.reportData.summary?.indicative_collectibility || 1,
    override: props.reportData.summary?.is_override || false,
    overrideReason: props.reportData.summary?.override_reason || '',
    businessNotes: props.reportData.summary?.business_notes || '',
    reviewerNotes: props.reportData.summary?.reviewer_notes || '',
});

const isLoading = ref(false);
const expandedSections: any = ref({
    borrowerInfo: true,
    facilities: true,
    aspects: true,
    summary: true
});

// Computed untuk informasi debitur
const borrowerInfo = computed(() => {
    const borrower = props.reportData.borrower;
    const details = borrower?.details;
    return {
        name: borrower?.name || 'N/A',
        division: borrower?.division?.name || 'N/A',
        group: details?.borrower_group || 'N/A',
        purpose: details?.purpose || 'N/A',
        economicSector: details?.economic_sector || 'N/A',
        businessField: details?.business_field || 'N/A',
        borrowerBusiness: details?.borrower_business || 'N/A',
        collectibility: details?.collectibility || 'N/A',
        restructuring: details?.restructuring ? 'Ya' : 'Tidak'
    };
});

// Computed untuk fasilitas debitur
const borrowerFacilities = computed(() => {
    return props.reportData.borrower?.facilities || [];
});

// Computed untuk total fasilitas
const facilitiesTotals = computed(() => {
    const facilities = borrowerFacilities.value;
    return {
        totalLimit: facilities.reduce((sum: any, f: any) => sum + (parseFloat(f.limit) || 0), 0),
        totalOutstanding: facilities.reduce((sum: any, f: any) => sum + (parseFloat(f.outstanding) || 0), 0),
        totalPrincipalArrears: facilities.reduce((sum: any, f: any) => sum + (parseFloat(f.principal_arrears) || 0), 0),
        totalInterestArrears: facilities.reduce((sum: any, f: any) => sum + (parseFloat(f.interest_arrears) || 0), 0),
        utilizationRatio: 0
    };
});

// Computed untuk utilization ratio
const utilizationRatio = computed(() => {
    const totals = facilitiesTotals.value;
    return totals.totalLimit > 0 ? (totals.totalOutstanding / totals.totalLimit * 100) : 0;
});

const aspects = computed(() => {
    return props.summaryCalculation.aspects.map((aspect: any) => ({
        id: aspect.aspect_code,
        name: aspect.aspect_name,
        classification: aspect.classification,
        totalScore: aspect.total_score,
        maxScore: aspect.max_score,
        percentage: aspect.percentage,
        weight: aspect.weight,
        weightedScore: aspect.weighted_score
    }));
});

const overallSummary = computed(() => props.summaryCalculation.summary);

const getClassificationColor = (classification: any) => {
    switch (classification) {
        case 'SAFE': return 'text-green-600';
        case 'WATCHLIST': return 'text-red-600';
        default: return 'text-gray-600';
    };
};

const getClassificationBg = (classification: any) => {
    switch (classification) {
        case 'SAFE': return 'bg-green-50 border-green-200';
        case 'WATCHLIST': return 'bg-red-50 border-red-200';
        default: return 'bg-gray-50 border-gray-200';
    }
}

const getClassificationIcon = (classification: any) => {
    switch (classification) {
        case 'SAFE': return CheckCircle;
        case 'WATCHLIST': return AlertTriangle;
        default: return Info;
    }
}

// Format currency
const formatCurrency = (amount: any) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
};

// Format compact currency
const formatCompactCurrency = (amount: any) => {
    if (amount >= 1e9) {
        return `Rp ${(amount / 1e9).toFixed(1)}M`;
    } else if (amount >= 1e6) {
        return `Rp ${(amount / 1e6).toFixed(1)}Jt`;
    } else if (amount >= 1e3) {
        return `Rp ${(amount / 1e3).toFixed(0)}Rb`;
    }
    return formatCurrency(amount);
};

// Format date
const formatDate = (date: any) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

// Get collectibility label and color
const getCollectibilityInfo = (value: any) => {
    const info: any = {
        1: { label: '1 - Lancar', color: 'text-green-600', bg: 'bg-green-100' },
        2: { label: '2 - DPK', color: 'text-yellow-600', bg: 'bg-yellow-100' },
        3: { label: '3 - Kurang Lancar', color: 'text-orange-600', bg: 'bg-orange-100' },
        4: { label: '4 - Diragukan', color: 'text-red-600', bg: 'bg-red-100' },
        5: { label: '5 - Macet', color: 'text-red-700', bg: 'bg-red-200' }
    };
    return info[value] || { label: 'N/A', color: 'text-gray-600', bg: 'bg-gray-100' };
};

// Toggle section expansion
const toggleSection = (section: any) => {
    expandedSections.value[section] = !expandedSections.value[section];
};

// Get risk level color
const getRiskLevelColor = (ratio: any) => {
    if (ratio >= 90) return 'text-red-600';
    if (ratio >= 75) return 'text-orange-600';
    if (ratio >= 50) return 'text-yellow-600';
    return 'text-green-600';
};

</script>

<template>
    <div class="min-h-screen">
        <!-- Header -->
        <div class="bg-[#2e3192] p-6 text-white shadow-lg">
            <div class="max-w-7xl mx-auto">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold mb-2">Summary Early Warning</h1>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-blue-100">Report ID</div>
                        <div class="text-xl font-mono font-bold">#{{ reportId }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-7xl px-4 py-8 space-y-8">
            <!-- Quick Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Limit -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Limit</p>
                            <p class="text-2xl font-bold text-blue-600">{{ formatCompactCurrency(facilitiesTotals.totalLimit) }}</p>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-full">
                            <CreditCard class="w-6 h-6 text-blue-600" />
                        </div>
                    </div>
                </div>

                <!-- Total Outstanding -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Outstanding</p>
                            <p class="text-2xl font-bold text-green-600">{{ formatCompactCurrency(facilitiesTotals.totalOutstanding) }}</p>
                        </div>
                        <div class="p-3 bg-green-100 rounded-full">
                            <TrendingUp class="w-6 h-6 text-green-600" />
                        </div>
                    </div>
                </div>

                <!-- Utilization Ratio -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Utilisasi</p>
                            <p class="text-2xl font-bold" :class="getRiskLevelColor(utilizationRatio)">{{ utilizationRatio.toFixed(1) }}%</p>
                        </div>
                        <div class="p-3 bg-purple-100 rounded-full">
                            <Percent class="w-6 h-6 text-purple-600" />
                        </div>
                    </div>
                </div>

                <!-- Classification -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Klasifikasi</p>
                            <p class="text-xl font-bold" :class="getClassificationColor(overallSummary.final_classification)">
                                {{ overallSummary.final_classification }}
                            </p>
                            <!-- Tambahkan tombol NAW jika klasifikasi WATCHLIST -->
                            <div v-if="overallSummary.final_classification === 'WATCHLIST'" class="mt-3">
                                <a :href="route('naw.show', { report_id: reportId })" 
                                   class="inline-flex items-center px-3 py-2 text-xs font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                                    <AlertTriangle class="w-4 h-4 mr-1" />
                                    Buka NAW
                                </a>
                            </div>
                        </div>
                        <div class="p-3 rounded-full" :class="getClassificationBg(overallSummary.final_classification)">
                            <component :is="getClassificationIcon(overallSummary.final_classification)" 
                                class="w-6 h-6" :class="getClassificationColor(overallSummary.final_classification)" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informasi Debitur -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-200">
                    <button @click="toggleSection('borrowerInfo')" 
                        class="w-full flex items-center justify-between text-left hover:bg-blue-100 rounded-lg p-2 -m-2 transition-colors">
                        <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                            <User class="w-6 h-6 mr-3 text-blue-600" />
                            Informasi Debitur
                        </h2>
                        <div class="transform transition-transform" :class="{ 'rotate-180': !expandedSections.borrowerInfo }">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </button>
                </div>
                
                <div v-show="expandedSections.borrowerInfo" class="p-6">
                    <!-- Informasi Utama -->
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3 mb-8">
                        <div class="space-y-3">
                            <Label class="text-sm font-semibold text-gray-700 flex items-center">
                                <Building2 class="w-4 h-4 mr-2 text-blue-500" />
                                Nama Debitur
                            </Label>
                            <div class="bg-gradient-to-r from-blue-50 to-blue-100 border-2 border-blue-200 rounded-xl p-4 shadow-sm">
                                <div class="text-xl font-bold text-gray-800">{{ borrowerInfo.name }}</div>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <Label class="text-sm font-semibold text-gray-700 flex items-center">
                                <Calendar class="w-4 h-4 mr-2 text-green-500" />
                                Periode
                            </Label>
                            <div class="bg-gradient-to-r from-green-50 to-green-100 border-2 border-green-200 rounded-xl p-4 shadow-sm">
                                <div class="text-xl font-bold text-gray-800">{{ form.period }}</div>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <Label class="text-sm font-semibold text-gray-700 flex items-center">
                                <MapPin class="w-4 h-4 mr-2 text-purple-500" />
                                Divisi
                            </Label>
                            <div class="bg-gradient-to-r from-purple-50 to-purple-100 border-2 border-purple-200 rounded-xl p-4 shadow-sm">
                                <div class="text-xl font-bold text-gray-800">{{ borrowerInfo.division }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Detail Informasi dalam Cards -->
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 hover:bg-gray-100 transition-colors">
                            <Label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Grup Debitur</Label>
                            <div class="text-lg font-medium text-gray-800 mt-1">{{ borrowerInfo.group }}</div>
                        </div>
                        
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 hover:bg-gray-100 transition-colors">
                            <Label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Tujuan</Label>
                            <div class="text-lg font-medium text-gray-800 mt-1">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                    {{ borrowerInfo.purpose }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 hover:bg-gray-100 transition-colors">
                            <Label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Sektor Ekonomi</Label>
                            <div class="text-lg font-medium text-gray-800 mt-1">{{ borrowerInfo.economicSector }}</div>
                        </div>
                        
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 hover:bg-gray-100 transition-colors">
                            <Label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Bidang Usaha</Label>
                            <div class="text-lg font-medium text-gray-800 mt-1">{{ borrowerInfo.businessField }}</div>
                        </div>
                        
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 hover:bg-gray-100 transition-colors">
                            <Label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Usaha Debitur</Label>
                            <div class="text-lg font-medium text-gray-800 mt-1">{{ borrowerInfo.borrowerBusiness }}</div>
                        </div>
                        
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 hover:bg-gray-100 transition-colors">
                            <Label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Kolektibilitas</Label>
                            <div class="mt-1">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold border" 
                                    :class="[getCollectibilityInfo(borrowerInfo.collectibility).color, getCollectibilityInfo(borrowerInfo.collectibility).bg]">
                                    {{ getCollectibilityInfo(borrowerInfo.collectibility).label }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 hover:bg-gray-100 transition-colors">
                            <Label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Restrukturisasi</Label>
                            <div class="mt-1">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold" 
                                    :class="borrowerInfo.restructuring === 'Ya' ? 'bg-red-100 text-red-800 border border-red-200' : 'bg-green-100 text-green-800 border border-green-200'">
                                    <component :is="borrowerInfo.restructuring === 'Ya' ? AlertTriangle : CheckCircle" class="w-4 h-4 mr-1" />
                                    {{ borrowerInfo.restructuring }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fasilitas Debitur -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden" v-if="borrowerFacilities.length > 0">
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-4 border-b border-gray-200">
                    <button @click="toggleSection('facilities')" 
                        class="w-full flex items-center justify-between text-left hover:bg-green-100 rounded-lg p-2 -m-2 transition-colors">
                        <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                            <CreditCard class="w-6 h-6 mr-3 text-green-600" />
                            Fasilitas Debitur
                            <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {{ borrowerFacilities.length }} Fasilitas
                            </span>
                        </h2>
                        <div class="transform transition-transform" :class="{ 'rotate-180': !expandedSections.facilities }">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </button>
                </div>
                
                <div v-show="expandedSections.facilities" class="overflow-x-auto">
                    <Table>
                        <TableHeader class="bg-gradient-to-r from-gray-50 to-gray-100">
                            <TableRow>
                                <TableHead class="px-6 py-4 text-left font-bold text-gray-700 border-b-2 border-gray-200">Fasilitas</TableHead>
                                <TableHead class="px-6 py-4 text-right font-bold text-gray-700 border-b-2 border-gray-200">Limit</TableHead>
                                <TableHead class="px-6 py-4 text-right font-bold text-gray-700 border-b-2 border-gray-200">Outstanding</TableHead>
                                <TableHead class="px-6 py-4 text-center font-bold text-gray-700 border-b-2 border-gray-200">Utilisasi</TableHead>
                                <TableHead class="px-6 py-4 text-center font-bold text-gray-700 border-b-2 border-gray-200">Suku Bunga</TableHead>
                                <TableHead class="px-6 py-4 text-right font-bold text-gray-700 border-b-2 border-gray-200">Tunggakan Pokok</TableHead>
                                <TableHead class="px-6 py-4 text-right font-bold text-gray-700 border-b-2 border-gray-200">Tunggakan Bunga</TableHead>
                                <TableHead class="px-6 py-4 text-center font-bold text-gray-700 border-b-2 border-gray-200">PDO</TableHead>
                                <TableHead class="px-6 py-4 text-center font-bold text-gray-700 border-b-2 border-gray-200">Jatuh Tempo</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(facility, index) in borrowerFacilities" :key="facility.id" 
                                class="hover:bg-blue-50 transition-colors" :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'">
                                <TableCell class="px-6 py-4 text-left">
                                    <div class="flex items-center">
                                        <div class="w-2 h-2 bg-blue-500 rounded-full mr-3"></div>
                                        <span class="font-semibold text-gray-800">{{ facility.facility_name }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="px-6 py-4 text-right">
                                    <div class="font-mono text-sm">
                                        <div class="font-bold text-blue-600">{{ formatCompactCurrency(facility.limit) }}</div>
                                        <div class="text-xs text-gray-500">{{ formatCurrency(facility.limit) }}</div>
                                    </div>
                                </TableCell>
                                <TableCell class="px-6 py-4 text-right">
                                    <div class="font-mono text-sm">
                                        <div class="font-bold text-green-600">{{ formatCompactCurrency(facility.outstanding) }}</div>
                                        <div class="text-xs text-gray-500">{{ formatCurrency(facility.outstanding) }}</div>
                                    </div>
                                </TableCell>
                                <TableCell class="px-6 py-4 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 bg-gray-200 rounded-full h-2 mb-1">
                                            <div class="h-2 rounded-full" 
                                                :class="facility.limit > 0 && (facility.outstanding / facility.limit * 100) >= 90 ? 'bg-red-500' : 
                                                        facility.limit > 0 && (facility.outstanding / facility.limit * 100) >= 75 ? 'bg-orange-500' : 'bg-green-500'"
                                                :style="{ width: facility.limit > 0 ? Math.min((facility.outstanding / facility.limit * 100), 100) + '%' : '0%' }">
                                            </div>
                                        </div>
                                        <span class="text-xs font-semibold" 
                                            :class="facility.limit > 0 && (facility.outstanding / facility.limit * 100) >= 90 ? 'text-red-600' : 
                                                    facility.limit > 0 && (facility.outstanding / facility.limit * 100) >= 75 ? 'text-orange-600' : 'text-green-600'">
                                            {{ facility.limit > 0 ? (facility.outstanding / facility.limit * 100).toFixed(1) : 0 }}%
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-red-100 text-red-800 border border-red-200">
                                        <Percent class="w-3 h-3 mr-1" />
                                        {{ facility.interest_rate }}%
                                    </span>
                                </TableCell>
                                <TableCell class="px-6 py-4 text-right">
                                    <div class="font-mono text-sm" :class="facility.principal_arrears > 0 ? 'text-red-600 font-bold' : 'text-gray-600'">
                                        <div>{{ formatCompactCurrency(facility.principal_arrears) }}</div>
                                        <div class="text-xs">{{ formatCurrency(facility.principal_arrears) }}</div>
                                    </div>
                                </TableCell>
                                <TableCell class="px-6 py-4 text-right">
                                    <div class="font-mono text-sm" :class="facility.interest_arrears > 0 ? 'text-red-600 font-bold' : 'text-gray-600'">
                                        <div>{{ formatCompactCurrency(facility.interest_arrears) }}</div>
                                        <div class="text-xs">{{ formatCurrency(facility.interest_arrears) }}</div>
                                    </div>
                                </TableCell>
                                <TableCell class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-sm font-bold" 
                                        :class="facility.pdo_days > 0 ? 'bg-red-100 text-red-800 border border-red-200' : 'bg-green-100 text-green-800 border border-green-200'">
                                        {{ facility.pdo_days }} hari
                                    </span>
                                </TableCell>
                                <TableCell class="px-6 py-4 text-center">
                                    <div class="text-sm">
                                        <div class="font-medium text-gray-800">{{ formatDate(facility.maturity_date) }}</div>
                                    </div>
                                </TableCell>
                            </TableRow>
                            
                            <!-- Enhanced Total Row -->
                            <TableRow class="bg-gradient-to-r from-blue-50 to-indigo-50 border-t-4 border-blue-200">
                                <TableCell class="px-6 py-5 text-left">
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 bg-blue-600 rounded-full mr-3"></div>
                                        <span class="font-bold text-lg text-gray-800">TOTAL</span>
                                    </div>
                                </TableCell>
                                <TableCell class="px-6 py-5 text-right">
                                    <div class="font-mono">
                                        <div class="font-bold text-lg text-blue-600">{{ formatCompactCurrency(facilitiesTotals.totalLimit) }}</div>
                                        <div class="text-xs text-gray-500">{{ formatCurrency(facilitiesTotals.totalLimit) }}</div>
                                    </div>
                                </TableCell>
                                <TableCell class="px-6 py-5 text-right">
                                    <div class="font-mono">
                                        <div class="font-bold text-lg text-green-600">{{ formatCompactCurrency(facilitiesTotals.totalOutstanding) }}</div>
                                        <div class="text-xs text-gray-500">{{ formatCurrency(facilitiesTotals.totalOutstanding) }}</div>
                                    </div>
                                </TableCell>
                                <TableCell class="px-6 py-5 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-20 bg-gray-200 rounded-full h-3 mb-2">
                                            <div class="h-3 rounded-full" 
                                                :class="utilizationRatio >= 90 ? 'bg-red-500' : utilizationRatio >= 75 ? 'bg-orange-500' : 'bg-green-500'"
                                                :style="{ width: Math.min(utilizationRatio, 100) + '%' }">
                                            </div>
                                        </div>
                                        <span class="text-sm font-bold" :class="getRiskLevelColor(utilizationRatio)">
                                            {{ utilizationRatio.toFixed(1) }}%
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell class="px-6 py-5 text-center">
                                    <span class="text-gray-400 font-medium">-</span>
                                </TableCell>
                                <TableCell class="px-6 py-5 text-right">
                                    <div class="font-mono font-bold text-lg text-red-600">
                                        {{ formatCompactCurrency(facilitiesTotals.totalPrincipalArrears) }}
                                    </div>
                                </TableCell>
                                <TableCell class="px-6 py-5 text-right">
                                    <div class="font-mono font-bold text-lg text-red-600">
                                        {{ formatCompactCurrency(facilitiesTotals.totalInterestArrears) }}
                                    </div>
                                </TableCell>
                                <TableCell class="px-6 py-5 text-center">
                                    <span class="text-gray-400 font-medium">-</span>
                                </TableCell>
                                <TableCell class="px-6 py-5 text-center">
                                    <span class="text-gray-400 font-medium">-</span>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>

            <!-- Klasifikasi Aspek -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-red-50 to-orange-50 px-6 py-4 border-b border-gray-200">
                    <button @click="toggleSection('aspects')" 
                        class="w-full flex items-center justify-between text-left hover:bg-red-100 rounded-lg p-2 -m-2 transition-colors">
                        <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                            <Briefcase class="w-6 h-6 mr-3 text-red-600" />
                            Klasifikasi Aspek
                            <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                {{ aspects.length }} Aspek
                            </span>
                        </h2>
                        <div class="transform transition-transform" :class="{ 'rotate-180': !expandedSections.aspects }">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </button>
                </div>
                
                <div v-show="expandedSections.aspects" class="overflow-x-auto">
                    <Table>
                        <TableHeader class="bg-gradient-to-r from-gray-50 to-gray-100">
                            <TableRow>
                                <TableHead class="px-6 py-4 text-left font-bold text-gray-700 border-b-2 border-gray-200">Kode</TableHead>
                                <TableHead class="px-6 py-4 text-left font-bold text-gray-700 border-b-2 border-gray-200">Nama Aspek</TableHead>
                                <TableHead class="px-6 py-4 text-center font-bold text-gray-700 border-b-2 border-gray-200">Skor</TableHead>
                                <TableHead class="px-6 py-4 text-center font-bold text-gray-700 border-b-2 border-gray-200">Persentase</TableHead>
                                <TableHead class="px-6 py-4 text-center font-bold text-gray-700 border-b-2 border-gray-200">Klasifikasi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(aspect, index) in aspects" :key="aspect.id" 
                                class="hover:bg-red-50 transition-colors" :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'">
                                <TableCell class="px-6 py-4 text-left">
                                    <span class="inline-flex items-center px-3 py-1 rounded-lg font-mono font-bold text-sm bg-gray-100 text-gray-700 border border-gray-300">
                                        {{ aspect.id }}
                                    </span>
                                </TableCell>
                                <TableCell class="px-6 py-4 text-left">
                                    <div class="font-semibold text-gray-800">{{ aspect.name }}</div>
                                </TableCell>
                                <TableCell class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                        {{ aspect.percentage.toFixed(1) }}%
                                    </span>
                                </TableCell>
                                <TableCell class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold border-2" 
                                        :class="[
                                            getClassificationColor(aspect.classification),
                                            getClassificationBg(aspect.classification)
                                        ]">
                                        <component :is="getClassificationIcon(aspect.classification)" class="w-4 h-4 mr-2" />
                                        {{ aspect.classification }}
                                    </span>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>

            <!-- Overall Summary -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-purple-50 to-pink-50 px-6 py-4 border-b border-gray-200">
                    <button @click="toggleSection('summary')" 
                        class="w-full flex items-center justify-between text-left">
                        <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                            <TrendingUp class="w-6 h-6 mr-3 text-purple-600" />
                            Ringkasan Keseluruhan
                        </h2>
                        <div class="transform transition-transform" :class="{ 'rotate-180': !expandedSections.summary }">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </button>
                </div>
                
                <div v-show="expandedSections.summary" class="p-8">
                    <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                        <!-- Klasifikasi Final -->
                        <div class="text-center p-6 rounded-2xl border-4 shadow-lg" :class="getClassificationBg(overallSummary.final_classification)">
                            <div class="mb-4">
                                <component :is="getClassificationIcon(overallSummary.final_classification)" 
                                    class="w-12 h-12 mx-auto" :class="getClassificationColor(overallSummary.final_classification)" />
                            </div>
                            <Label class="text-sm font-bold text-gray-600 block mb-3 uppercase tracking-wide">Klasifikasi Final</Label>
                            <div class="mt-2">
                                <span class="inline-block rounded-2xl px-6 py-3 text-2xl font-bold border-4" 
                                    :class="[
                                        getClassificationColor(overallSummary.final_classification),
                                        getClassificationBg(overallSummary.final_classification)
                                    ]">
                                    {{ overallSummary.final_classification }}
                                </span>
                            </div>
                        </div>
                        
                        <!-- Risk Level Placeholder -->
                        <div class="text-center p-6 rounded-2xl border-4 bg-gray-50 border-gray-200 shadow-lg">
                            <div class="mb-4">
                                <TrendingUp class="w-12 h-12 mx-auto text-gray-400" />
                            </div>
                            <Label class="text-sm font-bold text-gray-600 block mb-3 uppercase tracking-wide">Risk Level</Label>
                            <div class="mt-2">
                                <span class="inline-block rounded-2xl px-6 py-3 text-xl font-bold bg-gray-100 text-gray-500 border-4 border-gray-200">
                                    Coming Soon
                                </span>
                            </div>
                        </div>
                        
                        <!-- Additional Metrics -->
                        <div class="text-center p-6 rounded-2xl border-4 bg-indigo-50 border-indigo-200 shadow-lg">
                            <div class="mb-4">
                                <Percent class="w-12 h-12 mx-auto text-indigo-600" />
                            </div>
                            <Label class="text-sm font-bold text-gray-600 block mb-3 uppercase tracking-wide">Total Score</Label>
                            <div class="mt-2">
                                <span class="inline-block rounded-2xl px-6 py-3 text-2xl font-bold bg-indigo-100 text-indigo-800 border-4 border-indigo-200">
                                    {{ overallSummary.total_score || 'N/A' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
