<template>
    <AppLayout title="Detail Report">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Detail Report - {{ report.borrower?.name }}
                </h2>
                <Link
                    :href="route('reports.index')"
                    class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                >
                    Kembali ke Rekap
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Report Info -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Report</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Borrower</label>
                                <p class="mt-1 text-sm text-gray-900">{{ report.borrower?.name }} ({{ report.borrower?.code }})</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Template</label>
                                <p class="mt-1 text-sm text-gray-900">{{ report.template?.name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Periode</label>
                                <p class="mt-1 text-sm text-gray-900">{{ report.period?.name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Dibuat Oleh</label>
                                <p class="mt-1 text-sm text-gray-900">{{ report.creator?.name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tanggal Dibuat</label>
                                <p class="mt-1 text-sm text-gray-900">{{ formatDate(report.created_at) }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Klasifikasi Final</label>
                                <span
                                    :class="getClassificationBadgeClass(report.summary?.final_classification)"
                                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full mt-1"
                                >
                                    {{ getClassificationText(report.summary?.final_classification) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Aksi</h3>
                        <div class="flex gap-4">
                            <Link
                                :href="route('summary', { reportId: report.id })"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                            >
                                Lihat Summary Lengkap
                            </Link>
                            <button
                                @click="confirmDelete"
                                class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
                            >
                                Hapus Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3 text-center">
                    <h3 class="text-lg font-medium text-gray-900">Konfirmasi Hapus</h3>
                    <div class="mt-2 px-7 py-3">
                        <p class="text-sm text-gray-500">
                            Apakah Anda yakin ingin menghapus report ini?
                            Tindakan ini tidak dapat dibatalkan.
                        </p>
                    </div>
                    <div class="items-center px-4 py-3">
                        <button
                            @click="deleteReport"
                            class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-300"
                        >
                            Hapus
                        </button>
                        <button
                            @click="showDeleteModal = false"
                            class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-24 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300"
                        >
                            Batal
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
    report: Object
})

const showDeleteModal = ref(false)

const getClassificationBadgeClass = (classification) => {
    switch (classification) {
        case 'safe':
            return 'bg-green-100 text-green-800'
        case 'watchlist':
            return 'bg-red-100 text-red-800'
        default:
            return 'bg-gray-100 text-gray-800'
    }
}

const getClassificationText = (classification) => {
    switch (classification) {
        case 'safe':
            return 'SAFE'
        case 'watchlist':
            return 'WATCHLIST'
        default:
            return '-'
    }
}

const formatDate = (dateString) => {
    if (!dateString) return '-'
    return new Date(dateString).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const confirmDelete = () => {
    showDeleteModal.value = true
}

const deleteReport = () => {
    router.delete(route('reports.destroy', props.report.id), {
        onSuccess: () => {
            router.visit(route('reports.index'))
        }
    })
}
</script>