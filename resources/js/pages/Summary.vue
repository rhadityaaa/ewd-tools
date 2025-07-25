<script setup lang="ts">
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { ref, computed } from 'vue';
import { useToast } from 'vue-toastification';
import { useForm } from '@inertiajs/vue3';

const toast = useToast();

const props = defineProps({
    reportData: {
        type: Object,
        required: true,
    },
    reportId: {
        type: Number,
        required: true,
    },
    summaryCalculation: {
        type: Object,
        required: true,
    },
});

const formData = useForm({
    borrowerName: props.reportData.borrower?.name || 'N/A',
    period: props.reportData.period?.name || 'N/A',
    businessNotes: props.reportData.summary?.business_notes || '',
    override: props.reportData.summary?.is_override || false,
    collectibilityIndicator: props.reportData.summary?.indicative_collectibility || 0,
    reviewerNotes: props.reportData.summary?.reviewer_notes || '',
});

// Computed untuk aspek dari hasil perhitungan
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

// Computed untuk summary keseluruhan
const overallSummary = computed(() => props.summaryCalculation.summary);

const getClassificationColor = (classification: any) => {
    switch(classification) {
        case 'SAFE': return 'text-green-600';
        case 'WARNING': return 'text-yellow-600';
        case 'CRITICAL': return 'text-red-600';
        default: return 'text-gray-600';
    }
};

const getClassificationBg = (classification: any) => {
    switch(classification) {
        case 'SAFE': return 'bg-green-100';
        case 'WARNING': return 'bg-yellow-100';
        case 'CRITICAL': return 'bg-red-100';
        default: return 'bg-gray-100';
    }
};

const getCollectibilityText = (level: number) => {
    const levels = {
        0: 'Current',
        1: 'Special Mention',
        2: 'Substandard',
        3: 'Doubtful',
        4: 'Loss'
    };
    return levels[level as keyof typeof levels] || 'Unknown';
};

// Fungsi untuk menyimpan business notes dan reviewer notes
const saveSummaryNotes = () => {
    formData.patch(route('summary.update', props.reportId), {
        onSuccess: () => {
            toast.success('Catatan berhasil disimpan');
        },
        onError: (errors) => {
            console.error('Save errors:', errors);
            toast.error('Gagal menyimpan catatan');
        },
    });
};
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <div class="bg-[#2e3192] p-4 text-white shadow-md dark:bg-[#1a1d68] dark:text-gray-200">
            <Label class="pl-2 text-2xl font-bold">Summary Early Warning</Label>
        </div>

        <div class="mx-auto max-w-7xl p-6">
            <!-- Borrower Information -->
            <div class="mb-6 rounded-lg bg-white p-6 shadow-sm">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <Label class="text-sm font-medium text-gray-600">Nama Debitur</Label>
                        <div class="mt-1 rounded-md border border-gray-200 bg-gray-50 p-3 text-lg font-semibold text-gray-900">
                            {{ formData.borrowerName }}
                        </div>
                    </div>
                    <div>
                        <Label class="text-sm font-medium text-gray-600">Periode</Label>
                        <div class="mt-1 rounded-md border border-gray-200 bg-gray-50 p-3 text-lg font-semibold text-gray-900">
                            {{ formData.period }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overall Summary -->
            <div class="mb-6 rounded-lg bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-gray-800">Ringkasan Keseluruhan</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div class="text-center">
                        <Label class="text-sm font-medium text-gray-600">Total Skor Berbobot</Label>
                        <div class="mt-1 text-2xl font-bold text-blue-600">
                            {{ overallSummary.total_weighted_score }}/{{ overallSummary.max_weighted_score }}
                        </div>
                    </div>
                    <div class="text-center">
                        <Label class="text-sm font-medium text-gray-600">Persentase Keseluruhan</Label>
                        <div class="mt-1 text-2xl font-bold text-blue-600">
                            {{ overallSummary.overall_percentage }}%
                        </div>
                    </div>
                    <div class="text-center">
                        <Label class="text-sm font-medium text-gray-600">Risk Level</Label>
                        <div class="mt-1 text-lg font-bold" :class="{
                            'text-green-600': overallSummary.risk_level === 'low',
                            'text-yellow-600': overallSummary.risk_level === 'medium',
                            'text-red-600': overallSummary.risk_level === 'high'
                        }">
                            {{ overallSummary.risk_level.toUpperCase() }}
                        </div>
                    </div>
                    <div class="text-center">
                        <Label class="text-sm font-medium text-gray-600">Klasifikasi Final</Label>
                        <div class="mt-1">
                            <span :class="[
                                'inline-block rounded-full px-3 py-1 text-sm font-semibold',
                                getClassificationBg(overallSummary.final_classification),
                                getClassificationColor(overallSummary.final_classification)
                            ]">
                                {{ overallSummary.final_classification }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Aspect Classification Table - Simplified -->
            <div class="mb-6 rounded-lg bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-gray-800">Klasifikasi Aspek dengan Detail Perhitungan</h2>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse border border-gray-300">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border border-gray-300 px-4 py-3 text-left font-semibold text-gray-700">Aspect ID</th>
                                <th class="border border-gray-300 px-4 py-3 text-left font-semibold text-gray-700">Aspect Name</th>
                                <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700">Classification</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="aspect in aspects" :key="aspect.id" class="bg-gray-50">
                                <td class="border border-gray-300 px-4 py-3 text-center font-mono font-semibold text-gray-700">{{ aspect.id }}</td>
                                <td class="border border-gray-300 px-4 py-3 text-gray-700">{{ aspect.name }}</td>
                                <td class="border border-gray-300 px-4 py-3 text-center">
                                    <span :class="[
                                        'inline-block rounded-full px-3 py-1 text-sm font-semibold',
                                        getClassificationBg(aspect.classification),
                                        getClassificationColor(aspect.classification)
                                    ]">
                                        {{ aspect.classification }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 text-sm text-gray-600">
                    <p><strong>Keterangan:</strong></p>
                    <ul class="list-disc list-inside mt-2 space-y-1">
                        <li><strong>Klasifikasi:</strong> SAFE (≥80%), WARNING (60-79%), CRITICAL (<60%)</li>
                    </ul>
                </div>
            </div>

            <!-- Business Notes Section (Editable) -->
            <div class="mb-6 rounded-lg bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-gray-800">Business Notes</h2>
                <Textarea
                    v-model="formData.businessNotes"
                    placeholder="Masukkan catatan bisnis..."
                    class="min-h-[100px] w-full"
                />
            </div>

            <!-- Reviewer Notes Section (Editable) -->
            <div class="mb-6 rounded-lg bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-gray-800">Reviewer Notes</h2>
                <Textarea
                    v-model="formData.reviewerNotes"
                    placeholder="Masukkan catatan reviewer..."
                    class="min-h-[100px] w-full"
                />
                
                <div class="mt-4 flex justify-end">
                    <Button 
                        @click="saveSummaryNotes"
                        :disabled="formData.processing"
                        class="px-6 py-2"
                    >
                        {{ formData.processing ? 'Menyimpan...' : 'Simpan Catatan' }}
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
