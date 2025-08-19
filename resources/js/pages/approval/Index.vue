<template>
    <AppLayout title="Persetujuan Laporan">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Persetujuan Laporan
                </h2>
                <!-- Add refresh button -->
                <Button @click="refreshData" variant="outline" size="sm" :disabled="isRefreshing">
                    <RefreshCwIcon :class="['h-4 w-4 mr-2', { 'animate-spin': isRefreshing }]" />
                    {{ isRefreshing ? 'Refreshing...' : 'Refresh' }}
                </Button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Debug Info (Remove in production) -->
                <div v-if="debugInfo && showDebug" class="mb-4 p-4 bg-gray-100 rounded">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-bold">Debug Info:</h3>
                        <Button @click="showDebug = false" size="sm" variant="ghost">
                            <XIcon class="h-4 w-4" />
                        </Button>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <p><strong>User ID:</strong> {{ debugInfo.user_id }}</p>
                        <p><strong>User Roles:</strong> {{ debugInfo.user_roles?.join(', ') || 'None' }}</p>
                        <p><strong>Total Reports:</strong> {{ debugInfo.total_reports }}</p>
                        <p><strong>Total Approvals:</strong> {{ debugInfo.total_approvals }}</p>
                        <p><strong>User Approvals:</strong> {{ debugInfo.user_approvals }}</p>
                        <p><strong>Pending Count:</strong> {{ debugInfo.pending_count }}</p>
                    </div>
                </div>

                <!-- Role Information -->
                <Card v-if="userRole" class="mb-6">
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <Badge variant="secondary">{{ getRoleLabel(userRole) }}</Badge>
                                <span class="text-sm text-gray-600">{{ getRoleDescription(userRole) }}</span>
                            </div>
                            <Button v-if="debugInfo && !showDebug" @click="showDebug = true" size="sm" variant="ghost">
                                Debug Info
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Statistics Cards -->
                <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Menunggu Persetujuan</p>
                                    <p class="text-2xl font-bold text-yellow-600">{{ pendingReports?.length || 0 }}</p>
                                </div>
                                <ClockIcon class="h-8 w-8 text-yellow-600" />
                            </div>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Perlu Tindakan</p>
                                    <p class="text-2xl font-bold text-red-600">{{ urgentReports }}</p>
                                </div>
                                <AlertTriangleIcon class="h-8 w-8 text-red-600" />
                            </div>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Disetujui Hari Ini</p>
                                    <p class="text-2xl font-bold text-green-600">{{ approvedToday }}</p>
                                </div>
                                <CheckCircleIcon class="h-8 w-8 text-green-600" />
                            </div>
                        </CardContent>
                    </Card>
                    
                    <!-- Add average processing time -->
                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Rata-rata Proses</p>
                                    <p class="text-2xl font-bold text-blue-600">{{ averageProcessingDays }}d</p>
                                </div>
                                <TrendingUpIcon class="h-8 w-8 text-blue-600" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Filter and Search -->
                <Card class="mb-6">
                    <CardContent class="p-4">
                        <div class="flex flex-col md:flex-row gap-4">
                            <div class="flex-1">
                                <input 
                                    v-model="searchQuery"
                                    type="text" 
                                    placeholder="Cari berdasarkan nama debitur..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                />
                            </div>
                            <select 
                                v-model="priorityFilter"
                                class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="">Semua Prioritas</option>
                                <option value="urgent">Urgent</option>
                                <option value="high">High</option>
                                <option value="normal">Normal</option>
                            </select>
                            <Button @click="clearFilters" variant="outline" size="sm">
                                Clear Filters
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Pending Reports Table -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle>Laporan Menunggu Persetujuan</CardTitle>
                            <Badge variant="outline">{{ filteredReports.length }} dari {{ pendingReports?.length || 0 }}</Badge>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div v-if="!filteredReports || filteredReports.length === 0" class="text-center py-8">
                            <FileTextIcon class="mx-auto h-12 w-12 text-gray-400" />
                            <p class="mt-2 text-gray-500">
                                {{ searchQuery || priorityFilter ? 'Tidak ada laporan yang sesuai filter' : 'Tidak ada laporan yang menunggu persetujuan' }}
                            </p>
                            <p class="text-sm text-gray-400 mt-1">Role Anda: {{ getRoleLabel(userRole) || 'Tidak ada role' }}</p>
                            <div v-if="searchQuery || priorityFilter" class="mt-4">
                                <Button @click="clearFilters" variant="outline" size="sm">
                                    Clear Filters
                                </Button>
                            </div>
                        </div>
                        
                        <div v-else class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b">
                                        <th class="text-left p-3">Debitur</th>
                                        <th class="text-left p-3">Divisi</th>
                                        <th class="text-left p-3">Tanggal Submit</th>
                                        <th class="text-left p-3">Tahap</th>
                                        <th class="text-left p-3">Prioritas</th>
                                        <th class="text-left p-3">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="report in filteredReports" :key="report.id" class="border-b hover:bg-gray-50 transition-colors">
                                        <td class="p-3 font-medium">{{ report.borrower?.name || 'N/A' }}</td>
                                        <td class="p-3">{{ report.borrower?.division?.code || 'N/A' }}</td>
                                        <td class="p-3">{{ formatDate(report.submitted_at) }}</td>
                                        <td class="p-3">
                                            <Badge variant="outline">{{ getCurrentStepLabel(report) }}</Badge>
                                        </td>
                                        <td class="p-3">
                                            <Badge :variant="getPriorityVariant(report)">{{ getPriorityLabel(report) }}</Badge>
                                        </td>
                                        <td class="p-3">
                                            <div class="flex space-x-2">
                                                <Link :href="route('approvals.show', report.id)" class="text-blue-600 hover:text-blue-800">
                                                    <Button size="sm" variant="outline">
                                                        <EyeIcon class="h-4 w-4 mr-1" />
                                                        Review
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
import { computed, ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Link } from '@inertiajs/vue3'
import { 
    ClockIcon, 
    AlertTriangleIcon, 
    CheckCircleIcon, 
    FileTextIcon,
    EyeIcon,
    RefreshCwIcon,
    TrendingUpIcon,
    XIcon
} from 'lucide-vue-next'

