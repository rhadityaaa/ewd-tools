<template>
    <AppLayout title="Rekap Report">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Rekap Report
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filter Section -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Filter Report</h3>
                        <form @submit.prevent="applyFilters" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Search -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Cari Borrower</label>
                                <input
                                    v-model="filterForm.search"
                                    type="text"
                                    placeholder="Nama atau kode borrower..."
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>

                            <!-- Period Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Periode</label>
                                <select
                                    v-model="filterForm.period_id"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Semua Periode</option>
                                    <option v-for="period in periods" :key="period.id" :value="period.id">
                                        {{ period.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Borrower Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Borrower</label>
                                <select
                                    v-model="filterForm.borrower_id"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Semua Borrower</option>
                                    <option v-for="borrower in borrowers" :key="borrower.id" :value="borrower.id">
                                        {{ borrower.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Classification Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Klasifikasi</label>
                                <select
                                    v-model="filterForm.classification"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Semua Klasifikasi</option>
                                    <option value="safe">SAFE</option>
                                    <option value="watchlist">WATCHLIST</option>
                                </select>
                            </div>

                            <!-- Filter Buttons -->
                            <div class="md:col-span-4 flex gap-2">
                                <button
                                    type="submit"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium"
                                >
                                    Terapkan Filter
                                </button>
                                <button
                                    type="button"
                                    @click="clearFilters"
                                    class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md text-sm font-medium"
                                >
                                    Reset Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Reports Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Borrower
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Template
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Periode
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Klasifikasi
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Kolektibilitas
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Dibuat
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="report in reports.data" :key="report.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ report.borrower?.name }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ report.template?.name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ report.period?.name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                :class="getClassificationBadgeClass(report.summary?.final_classification)"
                                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                            >
                                                {{ getClassificationText(report.summary?.final_classification) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ report.summary?.indicative_collectibility || '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ formatDate(report.created_at) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                            <Link
                                                :href="route('summary', { reportId: report.id })"
                                                class="text-blue-600 hover:text-blue-900"
                                            >
                                                Lihat Summary
                                            </Link>
                                            <Link
                                                :href="route('reports.show', report.id)"
                                                class="text-green-600 hover:text-green-900"
                                            >
                                                Detail
                                            </Link>
                                            <button
                                                @click="confirmDelete(report)"
                                                class="text-red-600 hover:text-red-900"
                                            >
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6" v-if="reports.links">
                            <nav class="flex items-center justify-between">
                                <div class="flex-1 flex justify-between sm:hidden">
                                    <Link
                                        v-if="reports.prev_page_url"
                                        :href="reports.prev_page_url"
                                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                    >
                                        Previous
                                    </Link>
                                    <Link
                                        v-if="reports.next_page_url"
                                        :href="reports.next_page_url"
                                        class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                    >
                                        Next
                                    </Link>
                                </div>
                                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            Menampilkan
                                            <span class="font-medium">{{ reports.from }}</span>
                                            sampai
                                            <span class="font-medium">{{ reports.to }}</span>
                                            dari
                                            <span class="font-medium">{{ reports.total }}</span>
                                            hasil
                                        </p>
                                    </div>
                                    <div>
                                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                            <template v-for="link in reports.links" :key="link.label">
                                                <Link
                                                    v-if="link.url"
                                                    :href="link.url"
                                                    v-html="link.label"
                                                    :class="[
                                                        'relative inline-flex items-center px-2 py-2 border text-sm font-medium',
                                                        link.active
                                                            ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                                                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                                    ]"
                                                />
                                                <span
                                                    v-else
                                                    v-html="link.label"
                                                    :class="[
                                                        'relative inline-flex items-center px-2 py-2 border text-sm font-medium',
                                                        'bg-gray-100 border-gray-300 text-gray-400 cursor-not-allowed'
                                                    ]"
                                                />
                                            </template>
                                        </nav>
                                    </div>
                                </div>
                            </nav>
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
                            Apakah Anda yakin ingin menghapus report untuk borrower
                            <strong>{{ reportToDelete?.borrower?.name }}</strong>?
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
import { ref, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
    reports: Object,
    periods: Array,
    borrowers: Array,
    filters: Object
})

const filterForm = reactive({
    search: props.filters.search || '',
    period_id: props.filters.period_id || '',
    borrower_id: props.filters.borrower_id || '',
    classification: props.filters.classification || ''
})

const showDeleteModal = ref(false)
const reportToDelete = ref(null)

const applyFilters = () => {
    router.get(route('reports.index'), filterForm, {
        preserveState: true,
        preserveScroll: true
    })
}

const clearFilters = () => {
    Object.keys(filterForm).forEach(key => {
        filterForm[key] = ''
    })
    applyFilters()
}

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

const confirmDelete = (report) => {
    reportToDelete.value = report
    showDeleteModal.value = true
}

const deleteReport = () => {
    if (reportToDelete.value) {
        router.delete(route('reports.destroy', reportToDelete.value.id), {
            onSuccess: () => {
                showDeleteModal.value = false
                reportToDelete.value = null
            }
        })
    }
}
</script>