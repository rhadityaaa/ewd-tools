<script setup lang="ts">
import { Label } from '@/components/ui/label';
import { ref, computed } from 'vue';
import { useToast } from 'vue-toastification';

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

const formData = ref({
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

            <!-- Aspect Classification Table dengan Detail Skor -->
            <div class="mb-6 rounded-lg bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-gray-800">Klasifikasi Aspek dengan Detail Perhitungan</h2>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse border border-gray-300">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border border-gray-300 px-4 py-3 text-left font-semibold text-gray-700">Aspect ID</th>
                                <th class="border border-gray-300 px-4 py-3 text-left font-semibold text-gray-700">Aspect Name</th>
                                <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700">Skor Total</th>
                                <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700">Persentase</th>
                                <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700">Bobot Template</th>
                                <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700">Skor Berbobot</th>
                                <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700">Classification</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="aspect in aspects" :key="aspect.id" class="bg-gray-50">
                                <td class="border border-gray-300 px-4 py-3 text-center font-mono font-semibold text-gray-700">{{ aspect.id }}</td>
                                <td class="border border-gray-300 px-4 py-3 text-gray-700">{{ aspect.name }}</td>
                                <td class="border border-gray-300 px-4 py-3 text-center text-sm">
                                    <div class="font-semibold">{{ aspect.totalScore }}/{{ aspect.maxScore }}</div>
                                </td>
                                <td class="border border-gray-300 px-4 py-3 text-center">
                                    <div class="font-semibold text-blue-600">{{ aspect.percentage }}%</div>
                                </td>
                                <td class="border border-gray-300 px-4 py-3 text-center">
                                    <div class="font-semibold text-purple-600">{{ aspect.weight }}%</div>
                                </td>
                                <td class="border border-gray-300 px-4 py-3 text-center">
                                    <div class="font-semibold text-indigo-600">{{ aspect.weightedScore }}</div>
                                </td>
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
                        <li><strong>Skor Total:</strong> Hasil penjumlahan (skor pertanyaan × bobot pertanyaan) untuk semua pertanyaan dalam aspek</li>
                        <li><strong>Persentase:</strong> (Skor Total / Skor Maksimal) × 100%</li>
                        <li><strong>Bobot Template:</strong> Bobot aspek sesuai template yang digunakan</li>
                        <li><strong>Skor Berbobot:</strong> (Persentase / 100) × Bobot Template</li>
                        <li><strong>Klasifikasi:</strong> SAFE (≥80%), WARNING (60-79%), CRITICAL (<60%)</li>
                    </ul>
                </div>
            </div>

            <!-- Business Notes Section (Editable) -->
            <div class="mb-6 rounded-lg bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-gray-800">Business Notes</h2>
                <textarea
                    v-model="formData.businessNotes"
                    class="w-full rounded-md border border-gray-300 p-3 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                    rows="4"
                    placeholder="Enter business-related notes and observations..."
                ></textarea>
            </div>

            <!-- Override and Collectibility Section (Read-only) -->
            <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Override Section (Read-only) -->
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold text-gray-800">Override Decision</h2>
                    <div class="flex items-center space-x-3">
                        <input
                            id="override-checkbox"
                            v-model="formData.override"
                            type="checkbox"
                            disabled
                            class="h-4 w-4 cursor-not-allowed rounded border-gray-300 bg-gray-100 text-gray-400"
                        />
                        <label for="override-checkbox" class="text-sm font-medium text-gray-500"> Override automatic classification </label>
                    </div>
                    <p class="mt-2 text-xs text-gray-400">This setting is read-only in summary view.</p>
                </div>

                <!-- Collectibility Indicator (Read-only) -->
                <div class="mb-6 rounded-lg bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold text-gray-800">Collectibility Indicator</h2>
                    <div class="flex items-center space-x-3">
                        <Label class="text-sm font-medium text-gray-700">Level:</Label>
                        <div class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-gray-700">
                            {{ overallSummary.collectibility }} - {{ getCollectibilityText(overallSummary.collectibility) }}
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-400">Indikator ini dihitung otomatis berdasarkan persentase skor keseluruhan.</p>
                </div>
            </div>

            <!-- Reviewer Notes Section (Editable) -->
            <div class="mb-6 rounded-lg bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-gray-800">Reviewer Notes</h2>
                <textarea
                    v-model="formData.reviewerNotes"
                    class="w-full rounded-md border border-gray-300 p-3 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                    rows="4"
                    placeholder="Enter reviewer comments and recommendations..."
                ></textarea>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-4">
                <button
                    type="button"
                    class="rounded-md border border-gray-300 bg-white px-6 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                >
                    Back
                </button>
                <button
                    type="button"
                    class="rounded-md bg-blue-600 px-6 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                >
                    Save Notes
                </button>
                <button
                    type="button"
                    class="rounded-md bg-green-600 px-6 py-2 text-sm font-medium text-white hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:outline-none"
                >
                    Export PDF
                </button>
            </div>
        </div>
    </div>
</template>
