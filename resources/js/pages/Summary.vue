<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/components/ui/table';
import { useDateFormatter } from '@/composables/useDateFormatter';
import { useClassificationHelper } from '@/helper/useClassificationHelper';
import { useSummaryData } from '@/helper/useSummaryData';
import { SummaryProps } from '@/types/summary';
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import axios from 'axios';

const props = defineProps<SummaryProps>();
const toast = useToast();

console.log("PROPS: ", props.reportData);

const { borrowerInfo, borrowerFacilities, facilitiesTotals, aspects, overallSummary } = useSummaryData(props);
const { getClassificationIcon, getClassificationColor, getClassificationBg, getCollectibilityInfo } = useClassificationHelper();
const { formatDate } = useDateFormatter();

const expandedSections: any = ref({
    details: true,
    facilities: true,
    aspects: true,
    summary: true,
});

// Form untuk ringkasan debitur
const summaryForm = ref({
    businessNotes: props.reportData?.summary?.business_notes || '',
    reviewerNotes: props.reportData?.summary?.reviewer_notes || '',
    isOverride: props.reportData?.summary?.is_override || false,
    collectibilityIndicator: props.reportData?.summary?.indicative_collectibility || overallSummary.value?.collectibility || 1,
    overrideReason: props.reportData?.summary?.override_reason || '',
});

const isSaving = ref(false);

const collectibilityOptions = [
    { value: 1, label: 'Kolektibilitas 1 (Lancar)' },
    { value: 2, label: 'Kolektibilitas 2 (Dalam Perhatian Khusus)' },
    { value: 3, label: 'Kolektibilitas 3 (Kurang Lancar)' },
    { value: 4, label: 'Kolektibilitas 4 (Diragukan)' },
    { value: 5, label: 'Kolektibilitas 5 (Macet)' },
];

// Helper function untuk memastikan classification type yang benar
const normalizeClassification = (classification: string | undefined): 'SAFE' | 'WATCHLIST' => {
    const normalized = classification?.toUpperCase();
    return (normalized === 'SAFE' || normalized === 'WATCHLIST') ? normalized : 'SAFE';
};

// Computed untuk final classification yang akan ditampilkan
const finalClassification = computed((): 'SAFE' | 'WATCHLIST' => {
    if (summaryForm.value.isOverride) {
        // Jika override, balik klasifikasi sistem
        const systemClassification = normalizeClassification(overallSummary.value?.final_classification);
        return systemClassification === 'SAFE' ? 'WATCHLIST' : 'SAFE';
    }
    return normalizeClassification(overallSummary.value?.final_classification);
});

// Computed untuk system classification
const systemClassification = computed((): 'SAFE' | 'WATCHLIST' => {
    return normalizeClassification(overallSummary.value?.final_classification);
});

// Check if NAW is required
const isNawRequired = computed(() => {
    return finalClassification.value === 'WATCHLIST';
});

const toggleSection = (section: any) => {
    expandedSections.value[section] = !expandedSections.value[section];
}

const saveSummary = async () => {
    if (summaryForm.value.isOverride && !summaryForm.value.overrideReason.trim()) {
        toast.error('Alasan override wajib diisi jika menggunakan override');
        return;
    }

    isSaving.value = true;
    
    try {
        const response = await axios.patch(`/summary/${props.reportId}`, {
            businessNotes: summaryForm.value.businessNotes,
            reviewerNotes: summaryForm.value.reviewerNotes,
            override: summaryForm.value.isOverride,
            collectibilityIndicator: summaryForm.value.collectibilityIndicator,
            overrideReason: summaryForm.value.overrideReason,
            finalClassification: finalClassification.value,
        });
        
        if (response.data.success) {
            toast.success('Data ringkasan berhasil disimpan');
            
            // Jika klasifikasi final adalah WATCHLIST, tampilkan notifikasi untuk NAW
            if (finalClassification.value === 'WATCHLIST') {
                toast.info('Debitur masuk kategori WATCHLIST. Silakan lengkapi Nota Analisa Watchlist (NAW).');
            }
        }
    } catch (error: any) {
        console.error('Error saving summary:', error);
        toast.error(error.response?.data?.message || 'Gagal menyimpan data');
    } finally {
        isSaving.value = false;
    }
};

const openNAW = () => {
    window.open(`/watchlist?reportId=${props.reportId}`, '_blank');
};
</script>

