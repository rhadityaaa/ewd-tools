<template>
    <AppLayout title="Laporan Persetujuan">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Laporan Persetujuan
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Period Selection -->
                <Card class="mb-6">
                    <CardHeader>
                        <CardTitle>Pilih Periode</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center space-x-4">
                            <Select v-model="selectedPeriod" @update:model-value="loadApprovalData">
                                <SelectTrigger class="w-64">
                                    <SelectValue placeholder="Pilih periode" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="period in periods" :key="period.id" :value="period.id">
                                        {{ period.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <Button v-if="selectedPeriod" @click="exportReport" variant="outline">
                                <DownloadIcon class="mr-2 h-4 w-4" />
                                Export
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Statistics Cards -->
                <div v-if="approvalData" class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Laporan</p>
                                    <p class="text-2xl font-bold">{{ approvalData.statistics.total }}</p>
                                </div>
                                <FileTextIcon class="h-8 w-8 text-blue-600" />
                            </div>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Disetujui</p>
                                    <p class="text-2xl font-bold text-green-600">{{ approvalData.statistics.approved }}</p>
                                    <p class="text-xs text-gray-500">{{ approvalData.statistics.approval_rate }}%</p>
                                </div>
                                <CheckCircleIcon class="h-8 w-8 text-green-600" />
                            </div>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Ditolak</p>
                                    <p class="text-2xl font-bold text-red-600">{{ approvalData.statistics.rejected }}</p>
                                    <p class="text-xs text-gray-500">{{ approvalData.statistics.rejection_rate }}%</p>
                                </div>
                                <XCircleIcon class="h-8 w-8 text-red-600" />
                            </div>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Menunggu</p>
                                    <p class="text-2xl font-bold text-yellow-600">{{ approvalData.statistics.pending }}</p>
                                </div>
                                <ClockIcon class="h-8 w-8 text-yellow-600" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Workflow Performance -->
                <Card v-if="approvalData" class="mb-6">
                    <CardHeader>
                        <CardTitle>Performa Workflow</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b">
                                        <th class="text-left p-2">Tahap</th>
                                        <th class="text-left p-2">Total</th>
                                        <th class="text-left p-2">Selesai</th>
                                        <th class="text-left p-2">Menunggu</th>
                                        <th class="text-left p-2">Rata-rata Waktu</th>
                                        <th class="text-left p-2">Tingkat Penyelesaian</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="step in approvalData.workflowPerformance" :key="step.step" class="border-b">
                                        <td class="p-2 font-medium">{{ step.step_name }}</td>
                                        <td class="p-2">{{ step.total_approvals }}</td>
                                        <td class="p-2 text-green-600">{{ step.completed }}</td>
                                        <td class="p-2 text-yellow-600">{{ step.pending }}</td>
                                        <td class="p-2">{{ step.avg_time_hours }}h</td>
                                        <td class="p-2">
                                            <div class="flex items-center">
                                                <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                                    <div class="bg-blue-600 h-2 rounded-full" :style="{ width: step.completion_rate + '%' }"></div>
                                                </div>
                                                <span class="text-sm">{{ step.completion_rate }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <!-- Reports List -->
                <Card v-if="approvalData">
                    <CardHeader>
                        <CardTitle>Daftar Laporan</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <!-- Fix table headers and data order -->
                                <thead>
                                    <tr class="border-b">
                                        <th class="text-left p-2">Debitur</th>
                                        <th class="text-left p-2">Divisi</th>
                                        <th class="text-left p-2">Tanggal Submit</th> <!-- ✅ Move this before Status -->
                                        <th class="text-left p-2">Status</th>
                                        <th class="text-left p-2">Tahap Saat Ini</th>
                                        <th class="text-left p-2">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="report in filteredReports" :key="report.id" class="border-b hover:bg-gray-50">
                                        <td class="p-3 font-medium">
                                            <Link :href="route('approval-reports.show', report.id)" class="text-blue-600 hover:text-blue-800">
                                                {{ report.borrower?.name }}
                                            </Link>
                                        </td>
                                        <td class="p-3">{{ report.borrower?.division?.code }}</td>
                                        <td class="p-3">{{ formatDate(report.submitted_at) }}</td> <!-- ✅ Fix order -->
                                        <td class="p-3">
                                            <Badge :variant="getStatusVariant(report.status)">{{ getStatusLabel(report.status) }}</Badge>
                                        </td>
                                        <td class="p-3">{{ getCurrentStepLabel(report) }}</td> <!-- ✅ Add current step -->
                                        <td class="p-3">
                                            <div class="flex space-x-2">
                                                <Link :href="route('approval-reports.show', report.id)">
                                                    <Button size="sm" variant="outline">
                                                        <EyeIcon class="h-4 w-4 mr-1" />
                                                        Detail
                                                    </Button>
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Link } from '@inertiajs/vue3'
import { 
    FileTextIcon, 
    CheckCircleIcon, 
    XCircleIcon, 
    ClockIcon, 
    DownloadIcon,
    EyeIcon 
} from 'lucide-vue-next'

const props = defineProps({
    periods: {
        type: Array,
        default: () => []
    },
    selectedPeriod: [String, Number],
    approvalData: {
        type: Object,
        default: () => null
    },
})

// ✅ Remove console.log
// console.log(props)

const selectedPeriod = ref(props.selectedPeriod)

// ✅ Add computed property for filtered reports
const filteredReports = computed(() => {
    if (!props.approvalData || !props.approvalData.reports) {
        return []
    }
    return props.approvalData.reports
})

// ✅ Add computed property to check if data exists
const hasData = computed(() => {
    return props.approvalData && props.approvalData.reports && props.approvalData.reports.length > 0
})

const loadApprovalData = () => {
    if (selectedPeriod.value) {
        router.get(route('approval-reports.index'), {
            period_id: selectedPeriod.value
        }, {
            preserveState: true,
            preserveScroll: true
        })
    }
}

const exportReport = () => {
    if (selectedPeriod.value) {
        window.open(route('approval-reports.export', selectedPeriod.value))
    }
}

const getStatusVariant = (status) => {
    const variants = {
        'approved': 'success',
        'rejected': 'destructive',
        'under_review': 'warning',
        'submitted': 'secondary'
    }
    return variants[status] || 'secondary'
}

const getStatusLabel = (status) => {
    const labels = {
        'approved': 'Disetujui',
        'rejected': 'Ditolak',
        'under_review': 'Dalam Review',
        'submitted': 'Disubmit'
    }
    return labels[status] || status
}

const getCurrentStepLabel = (report) => {
    // ✅ Get current step from report approvals
    if (!report.approvals || report.approvals.length === 0) {
        return 'Tidak ada tahap'
    }
    
    const stepLabels = {
        2: 'Risk Analyst',
        3: 'Kadept Bisnis', 
        4: 'Kadept Risk'
    }
    
    const currentApproval = report.approvals.find(approval => approval.status === 'pending')
    const stepValue = currentApproval?.approval_step || report.approvals[0]?.approval_step
    
    return stepLabels[stepValue] || `Step ${stepValue}` || 'Unknown'
}

const formatDate = (date) => {
    if (!date) return 'N/A'
    return new Date(date).toLocaleDateString('id-ID')
}
</script>