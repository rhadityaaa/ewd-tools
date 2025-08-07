<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Label } from '@/components/ui/label';
import { MonitoringNoteProps } from '@/types/monitoring';
import { ref } from 'vue';

const props = defineProps<MonitoringNoteProps>();

console.log('Monitoring Note Props:', props);

const expandedSections: any = ref({
    monitoring: false,
    actionItems: false,
    watchlist: false,
})

const toggleSection = (section: any) => {
    expandedSections.value[section] = !expandedSections.value[section];
}
</script>

<template>
    <div class="min-h-screen">
        <Head title="Nota Analisa Watchlist"/>
        
        <!-- Header -->
        <div class="bg-orange-600 p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <Label class="pl-2 text-2xl font-bold">Nota Analisa Watchlist</Label>
                <div class="text-right">
                    <div class="text-xs text-orange-100">Dibuat oleh:</div>
                    <div class="font-bold">{{ report.creator?.name }}</div>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-7xl px-4 py-8 space-y-6">
            <!-- Nota Monitoring -->
            <div class="rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div @click="toggleSection('monitoring')" class="px-6 py-4 border-b border-gray-200 cursor-pointer">
                    <div class="w-full flex items-center justify-between text-left rounded-lg">
                        <Label class="text-lg font-semibold text-gray-800 flex-items-center">Nota Monitoring</Label>
                        <div class="transform transition-transform" :class="{ 'rotate-180': !expandedSections.monitoring }">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div v-show="expandedSections.monitoring" class="px-6 py-4">
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <Label class="text-sm font-medium text-gray-500">Alasan Watchlist</Label>
                                <div class="mt-2">
                                    <textarea
                                        v-model="monitoringNote.watchlist_reason"
                                        class="w-full min-h-[100px] p-4 bg-gray-50 rounded-lg border resize-y"
                                        placeholder="Masukkan alasan watchlist..."
                                    ></textarea>
                                </div>
                            </div>
                            <div>
                                <Label class="text-sm font-medium text-gray-500">Account Strategy</Label>
                                <div class="mt-2">
                                    <textarea
                                        v-model="monitoringNote.account_strategy"
                                        class="w-full min-h-[100px] p-4 bg-gray-50 rounded-lg border resize-y"
                                        placeholder="Masukkan account strategy..."
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Items -->
            <div class="rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div @click="toggleSection('actionItems')" class="px-6 py-4 border-b border-gray-200 cursor-pointer">
                    <div class="w-full flex items-center justify-between text-left rounded-lg">
                        <Label class="text-lg font-semibold text-gray-800 flex-items-center">Action Plan</Label>
                        <div class="transform transition-transform" :class="{ 'rotate-180': !expandedSections.actionItems }">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div v-show="expandedSections.actionItems" class="overflow-x-auto">
                    <div>
                    </div>
                </div>
            </div>
        </div>

        <h1 class="text-red-500">{{ props.isNawRequired }}</h1>
        <h1 class="text-blue-500">{{ props.monitoringNote }}</h1>
        <h1 class="text-green-500">{{ props.watchlist }}</h1>
    </div>
</template>