<template>
    <div class="min-h-screen">
        <Head title="Summary Early Warning" />
        
        <!-- Header -->
        <div class="bg-[#2e3192] p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <Label class="pl-2 text-2xl font-bold">Summary Early Warning</Label>
                <div class="flex items-center space-x-4">
                    <!-- NAW Button jika diperlukan -->
                    <div v-if="isNawRequired">
                        <Button @click="openNAW" class="bg-orange-600 hover:bg-orange-700 text-white">
                            📝 Buka NAW
                        </Button>
                    </div>
                    
                    <div class="text-right">
                        <div class="text-xs text-blue-100">Dibuat oleh:</div>
                        <div class="font-bold">{{ reportData.creator?.name }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-7xl px-4 py-8 space-y-6">
            <!-- NAW Alert jika diperlukan -->
            <div v-if="isNawRequired" class="p-4 bg-orange-50 border border-orange-200 rounded-lg">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-sm font-semibold text-orange-800">Nota Analisa Watchlist (NAW) Diperlukan</h3>
                        <p class="text-sm text-orange-700 mt-1">
                            Debitur masuk dalam kategori <strong>WATCHLIST</strong>. Anda perlu melengkapi Nota Analisa Watchlist (NAW).
                        </p>
                        <div class="mt-3">
                            <Button @click="openNAW" class="bg-orange-600 hover:bg-orange-700 text-white text-sm">
                                📝 Lengkapi NAW Sekarang
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ... existing sections (Informasi Debitur, Fasilitas Debitur, Klasifikasi Aspek) ... -->
            <!-- Informasi Debitur-->
            <div class="rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div @click="toggleSection('details')" class="px-6 py-4 border-b border-gray-200">
                    <div class="w-full flex items-center justify-between text-left rounded-lg">
                        <Label class="text-xl font-semibold text-gray-800 flex items-center">Informasi Debitur</Label>
                        <div class="transform transition-transform" :class="{ 'rotate-180': !expandedSections.details }">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div v-show="expandedSections.details" class="px-6 py-4">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <Label class="text-sm font-medium text-gray-500">Nama Debitur</Label>
                            <div class="text-lg font-semibold text-gray-900">{{ borrowerInfo.name }}</div>
                        </div>
                        <div>
                            <Label class="text-sm font-medium text-gray-500">Divisi</Label>
                            <div class="text-lg font-semibold text-gray-900">{{ borrowerInfo.division }}</div>
                        </div>
                        <div>
                            <Label class="text-sm font-medium text-gray-500">Grup Debitur</Label>
                            <div class="text-lg font-semibold text-gray-900">{{ borrowerInfo.group }}</div>
                        </div>
                        <div>
                            <Label class="text-sm font-medium text-gray-500">Tujuan Kredit</Label>
                            <div class="text-lg font-semibold text-gray-900 uppercase">{{ borrowerInfo.purpose }}</div>
                        </div>
                        <div>
                            <Label class="text-sm font-medium text-gray-500">Sektor Ekonomi</Label>
                            <div class="text-lg font-semibold text-gray-900">{{ borrowerInfo.economicSector }}</div>
                        </div>
                        <div>
                            <Label class="text-sm font-medium text-gray-500">Bidang Usaha</Label>
                            <div class="text-lg font-semibold text-gray-900">{{ borrowerInfo.businessField }}</div>
                        </div>
                        <div>
                            <Label class="text-sm font-medium text-gray-500">Jenis Usaha</Label>
                            <div class="text-lg font-semibold text-gray-900">{{ borrowerInfo.borrowerBusiness }}</div>
                        </div>
                        <div>
                            <Label class="text-sm font-medium text-gray-500">Kolektabilitas</Label>
                            <Badge class="text-md" :class="[
                                getCollectibilityInfo(borrowerInfo.collectibility).color,
                                getCollectibilityInfo(borrowerInfo.collectibility).bg
                                ]">{{ getCollectibilityInfo(borrowerInfo.collectibility).label }}</Badge>
                        </div>
                        <div>
                            <Label class="text-sm font-medium text-gray-500">Restrukturisasi</Label>
                            <Badge class="text-md" :class="borrowerInfo.restructuring ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'">{{ borrowerInfo.restructuring ? 'Ya' : 'Tidak' }}</Badge>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fasilitas Debitur -->
            <div class="rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div @click="toggleSection('facilities')" class="px-6 py-4 border-b border-gray-200">
                    <div class="w-full flex items-center justify-between text-left rounded-lg">
                        <Label class="text-xl font-semibold text-gray-800 flex items-center">Fasilitas Debitur</Label>
                        <div class="transform transition-transform" :class="{ 'rotate-180': !expandedSections.facilities }">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div v-show="expandedSections.facilities" class="overflow-x-auto">
                    <Table>
                        <TableHeader class="bg-gradient-to-r from-gray-50 to-gray-100">
                            <TableRow>
                                <TableHead class="px-6 py-4 text-left font-bold">Fasilitas</TableHead>
                                <TableHead class="px-6 py-4 text-right font-bold">Limit</TableHead>
                                <TableHead class="px-6 py-4 text-right font-bold">Outstanding</TableHead>
                                <TableHead class="px-6 py-4 text-center font-bold">Suku Bunga</TableHead>
                                <TableHead class="px-6 py-4 text-right font-bold">Tunggakan Pokok</TableHead>
                                <TableHead class="px-6 py-4 text-right font-bold">Tunggakan Bunga</TableHead>
                                <TableHead class="px-6 py-4 text-center font-bold">PDO</TableHead>
                                <TableHead class="px-6 py-4 text-center font-bold">Jatuh Tempo</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(facility, index) in borrowerFacilities" :key="facility.id" class="no-hover"
                                :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'">
                                <TableCell class="px-6 py-4 text-left">
                                    <span class="font-mono text-sm text-gray-500">{{ facility.facility_name }}</span>
                                </TableCell>
                                <TableCell class="px-6 py-4 text-right">
                                    <div class="font-mono text-sm text-gray-500">{{ Number(facility.limit) }}</div>
                                </TableCell>
                                <TableCell class="px-6 py-4 text-right">
                                    <div class="font-mono text-sm text-gray-500">{{ Number(facility.outstanding) }}</div>
                                </TableCell>
                                <TableCell class="px-6 py-4 text-center">
                                    <div class="font-mono text-sm text-gray-500">{{ facility.interest_rate }}</div>
                                </TableCell>
                                <TableCell class="px-6 py-4 text-right">
                                    <div class="font-mono text-sm text-gray-500">{{ facility.principal_arrears }}</div>
                                </TableCell>
                                <TableCell class="px-6 py-4 text-right">
                                    <div class="font-mono text-sm text-gray-500">{{ facility.interest_arrears }}</div>
                                </TableCell>
                                <TableCell class="px-6 py-4 text-center">
                                    <div class="font-mono text-sm text-gray-500">{{ facility.pdo_days }} hari</div>
                                </TableCell>
                                <TableCell class="px-6 py-4 text-center">
                                    <div class="font-mono text-sm text-gray-500">{{ formatDate(facility.maturity_date) }}</div>
                                </TableCell>
                            </TableRow>
                            <!-- Total Row -->
                            <TableRow class="bg-gradient-to-r from-blue-50 to-indigo-50 border-t-2 border-gray-200">
                                <TableCell class="px-6 py-4">TOTAL </TableCell>
                                <TableCell class="px-6 py-4 text-right">{{ facilitiesTotals.total_limit }}</TableCell>
                                <TableCell class="px-6 py-4 text-right">{{ facilitiesTotals.total_outstanding }}</TableCell>
                                <TableCell class="px-6 py-4 text-center"></TableCell>
                                <TableCell class="px-6 py-4 text-right">{{ facilitiesTotals.total_principal_arrears }}</TableCell>
                                <TableCell class="px-6 py-4 text-right">{{ facilitiesTotals.total_interest_arrears}}</TableCell>
                                <TableCell class="px-6 py-4 text-center"></TableCell>
                                <TableCell class="px-6 py-4 text-center"></TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>

            <!-- Klasifikasi Aspek -->
            <div class="rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div @click="toggleSection('aspects')" class="px-6 py-4 border-b border-gray-200">
                    <div class="w-full flex items-center justify-between text-left rounded-lg">
                        <Label class="text-xl font-semibold text-gray-800 flex items-center">Klasifikasi Aspek</Label>
                        <div class="transform transition-transform" :class="{ 'rotate-180': !expandedSections.aspects }">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div v-show="expandedSections.aspects" class="overflow-x-auto">
                    <Table>
                        <TableHeader class="bg-gradient-to-r from-gray-50 to-gray-100">
                            <TableRow>
                                <TableHead class="px-6 py-4 text-left font-bold border-b-2 border-gray-200">Kode</TableHead>
                                <TableHead class="px-6 py-4 text-center font-bold border-b-2 border-gray-200">Nama Aspek</TableHead>
                                <TableHead class="px-6 py-4 text-right font-bold border-b-2 border-gray-200">Klasifikasi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(aspect, index) in aspects" :key="aspect.aspect_code"
                                :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'">
                                <TableCell class="px-6 py-4 text-left font-semibold">{{ aspect.aspect_code }}</TableCell>
                                <TableCell class="px-6 py-4 text-center">{{ aspect.aspect_name }}</TableCell>
                                <TableCell class="px-4 py-2 text-right">
                                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold border-2"
                                        :class="[
                                            getClassificationColor(aspect.classification),
                                            getClassificationBg(aspect.classification)
                                        ]"
                                    >
                                        <component :is="getClassificationIcon(aspect.classification)" class="w-4 h-4 mr-2"/>
                                        {{ aspect.classification }}
                                    </span>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>

            <!-- Ringkasan Debitur -->
            <div class="rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div @click="toggleSection('summary')" class="px-6 py-4 border-b border-gray-200">
                    <div class="w-full flex items-center justify-between text-left rounded-lg">
                        <Label class="text-xl font-semibold text-gray-800 flex items-center">Ringkasan Debitur</Label>
                        <div class="transform transition-transform" :class="{ 'rotate-180': !expandedSections.summary }">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div v-show="expandedSections.summary" class="px-6 py-6">
                    <!-- Hasil Kalkulasi Sistem -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Hasil Kalkulasi Sistem</h3>
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 p-4 bg-gray-50 rounded-lg">
                            <div>
                                <Label class="text-sm font-medium text-gray-500">Klasifikasi Sistem</Label>
                                <div class="mt-1">
                                    <Badge class="text-sm px-3 py-1" :class="[
                                        getClassificationColor(systemClassification),
                                        getClassificationBg(systemClassification)
                                    ]">
                                        {{ systemClassification }}
                                    </Badge>
                                </div>
                            </div>
                            <div>
                                <Label class="text-sm font-medium text-gray-500">Kolektabilitas</Label>
                                <div class="mt-1">
                                    <Badge class="text-sm px-3 py-1" :class="[
                                        getCollectibilityInfo(overallSummary?.collectibility).color,
                                        getCollectibilityInfo(overallSummary?.collectibility).bg
                                    ]">
                                        {{ getCollectibilityInfo(overallSummary?.collectibility).label }}
                                    </Badge>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Klasifikasi Final -->
                    <div class="mb-6 p-4 rounded-lg border" :class="summaryForm.isOverride ? 'border-amber-200 bg-amber-50' : 'border-gray-200 bg-white'">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">Klasifikasi Final</h3>
                        <div class="flex items-center justify-between">
                            <div>
                                <Badge class="text-lg px-4 py-2" :class="[
                                    getClassificationColor(finalClassification),
                                    getClassificationBg(finalClassification)
                                ]">
                                    {{ finalClassification }}
                                </Badge>
                            </div>
                            <div v-if="summaryForm.isOverride" class="text-sm text-amber-600">
                                Override: {{ systemClassification }} → {{ finalClassification }}
                            </div>
                        </div>
                    </div>

                    <!-- Form Override dan Catatan -->
                    <div class="space-y-6">
                        <!-- Override Section -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center space-x-3">
                                <Checkbox 
                                    id="override" 
                                    v-model="summaryForm.isOverride" 
                                />
                                <Label for="override" class="text-base font-medium text-gray-800">
                                    Override Hasil Sistem
                                </Label>
                            </div>
                            
                            <div v-if="summaryForm.isOverride" class="space-y-4 m-6">
                                <div class="text-sm text-gray-600 p-3 bg-gray-50 rounded">
                                    Klasifikasi akan berubah dari <strong>{{ systemClassification }}</strong> 
                                    menjadi <strong>{{ finalClassification }}</strong>
                                </div>

                                <div>
                                    <Label for="overrideReason" class="text-sm font-medium text-gray-700">Alasan Override</Label>
                                    <Textarea 
                                        id="overrideReason"
                                        v-model="summaryForm.overrideReason"
                                        placeholder="Jelaskan alasan melakukan override..."
                                        rows="3"
                                        class="mt-1"
                                        required
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Catatan Bisnis -->
                        <div>
                            <Label for="businessNotes" class="text-base font-medium text-gray-800">Catatan Bisnis</Label>
                            <Textarea 
                                id="businessNotes"
                                v-model="summaryForm.businessNotes"
                                placeholder="Catatan bisnis terkait debitur..."
                                rows="4"
                                class="mt-2"
                            />
                        </div>

                        <!-- Catatan Reviewer -->
                        <div>
                            <Label for="reviewerNotes" class="text-base font-medium text-gray-800">Catatan Reviewer</Label>
                            <Textarea 
                                id="reviewerNotes"
                                v-model="summaryForm.reviewerNotes"
                                placeholder="Catatan reviewer..."
                                rows="3"
                                class="mt-2"
                            />
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex justify-end pt-4 border-t border-gray-200">
                            <Button 
                                @click="saveSummary" 
                                :disabled="isSaving"
                                class="px-6 py-2"
                            >
                                {{ isSaving ? 'Menyimpan...' : 'Simpan Ringkasan' }}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>