<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/components/ui/table';
import { useDateFormatter } from '@/composables/useDateFormatter';
import { useClassificationHelper } from '@/helper/useClassificationHelper';
import { useSummaryData } from '@/helper/useSummaryData';
import { SummaryProps } from '@/types/summary';
import { ref } from 'vue';

const props = defineProps<SummaryProps>();

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

const toggleSection = (section: any) => {
    expandedSections.value[section] = !expandedSections.value[section];
}
</script>

<template>
    <div class="min-h-screen">
        <Head title="Summary Early Warning" />
        
        <!-- Header -->
        <div class="bg-[#2e3192] p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <Label class="pl-2 text-2xl font-bold">Summary Early Warning</Label>
                <div class="text-right">
                    <div class="text-xs text-blue-100">Dibuat oleh:</div>
                    <div class="font-bold">{{ reportData.creator?.name }}</div>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-7xl px-4 py-8 space-y-6">
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
                    <!-- <div>{{ reportData.borrower?.details }}</div> -->
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
                    <!-- <div>{{ reportData.borrower?.facilities }}</div> -->
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
                    <!-- <div>{{ summaryCalculation.aspects }}</div> -->
                </div>
            </div>

            <!-- Ringkasan Debitur -->
            <div class="rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div @click="toggleSection('overallSummary')" class="px-6 py-4 border-b border-gray-200">
                    <div class="w-full flex items-center justify-between text-left rounded-lg">
                        <Label class="text-xl font-semibold text-gray-800 flex items-center">Ringkasan Debitur</Label>
                        <div class="transform transition-transform" :class="{ 'rotate-180': !expandedSections.overallSummary }">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div v-show="expandedSections.overallSummary" class="overflow-x-auto">
                    <div>{{ reportData.summary, overallSummary }}</div>
                </div>
            </div>
        </div>
    </div>
</template>