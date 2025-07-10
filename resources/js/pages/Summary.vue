<script setup lang="ts">
import { Label } from '@/components/ui/label';
import { ref } from 'vue';
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
});

const formData = ref({
    borrowerName: 'BARASENTOSA LESTARI',
    period: 'IV 2024',
    aspects: [
        { id: 'A', name: 'HUKUM', classification: 'SAFE' },
        { id: 'B', name: 'MANAJEMEN', classification: 'SAFE' },
        { id: 'C', name: 'AGUNAN/JAMINAN', classification: 'WARNING' },
        { id: 'D', name: 'KEUANGAN', classification: 'WARNING' },
        { id: 'E', name: 'PEMASARAN', classification: 'SAFE' },
        { id: 'F', name: 'AMDAL', classification: 'WARNING' },
        { id: 'G', name: 'LAINNYA', classification: 'SAFE' },
    ],
    businessNotes: '',
    override: false,
    collectibilityIndicator: 0,
    reviewerNotes: '',
});

const getClassificationColor = (classification: any) => {
    return classification === 'SAFE' ? 'text-green-600' : 'text-red-600';
};

const getClassificationBg = (classification: any) => {
    return classification === 'SAFE' ? 'bg-green-100' : 'bg-red-100';
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

            <!-- Aspect Classification Table (Read-only) -->
            <div class="mb-6 rounded-lg bg-white p-6 shadow-sm">
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
                            <tr v-for="aspect in formData.aspects" :key="aspect.id" class="bg-gray-50">
                                <td class="border border-gray-300 px-4 py-3 text-center font-mono font-semibold text-gray-700">{{ aspect.id }}</td>
                                <td class="border border-gray-300 px-4 py-3 text-gray-700">{{ aspect.name }}</td>
                                <td class="border border-gray-300 px-4 py-3 text-center">
                                    <span
                                        :class="[
                                            'inline-block rounded-full px-3 py-1 text-sm font-semibold',
                                            getClassificationBg(aspect.classification),
                                            getClassificationColor(aspect.classification),
                                        ]"
                                    >
                                        {{ aspect.classification }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold text-gray-800">Collectibility Indicator</h2>
                    <div class="flex items-center space-x-3">
                        <Label class="text-sm font-medium text-gray-700">Level:</Label>
                        <div class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-gray-700">
                            {{ formData.collectibilityIndicator }} -
                            {{
                                formData.collectibilityIndicator === 0
                                    ? 'Current'
                                    : formData.collectibilityIndicator === 1
                                      ? 'Special Mention'
                                      : formData.collectibilityIndicator === 2
                                        ? 'Substandard'
                                        : formData.collectibilityIndicator === 3
                                          ? 'Doubtful'
                                          : 'Loss'
                            }}
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-400">This indicator is read-only in summary view.</p>
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
