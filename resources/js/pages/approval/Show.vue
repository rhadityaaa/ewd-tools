<template>
    <AppLayout title="Detail Persetujuan">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Detail Persetujuan - {{ report.borrower?.name || 'N/A' }}
                </h2>
                <Link :href="route('approvals.index')" class="text-blue-600 hover:text-blue-800">
                    <Button variant="outline">
                        <ArrowLeftIcon class="h-4 w-4 mr-2" />
                        Kembali
                    </Button>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Debug Info (Remove in production) -->
                <Card v-if="debugMode" class="border-yellow-200 bg-yellow-50">
                    <CardHeader>
                        <CardTitle class="text-yellow-800">Debug Info</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-sm space-y-1">
                            <p><strong>User Role:</strong> {{ userRole || 'No role' }}</p>
                            <p><strong>Can Approve (Backend):</strong> {{ canApprove }}</p>
                            <p><strong>Can Override (Backend):</strong> {{ canOverride }}</p>
                            <p><strong>Current User ID:</strong> {{ user?.id }}</p>
                            <p><strong>Current Step:</strong> {{ getCurrentStepInfo() }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Report Info -->
                <Card>
                    <CardHeader>
                        <CardTitle>Informasi Laporan</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-gray-600">Debitur</label>
                                <p class="text-lg font-semibold">{{ report.borrower?.name || 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600">Divisi</label>
                                <p class="text-lg">{{ report.borrower?.division?.name || 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600">Tanggal Submit</label>
                                <p class="text-lg">{{ formatDate(report.submitted_at) }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600">Status</label>
                                <Badge :variant="getStatusVariant(report.status)">{{ getStatusLabel(report.status) }}</Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Workflow Progress -->
                <Card>
                    <CardHeader>
                        <CardTitle>Progress Persetujuan</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="!workflowSteps || workflowSteps.length === 0" class="text-center py-4 text-gray-500">
                            Tidak ada workflow steps
                        </div>
                        <div v-else class="space-y-4">
                            <!-- ✅ FIX: Proper workflow display WITHOUT Unit Bisnis -->
                            <div v-for="(step, index) in workflowSteps" :key="step.step" class="flex items-center">
                                <div class="flex items-center">
                                    <div :class="[
                                        'w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium',
                                        step.status === 'approved' ? 'bg-green-100 text-green-800' :
                                        step.is_current ? 'bg-yellow-100 text-yellow-800' :
                                        'bg-gray-100 text-gray-500'
                                    ]">
                                        <CheckIcon v-if="step.status === 'approved'" class="h-4 w-4" />
                                        <ClockIcon v-else-if="step.is_current" class="h-4 w-4" />
                                        <!-- ✅ FIX: Use step_number instead of index + 1 -->
                                        <span v-else>{{ step.step_number || (index + 1) }}</span>
                                    </div>
                                    <div class="ml-4">
                                        <p class="font-medium">{{ step.step_name }}</p>
                                        <p v-if="step.assigned_to" class="text-sm text-gray-600">{{ step.assigned_to.name }}</p>
                                        <p v-if="step.assigned_at" class="text-xs text-gray-500">{{ formatDate(step.assigned_at) }}</p>
                                    </div>
                                </div>
                                <div v-if="index < workflowSteps.length - 1" class="flex-1 h-px bg-gray-200 mx-4"></div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- ✅ NEW: Unit Bisnis Information (Read-only) -->
                <Card v-if="userRole === 'unit_bisnis'">
                    <CardHeader>
                        <CardTitle>Informasi untuk Unit Bisnis</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <InfoIcon class="h-5 w-5 text-blue-600 mr-2" />
                                <div>
                                    <p class="text-sm font-medium text-blue-800">Laporan Telah Disubmit</p>
                                    <p class="text-xs text-blue-600 mt-1">
                                        Laporan Anda sedang dalam proses review oleh tim approval. 
                                        Anda akan mendapat notifikasi jika ada update.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Approval Actions (Only for approvers, NOT Unit Bisnis) -->
                <Card v-if="showApprovalActions && userRole !== 'unit_bisnis'">
                    <CardHeader>
                        <CardTitle>Tindakan Persetujuan</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="!canApprove && !canOverride" class="text-center py-4 text-gray-500">
                            <p>Anda tidak memiliki akses untuk melakukan tindakan pada laporan ini.</p>
                            <p class="text-sm mt-1">Role: {{ userRole || 'Tidak ada role' }}</p>
                        </div>
                        
                        <form v-else @submit.prevent="submitApproval">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Komentar</label>
                                    <textarea 
                                        v-model="form.comments" 
                                        rows="4" 
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="Tambahkan komentar (opsional)"
                                    ></textarea>
                                </div>
                                
                                <div class="flex space-x-3">
                                    <Button 
                                        v-if="canApprove"
                                        type="button" 
                                        @click="approve" 
                                        class="bg-green-600 hover:bg-green-700"
                                        :disabled="form.processing"
                                    >
                                        <CheckIcon class="h-4 w-4 mr-2" />
                                        Setujui
                                    </Button>
                                    
                                    <Button 
                                        v-if="canApprove"
                                        type="button" 
                                        @click="reject" 
                                        variant="destructive"
                                        :disabled="form.processing"
                                    >
                                        <XIcon class="h-4 w-4 mr-2" />
                                        Tolak
                                    </Button>
                                    
                                    <Button 
                                        v-if="canApprove"
                                        type="button" 
                                        @click="requestRevision" 
                                        variant="outline"
                                        :disabled="form.processing"
                                    >
                                        <EditIcon class="h-4 w-4 mr-2" />
                                        Minta Revisi
                                    </Button>
                                    
                                    <Button 
                                        v-if="canOverride" 
                                        type="button" 
                                        @click="override" 
                                        variant="secondary"
                                        :disabled="form.processing"
                                    >
                                        <ShieldIcon class="h-4 w-4 mr-2" />
                                        Override
                                    </Button>
                                </div>
                                
                                <!-- Action Descriptions -->
                                <div class="text-xs text-gray-500 space-y-1">
                                    <p v-if="canApprove">✓ Anda dapat menyetujui, menolak, atau meminta revisi pada tahap ini</p>
                                    <p v-if="canOverride">⚡ Anda dapat meng-override seluruh proses approval</p>
                                </div>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <!-- Approval History -->
                <Card>
                    <CardHeader>
                        <CardTitle>Riwayat Persetujuan</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="!approvalHistory || approvalHistory.length === 0" class="text-center py-4 text-gray-500">
                            Belum ada riwayat persetujuan
                        </div>
                        <div v-else class="space-y-4">
                            <div v-for="history in approvalHistory" :key="history.id" class="border-l-4 border-blue-200 pl-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium">{{ history.user?.name || 'Unknown User' }}</p>
                                        <p class="text-sm text-gray-600">{{ getActionLabel(history.action) }}</p>
                                        <p v-if="history.comments" class="text-sm text-gray-700 mt-1">{{ history.comments }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm text-gray-500">{{ formatDateTime(history.action_at) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Link } from '@inertiajs/vue3'
import { 
    ArrowLeftIcon,
    CheckIcon,
    ClockIcon,
    XIcon,
    EditIcon,
    ShieldIcon
} from 'lucide-vue-next'
import { InfoIcon } from 'lucide-vue-next'

const props = defineProps({
    report: {
        type: Object,
        required: true
    },
    workflowSteps: {
        type: Array,
        default: () => []
    },
    approvalHistory: {
        type: Array,
        default: () => []
    },
    canApprove: {
        type: Boolean,
        default: false
    },
    canOverride: {
        type: Boolean,
        default: false
    },
    userRole: {
        type: String,
        default: null
    },
})

const page = usePage()
const user = page.props.auth.user
const debugMode = ref(true) // ✅ Set to true for debugging

const form = useForm({
    comments: ''
})

// Use backend-provided permissions instead of computing locally
// ✅ FIX: Only show approval actions for actual approvers
const showApprovalActions = computed(() => {
    // Unit Bisnis should NOT see approval actions
    if (props.userRole === 'unit_bisnis') {
        return false
    }
    return props.canApprove || props.canOverride
})

const getCurrentStepInfo = () => {
    const currentStep = props.workflowSteps?.find(step => step.is_current)
    if (!currentStep) return 'No current step'
    return `${currentStep.step_name} (assigned to: ${currentStep.assigned_to?.name || 'N/A'})`
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

const approve = () => {
    if (!props.canApprove) {
        alert('Anda tidak memiliki akses untuk menyetujui laporan ini.')
        return
    }
    
    form.post(route('approvals.approve', props.report.id), {
        onSuccess: () => {
            form.reset()
        },
        onError: (errors) => {
            console.error('Approval error:', errors)
        }
    })
}

const reject = () => {
    if (!props.canApprove) {
        alert('Anda tidak memiliki akses untuk menolak laporan ini.')
        return
    }
    
    if (confirm('Apakah Anda yakin ingin menolak laporan ini?')) {
        form.post(route('approvals.reject', props.report.id), {
            onSuccess: () => {
                form.reset()
            },
            onError: (errors) => {
                console.error('Rejection error:', errors)
            }
        })
    }
}

const requestRevision = () => {
    if (!props.canApprove) {
        alert('Anda tidak memiliki akses untuk meminta revisi.')
        return
    }
    
    if (confirm('Apakah Anda yakin ingin meminta revisi untuk laporan ini?')) {
        form.post(route('approvals.revision', props.report.id), {
            onSuccess: () => {
                form.reset()
            },
            onError: (errors) => {
                console.error('Revision request error:', errors)
            }
        })
    }
}

const override = () => {
    if (!props.canOverride) {
        alert('Anda tidak memiliki akses untuk meng-override approval.')
        return
    }
    
    if (confirm('Apakah Anda yakin ingin meng-override persetujuan laporan ini? Tindakan ini akan langsung menyetujui laporan.')) {
        form.post(route('approvals.override', props.report.id), {
            onSuccess: () => {
                form.reset()
            },
            onError: (errors) => {
                console.error('Override error:', errors)
            }
        })
    }
}

const getStatusVariant = (status) => {
    const variants = {
        'approved': 'success',
        'rejected': 'destructive',
        'under_review': 'warning',
        'submitted': 'secondary',
        'revision_required': 'warning'
    }
    return variants[status] || 'secondary'
}

const getStatusLabel = (status) => {
    const labels = {
        'approved': 'Disetujui',
        'rejected': 'Ditolak',
        'under_review': 'Dalam Review',
        'submitted': 'Disubmit',
        'revision_required': 'Perlu Revisi'
    }
    return labels[status] || status
}

const getActionLabel = (action) => {
    const labels = {
        'submit': 'Mengirim laporan',
        'approve': 'Menyetujui laporan',
        'reject': 'Menolak laporan',
        'revision': 'Meminta revisi',
        'override': 'Meng-override persetujuan'
    }
    return labels[action] || action
}

const formatDate = (date) => {
    if (!date) return 'N/A'
    try {
        return new Date(date).toLocaleDateString('id-ID')
    } catch (e) {
        return 'Invalid Date'
    }
}

const formatDateTime = (date) => {
    if (!date) return 'N/A'
    try {
        return new Date(date).toLocaleString('id-ID')
    } catch (e) {
        return 'Invalid Date'
    }
}

// ✅ Add debug computed
const debugInfo = computed(() => {
    return {
        canApprove: props.canApprove,
        canOverride: props.canOverride,
        showApprovalActions: showApprovalActions.value,
        userRole: props.userRole,
        userId: user?.id,
        reportId: props.report?.id,
        workflowStepsCount: props.workflowSteps?.length || 0,
        approvalHistoryCount: props.approvalHistory?.length || 0,
    }
})
</script>