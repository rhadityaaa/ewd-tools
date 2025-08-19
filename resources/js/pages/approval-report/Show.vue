<template>
    <AppLayout title="Detail Laporan Persetujuan">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Detail Laporan - {{ report.borrower?.name || 'N/A' }}
                </h2>
                <div class="flex space-x-2">
                    <Link :href="route('approval-reports.index')" class="text-blue-600 hover:text-blue-800">
                        <Button variant="outline">
                            <ArrowLeftIcon class="h-4 w-4 mr-2" />
                            Kembali
                        </Button>
                    </Link>
                    <Button @click="exportReport" variant="secondary">
                        <DownloadIcon class="h-4 w-4 mr-2" />
                        Export
                    </Button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Report Overview -->
                <Card>
                    <CardHeader>
                        <CardTitle>Informasi Laporan</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="text-sm font-medium text-gray-600">Debitur</label>
                                <p class="text-lg font-semibold">{{ report.borrower?.name || 'N/A' }}</p>
                                <!-- ✅ Remove code reference since it doesn't exist -->
                                <p class="text-sm text-gray-500">ID: {{ report.borrower?.id || 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600">Divisi</label>
                                <p class="text-lg">{{ report.borrower?.division?.name || 'N/A' }}</p>
                                <p class="text-sm text-gray-500">{{ report.borrower?.division?.code || 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600">Periode</label>
                                <p class="text-lg">{{ report.period?.name || 'N/A' }}</p>
                                <p class="text-sm text-gray-500">{{ formatDateRange(report.period?.start_date, report.period?.end_date) }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600">Template</label>
                                <p class="text-lg">{{ report.template?.name || 'N/A' }}</p>
                                <!-- ✅ Remove version if it doesn't exist -->
                                <p class="text-sm text-gray-500">ID: {{ report.template?.id || 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600">Dibuat Oleh</label>
                                <p class="text-lg">{{ report.creator?.name || 'N/A' }}</p>
                                <p class="text-sm text-gray-500">{{ report.creator?.email || 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600">Status</label>
                                <Badge :variant="getStatusVariant(report.status)">{{ getStatusLabel(report.status) }}</Badge>
                                <p class="text-sm text-gray-500 mt-1">{{ formatDate(report.submitted_at) }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Borrower Information -->
                <Card>
                    <CardHeader>
                        <CardTitle>Informasi Debitur</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <!-- ✅ Fix relationship name: details instead of details -->
                        <div v-if="report.borrower?.details" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-gray-600">Grup Debitur</label>
                                <p class="text-lg">{{ report.borrower.details.borrower_group || 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600">Tujuan</label>
                                <p class="text-lg">{{ report.borrower.details.purpose || 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600">Sektor Ekonomi</label>
                                <p class="text-lg">{{ report.borrower.details.economic_sector || 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600">Bidang Usaha</label>
                                <p class="text-lg">{{ report.borrower.details.business_field || 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600">Usaha Debitur</label>
                                <p class="text-lg">{{ report.borrower.details.borrower_business || 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600">Kolektibilitas</label>
                                <Badge :variant="getCollectibilityVariant(report.borrower.details.collectibility)">
                                    {{ report.borrower.details.collectibility || 'N/A' }}
                                </Badge>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600">Restrukturisasi</label>
                                <Badge :variant="report.borrower.details.restructuring ? 'warning' : 'secondary'">
                                    {{ report.borrower.details.restructuring ? 'Ya' : 'Tidak' }}
                                </Badge>
                            </div>
                        </div>
                        <div v-else class="text-center py-4 text-gray-500">
                            Tidak ada detail informasi debitur
                        </div>
                    </CardContent>
                </Card>

                <!-- Facilities Information -->
                <Card>
                    <CardHeader>
                        <CardTitle>Informasi Fasilitas</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="report.borrower?.facilities?.length > 0" class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b">
                                        <th class="text-left p-3">Nama Fasilitas</th>
                                        <th class="text-left p-3">Limit</th>
                                        <th class="text-left p-3">Outstanding</th>
                                        <th class="text-left p-3">Suku Bunga</th>
                                        <th class="text-left p-3">Tunggakan Pokok</th>
                                        <th class="text-left p-3">Tunggakan Bunga</th>
                                        <th class="text-left p-3">PDO</th>
                                        <th class="text-left p-3">Jatuh Tempo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="facility in report.borrower.facilities" :key="facility.id" class="border-b">
                                        <td class="p-3 font-medium">{{ facility.facility_name || 'N/A' }}</td>
                                        <td class="p-3">{{ formatCurrency(facility.limit) }}</td>
                                        <td class="p-3">{{ formatCurrency(facility.outstanding) }}</td>
                                        <td class="p-3">{{ facility.interest_rate || 0 }}%</td>
                                        <td class="p-3">{{ formatCurrency(facility.principal_arrears) }}</td>
                                        <td class="p-3">{{ formatCurrency(facility.interest_arrears) }}</td>
                                        <td class="p-3">{{ facility.pdo_days || 0 }} hari</td>
                                        <td class="p-3">{{ formatDate(facility.maturity_date) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="text-center py-4 text-gray-500">
                            Tidak ada data fasilitas
                        </div>
                    </CardContent>
                </Card>

                <!-- NAW Assessment -->
                <Card>
                    <CardHeader>
                        <CardTitle>Penilaian NAW (Aspek)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="report.aspects?.length > 0" class="space-y-4">
                            <div v-for="aspect in report.aspects" :key="aspect.id" class="border rounded-lg p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="font-semibold">{{ aspect.aspect_version?.aspect?.name || 'N/A' }}</h4>
                                    <div class="flex items-center space-x-2">
                                        <Badge :variant="getClassificationVariant(aspect.classification)">
                                            {{ (aspect.classification || 'unknown').toUpperCase() }}
                                        </Badge>
                                        <span class="text-lg font-bold">{{ aspect.total_score || 0 }}</span>
                                    </div>
                                </div>
                                
                                <!-- ✅ Fix Questions and Answers filtering -->
                                <div v-if="getAspectAnswers(aspect.aspect_version_id).length > 0" class="space-y-2">
                                    <div v-for="answer in getAspectAnswers(aspect.aspect_version_id)" :key="answer.id" class="bg-gray-50 p-3 rounded">
                                        <p class="font-medium text-sm">{{ answer.question_version?.question?.text || 'N/A' }}</p>
                                        <p class="text-blue-600">{{ answer.question_option?.text || 'N/A' }}</p>
                                        <p v-if="answer.notes" class="text-sm text-gray-600 mt-1">{{ answer.notes }}</p>
                                    </div>
                                </div>
                                <div v-else class="text-sm text-gray-500">
                                    Tidak ada jawaban untuk aspek ini
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-4 text-gray-500">
                            Tidak ada data penilaian aspek
                        </div>
                    </CardContent>
                </Card>

                <!-- Summary -->
                <Card>
                    <CardHeader>
                        <CardTitle>Summary Laporan</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="report.summary" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="text-sm font-medium text-gray-600">Klasifikasi Akhir</label>
                                <Badge :variant="getClassificationVariant(report.summary.final_classification)" class="text-lg">
                                    {{ (report.summary.final_classification || 'unknown').toUpperCase() }}
                                </Badge>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600">Kolektibilitas Indikatif</label>
                                <Badge :variant="getCollectibilityVariant(report.summary.indicative_collectibility)" class="text-lg">
                                    {{ report.summary.indicative_collectibility || 'N/A' }}
                                </Badge>
                            </div>
                            <div v-if="report.summary.is_override" class="md:col-span-2">
                                <label class="text-sm font-medium text-gray-600">Override</label>
                                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mt-1">
                                    <p class="text-yellow-800 font-medium">Override Applied</p>
                                    <p class="text-yellow-700 text-sm">{{ report.summary.override_reason || 'Tidak ada alasan' }}</p>
                                </div>
                            </div>
                            <div v-if="report.summary.business_notes" class="md:col-span-2">
                                <label class="text-sm font-medium text-gray-600">Catatan Bisnis</label>
                                <p class="text-gray-800 mt-1">{{ report.summary.business_notes }}</p>
                            </div>
                            <div v-if="report.summary.reviewer_notes" class="md:col-span-2">
                                <label class="text-sm font-medium text-gray-600">Catatan Reviewer</label>
                                <p class="text-gray-800 mt-1">{{ report.summary.reviewer_notes }}</p>
                            </div>
                        </div>
                        <div v-else class="text-center py-4 text-gray-500">
                            Tidak ada data summary
                        </div>
                    </CardContent>
                </Card>

                <!-- Workflow Progress -->
                <Card>
                    <CardHeader>
                        <CardTitle>Progress Workflow</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="metrics?.approval_steps?.length > 0" class="space-y-4">
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium">Progress Keseluruhan</span>
                                    <span class="text-sm font-medium">{{ metrics.progress_percentage || 0 }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-600 h-2 rounded-full" :style="{ width: (metrics.progress_percentage || 0) + '%' }"></div>
                                </div>
                            </div>
                            
                            <div v-for="(step, index) in metrics.approval_steps" :key="step.step" class="flex items-center">
                                <div class="flex items-center">
                                    <div :class="[
                                        'w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium',
                                        step.status === 'approved' ? 'bg-green-100 text-green-800' :
                                        step.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                        'bg-gray-100 text-gray-500'
                                    ]">
                                        <CheckIcon v-if="step.status === 'approved'" class="h-4 w-4" />
                                        <ClockIcon v-else-if="step.status === 'pending'" class="h-4 w-4" />
                                        <span v-else>{{ index + 1 }}</span>
                                    </div>
                                    <div class="ml-4">
                                        <p class="font-medium">{{ step.step_name || 'N/A' }}</p>
                                        <p class="text-sm text-gray-600">{{ step.assigned_to || 'N/A' }}</p>
                                        <p v-if="step.processing_time" class="text-xs text-gray-500">{{ step.processing_time }} jam</p>
                                    </div>
                                </div>
                                <div v-if="index < metrics.approval_steps.length - 1" class="flex-1 h-px bg-gray-200 mx-4"></div>
                            </div>
                        </div>
                        <div v-else class="text-center py-4 text-gray-500">
                            Tidak ada data workflow
                        </div>
                    </CardContent>
                </Card>

                <!-- Approval Timeline -->
                <Card>
                    <CardHeader>
                        <CardTitle>Timeline Persetujuan</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="timeline?.length > 0" class="space-y-4">
                            <div v-for="event in timeline" :key="event.action_at" class="border-l-4 border-blue-200 pl-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium">{{ event.action_label || 'N/A' }}</p>
                                        <p class="text-sm text-gray-600">{{ event.user || 'N/A' }} - {{ event.step_name || 'N/A' }}</p>
                                        <p v-if="event.comments" class="text-sm text-gray-700 mt-1">{{ event.comments }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm text-gray-500">{{ formatDateTime(event.action_at) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-4 text-gray-500">
                            Tidak ada timeline persetujuan
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Link } from '@inertiajs/vue3'
import { 
    ArrowLeftIcon,
    DownloadIcon,
    CheckIcon,
    ClockIcon
} from 'lucide-vue-next'

const props = defineProps({
    report: {
        type: Object,
        required: true
    },
    workflowSteps: {
        type: Object,
        default: () => ({})
    },
    metrics: {
        type: Object,
        default: () => ({})
    },
    timeline: {
        type: Array,
        default: () => []
    },
})

// Helper methods
const getAspectAnswers = (aspectVersionId) => {
    return props.report.answers?.filter(answer => 
        answer.question_version?.aspect_version_id === aspectVersionId
    ) || []
}

const getStatusVariant = (status) => {
    const variants = {
        'approved': 'success',
        'rejected': 'destructive',
        'under_review': 'warning',
        'submitted': 'secondary',
        'draft': 'outline'
    }
    return variants[status] || 'secondary'
}

const getStatusLabel = (status) => {
    const labels = {
        'approved': 'Disetujui',
        'rejected': 'Ditolak',
        'under_review': 'Dalam Review',
        'submitted': 'Disubmit',
        'draft': 'Draft'
    }
    return labels[status] || status || 'Unknown'
}

const getClassificationVariant = (classification) => {
    if (!classification) return 'secondary'
    return classification === 'safe' ? 'success' : 'warning'
}

const getCollectibilityVariant = (collectibility) => {
    if (!collectibility) return 'secondary'
    if (collectibility <= 2) return 'success'
    if (collectibility <= 3) return 'warning'
    return 'destructive'
}

const formatCurrency = (amount) => {
    if (!amount && amount !== 0) return 'Rp 0'
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount)
}

const formatDate = (date) => {
    if (!date) return 'N/A'
    try {
        return new Date(date).toLocaleDateString('id-ID')
    } catch (error) {
        return 'Invalid Date'
    }
}

const formatDateTime = (date) => {
    if (!date) return 'N/A'
    try {
        return new Date(date).toLocaleString('id-ID')
    } catch (error) {
        return 'Invalid Date'
    }
}

const formatDateRange = (startDate, endDate) => {
    if (!startDate || !endDate) return 'N/A'
    return `${formatDate(startDate)} - ${formatDate(endDate)}`
}

// ✅ Fix export function
const exportReport = () => {
    if (props.report?.period?.id) {
        window.open(route('approval-reports.export', props.report.period.id), '_blank')
    } else {
        alert('Tidak dapat export: data periode tidak tersedia')
    }
}
</script>