const props = defineProps({
    pendingReports: {
        type: Array,
        default: () => []
    },
    userRole: String,
    debugInfo: Object, // Remove in production
})

// Debug mode toggle
const showDebug = ref(false)
const isRefreshing = ref(false)

// Search and filter
const searchQuery = ref('')
const priorityFilter = ref('')

// Log data for debugging (remove in production)
onMounted(() => {
    if (import.meta.env.DEV) {
        console.log('Approval Index - Props:', {
            pendingReports: props.pendingReports,
            userRole: props.userRole,
            debugInfo: props.debugInfo
        })
    }
})

const filteredReports = computed(() => {
    if (!props.pendingReports) return []
    
    let filtered = props.pendingReports
    
    // Search filter
    if (searchQuery.value) {
        filtered = filtered.filter(report => 
            report.borrower?.name?.toLowerCase().includes(searchQuery.value.toLowerCase())
        )
    }
    
    // Priority filter
    if (priorityFilter.value) {
        filtered = filtered.filter(report => {
            const priority = getPriorityLabel(report).toLowerCase()
            return priority === priorityFilter.value
        })
    }
    
    return filtered
})

const urgentReports = computed(() => {
    if (!props.pendingReports) return 0
    
    return props.pendingReports.filter(report => {
        if (!report.submitted_at) return false
        const submitDate = new Date(report.submitted_at)
        const daysDiff = (new Date() - submitDate) / (1000 * 60 * 60 * 24)
        return daysDiff > 3
    }).length
})

const approvedToday = computed(() => {
    // This would need to be calculated from backend
    return 0
})

const averageProcessingDays = computed(() => {
    if (!props.pendingReports || props.pendingReports.length === 0) return 0
    
    const totalDays = props.pendingReports.reduce((sum, report) => {
        if (!report.submitted_at) return sum
        const submitDate = new Date(report.submitted_at)
        const daysDiff = (new Date() - submitDate) / (1000 * 60 * 60 * 24)
        return sum + daysDiff
    }, 0)
    
    return Math.round(totalDays / props.pendingReports.length)
})

const refreshData = () => {
    isRefreshing.value = true
    router.reload({ 
        only: ['pendingReports', 'debugInfo'],
        onFinish: () => {
            isRefreshing.value = false
        }
    })
}

const clearFilters = () => {
    searchQuery.value = ''
    priorityFilter.value = ''
}

const getRoleLabel = (role) => {
    const labels = {
        'super_admin': 'Super Admin',
        'unit_bisnis': 'Unit Bisnis',
        'risk_analyst': 'Risk Analyst',
        'kadept_bisnis': 'Kepala Departemen Bisnis',
        'kadept_risk': 'Kepala Departemen Risk'
    }
    return labels[role] || role
}

const getRoleDescription = (role) => {
    const descriptions = {
        'super_admin': 'Akses penuh ke semua fitur sistem',
        'unit_bisnis': 'Dapat membuat dan mengirim laporan',
        'risk_analyst': 'Dapat melakukan review dan approval tahap pertama',
        'kadept_bisnis': 'Dapat melakukan approval dan override',
        'kadept_risk': 'Dapat melakukan final approval dan override'
    }
    return descriptions[role] || 'Role tidak dikenal'
}

const getCurrentStepLabel = (report) => {
    if (!report.approvals || report.approvals.length === 0) {
        return 'Tidak ada tahap'
    }
    
    // ✅ FIX: Correct step labels (exclude Unit Bisnis)
    const stepLabels = {
        2: 'Risk Analyst',     // WorkflowStep::RISK_ANALYST = 2
        3: 'Kadept Bisnis',    // WorkflowStep::KADEPT_BISNIS = 3
        4: 'Kadept Risk'       // WorkflowStep::KADEPT_RISK = 4
    }
    
    const currentApproval = report.approvals.find(approval => approval.status === 'pending')
    const stepValue = currentApproval?.approval_step || report.approvals[0]?.approval_step
    
    return stepLabels[stepValue] || `Step ${stepValue}` || 'Unknown'
}

const getPriorityVariant = (report) => {
    if (!report.submitted_at) return 'secondary'
    
    const submitDate = new Date(report.submitted_at)
    const daysDiff = (new Date() - submitDate) / (1000 * 60 * 60 * 24)
    
    if (daysDiff > 7) return 'destructive'
    if (daysDiff > 3) return 'warning'
    return 'secondary'
}

const getPriorityLabel = (report) => {
    if (!report.submitted_at) return 'Normal'
    
    const submitDate = new Date(report.submitted_at)
    const daysDiff = (new Date() - submitDate) / (1000 * 60 * 60 * 24)
    
    if (daysDiff > 7) return 'Urgent'
    if (daysDiff > 3) return 'High'
    return 'Normal'
}

const formatDate = (date) => {
    if (!date) return 'N/A'
    try {
        return new Date(date).toLocaleDateString('id-ID')
    } catch (e) {
        return 'Invalid Date'
    }
}
</script>