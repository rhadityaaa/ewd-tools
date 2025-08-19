<template>
    <AppLayout title="Detail Report">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Detail Report - {{ report.borrower?.name }}
                </h2>
                <div class="flex space-x-2">
                    <!-- Edit Button for Rejected Reports -->
                    <Link
                        v-if="canEditReport"
                        :href="route('reports.edit', report.id)"
                        class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded flex items-center"
                    >
                        <EditIcon class="h-4 w-4 mr-2" />
                        Edit Laporan
                    </Link>
                    
                    <!-- Resubmit Button for Draft Reports -->
                    <button
                        v-if="canResubmitReport"
                        @click="resubmitReport"
                        class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded flex items-center"
                    >
                        <SendIcon class="h-4 w-4 mr-2" />
                        Submit Ulang
                    </button>
                    
                    <Link
                        :href="route('reports.index')"
                        class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded flex items-center"
                    >
                        <ArrowLeftIcon class="h-4 w-4 mr-2" />
                        Kembali
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Rejection Alert -->
                <div v-if="isRejected && report.rejection_reason" class="mb-6">
                    <div class="bg-red-50 border border-red-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <AlertCircleIcon class="h-5 w-5 text-red-400" />
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">
                                    Laporan Ditolak
                                </h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <p><strong>Alasan Penolakan:</strong> {{ report.rejection_reason }}</p>
                                    <p class="mt-2">Silakan edit laporan untuk memperbaiki masalah yang disebutkan di atas.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Revision Required Alert -->
                <div v-if="isRevisionRequired && report.rejection_reason" class="mb-6">
                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <AlertTriangleIcon class="h-5 w-5 text-yellow-400" />
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">
                                    Revisi Diperlukan
                                </h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <p><strong>Catatan Revisi:</strong> {{ report.rejection_reason }}</p>
                                    <p class="mt-2">Silakan edit laporan sesuai dengan catatan revisi di atas.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Report Info -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Report</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Borrower</label>
                                <p class="mt-1 text-sm text-gray-900">{{ report.borrower?.name }}</p>
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
                                <label class="block text-sm font-medium text-gray-700">Status Laporan</label>
                                <span
                                    :class="getStatusBadgeClass(report.status)"
                                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full mt-1"
                                >
                                    {{ getStatusText(report.status) }}
                                </span>
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
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { 
    EditIcon, 
    SendIcon, 
    ArrowLeftIcon, 
    AlertCircleIcon, 
    AlertTriangleIcon 
} from 'lucide-vue-next'

const props = defineProps({
    report: Object
})

const showDeleteModal = ref(false)
const page = usePage()

// Check if current user can edit this report
const canEditReport = computed(() => {
    const user = page.props.auth?.user
    if (!user) return false
    
    // Only unit_bisnis role can edit
    if (!user.roles?.some(role => role.name === 'unit_bisnis')) return false
    
    // Only creator can edit their own report
    if (props.report.created_by !== user.id) return false
    
    // Only rejected, revision_required, or pending reports can be edited
    return isRejected.value || isRevisionRequired.value || isPending.value
})

// Check if current user can resubmit this report
const canResubmitReport = computed(() => {
    const user = page.props.auth?.user
    if (!user) return false
    
    // Only unit_bisnis role can resubmit
    if (!user.roles?.some(role => role.name === 'unit_bisnis')) return false
    
    // Only creator can resubmit their own report
    if (props.report.created_by !== user.id) return false
    
    // Only draft reports can be resubmitted
    return props.report.status === 1 // DRAFT
})

// Check report status
const isRejected = computed(() => props.report.status === 5) // REJECTED
const isRevisionRequired = computed(() => props.report.status === 6) // REVISION_REQUIRED
const isPending = computed(() => props.report.status === 2) // SUBMITTED/PENDING

// Status badge styling
const getStatusBadgeClass = (status) => {
    switch (status) {
        case 1: // DRAFT
            return 'bg-gray-100 text-gray-800'
        case 2: // SUBMITTED
            return 'bg-blue-100 text-blue-800'
        case 3: // UNDER_REVIEW
            return 'bg-yellow-100 text-yellow-800'
        case 4: // APPROVED
            return 'bg-green-100 text-green-800'
        case 5: // REJECTED
            return 'bg-red-100 text-red-800'
        case 6: // REVISION_REQUIRED
            return 'bg-orange-100 text-orange-800'
        default:
            return 'bg-gray-100 text-gray-800'
    }
}

const getStatusText = (status) => {
    switch (status) {
        case 1:
            return 'Draft'
        case 2:
            return 'Submitted'
        case 3:
            return 'Under Review'
        case 4:
            return 'Approved'
        case 5:
            return 'Rejected'
        case 6:
            return 'Revision Required'
        default:
            return 'Unknown'
    }
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

const confirmDelete = () => {
    showDeleteModal.value = true
}

const resubmitReport = () => {
    if (confirm('Apakah Anda yakin ingin submit ulang laporan ini untuk approval?')) {
        router.post(route('reports.resubmit', props.report.id), {}, {
            onSuccess: () => {
                // Success message will be handled by flash message
            },
            onError: (errors) => {
                console.error('Resubmit errors:', errors)
            }
        })
    }
}

const deleteReport = () => {
    router.delete(route('reports.destroy', props.report.id), {
        onSuccess: () => {
            router.visit(route('reports.index'))
        }
    })
}
</script>