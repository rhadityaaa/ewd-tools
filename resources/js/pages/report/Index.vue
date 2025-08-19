<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { BreadcrumbItem, Paginated, Report } from '@/types'
import { EditIcon, EyeIcon } from 'lucide-vue-next'

defineProps<{
    reports: Paginated<Report>;
}>();

const page = usePage()

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
    },
    {
        title: 'Laporan',
        href: route('reports.index'),
    },
]

// Check if current user is unit_bisnis
const isUnitBisnis = computed(() => {
    const user = page.props.auth?.user
    return user?.roles?.some(role => role.name === 'unit_bisnis') || false
})

const getStatusBadge = (status: number) => {
    switch (status) {
        case 1: // DRAFT
            return 'bg-gray-100 text-gray-800';
        case 2: // SUBMITTED
            return 'bg-blue-100 text-blue-800';
        case 3: // UNDER_REVIEW
            return 'bg-yellow-100 text-yellow-800';
        case 4: // APPROVED
            return 'bg-green-100 text-green-800';
        case 5: // REJECTED
            return 'bg-red-100 text-red-800';
        case 6: // REVISION_REQUIRED
            return 'bg-orange-100 text-orange-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const getStatusText = (status: number) => {
    switch (status) {
        case 1:
            return 'Draft';
        case 2:
            return 'Submitted';
        case 3:
            return 'Under Review';
        case 4:
            return 'Approved';
        case 5:
            return 'Rejected';
        case 6:
            return 'Revision Required';
        default:
            return 'Unknown';
    }
};

const getClassificationBadge = (classification: string) => {
    switch (classification?.toUpperCase()) {
        case 'WATCHLIST':
            return 'bg-red-100 text-red-800';
        case 'SAFE':
            return 'bg-green-100 text-green-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

// Check if report can be edited by current user
const canEditReport = (report: Report) => {
    const user = page.props.auth?.user
    if (!user || !isUnitBisnis.value) return false
    if (report.created_by !== user.id) return false
    // Allow editing for SUBMITTED/PENDING, REJECTED, or REVISION_REQUIRED
    return report.status === 2 || report.status === 5 || report.status === 6
}

const openSummary = (reportId: number) => {
    window.open(`/summary?reportId=${reportId}`, '_blank');
};

const openNAW = (reportId: number) => {
    window.open(`/watchlist?reportId=${reportId}`, '_blank');
};
</script>

<template>
    <Head title="Laporan" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-6 md:py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:max-w-7xl lg:px-8">
                <Card>
                    <CardHeader class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <CardTitle class="text-xl font-bold md:text-2xl">Daftar Laporan</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="reports.data.length === 0" class="py-8 text-center text-gray-500">
                            Belum ada laporan yang terdaftar. Silahkan tambahkan laporan baru.
                        </div>
                        <Table v-else class="w-full overflow-x-auto">
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Debitur</TableHead>
                                    <TableHead>Periode</TableHead>
                                    <TableHead>Status Laporan</TableHead>
                                    <TableHead>Klasifikasi Akhir</TableHead>
                                    <TableHead>Status NAW</TableHead>
                                    <TableHead>Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="report in reports.data" :key="report.id">
                                    <TableCell class="font-medium">{{ report.borrower?.name }}</TableCell>
                                    <TableCell>{{ report.period?.name }}</TableCell>
                                    <TableCell>
                                        <Badge :class="getStatusBadge(report.status)">
                                            {{ getStatusText(report.status) }}
                                        </Badge>
                                        <!-- Rejection reason for rejected reports -->
                                        <div v-if="(report.status === 5 || report.status === 6) && report.rejection_reason" class="mt-1">
                                            <p class="text-xs text-red-600 truncate max-w-xs" :title="report.rejection_reason">
                                                {{ report.rejection_reason }}
                                            </p>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :class="getClassificationBadge(report.summary?.final_classification)">
                                            {{ report.summary?.final_classification?.toUpperCase() || 'PENDING' }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div v-if="report.summary?.final_classification?.toUpperCase() === 'WATCHLIST'">
                                            <Badge class="bg-orange-100 text-orange-800">
                                                NAW Diperlukan
                                            </Badge>
                                        </div>
                                        <div v-else>
                                            <Badge class="bg-gray-100 text-gray-600">
                                                NAW Tidak Diperlukan
                                            </Badge>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex flex-wrap gap-2">
                                            <!-- View Button -->
                                            <Link :href="route('reports.show', report.id)">
                                                <Button 
                                                    variant="outline" 
                                                    size="sm"
                                                    class="text-blue-600 hover:text-blue-700"
                                                >
                                                    <EyeIcon class="h-3 w-3 mr-1" />
                                                    Lihat
                                                </Button>
                                            </Link>
                                            
                                            <!-- Edit Button for Rejected/Revision Required Reports -->
                                            <Link 
                                                v-if="canEditReport(report)"
                                                :href="route('reports.edit', report.id)"
                                            >
                                                <Button 
                                                    variant="outline" 
                                                    size="sm"
                                                    class="text-yellow-600 hover:text-yellow-700"
                                                >
                                                    <EditIcon class="h-3 w-3 mr-1" />
                                                    Edit
                                                </Button>
                                            </Link>
                                            
                                            <!-- Summary Button -->
                                            <Button 
                                                @click="openSummary(report.id)" 
                                                variant="outline" 
                                                size="sm"
                                                class="text-green-600 hover:text-green-700"
                                            >
                                                📊 Summary
                                            </Button>
                                            
                                            <!-- NAW Button -->
                                            <Button 
                                                v-if="report.summary?.final_classification?.toUpperCase() === 'WATCHLIST'"
                                                @click="openNAW(report.id)" 
                                                variant="outline" 
                                                size="sm"
                                                class="text-orange-600 hover:text-orange-700"
                                            >
                                                📝 NAW
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>