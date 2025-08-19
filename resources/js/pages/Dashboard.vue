<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';

const page = usePage();
const activePeriod = page.props.activePeriod;

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
    },
];

const stats = ref({
    totalReports: 0,
    watchlistReports: 0,
    pendingNAW: 0,
    completedNAW: 0
});

const recentWatchlistReports = ref([]);

onMounted(async () => {
    try {
        // Fetch dashboard stats
        const response = await axios.get('/api/dashboard/stats');
        stats.value = response.data;
        
        // Fetch recent watchlist reports
        const watchlistResponse = await axios.get('/api/dashboard/recent-watchlist');
        recentWatchlistReports.value = watchlistResponse.data;
    } catch (error) {
        console.error('Error fetching dashboard data:', error);
    }
});

const openNAW = (reportId: number) => {
    window.open(`/watchlist?reportId=${reportId}`, '_blank');
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <!-- Periode Aktif -->
            <div v-if="activePeriod" class="rounded-xl border border-sidebar-border/70 bg-card p-6 text-card-foreground dark:border-sidebar-border">
                <h2 class="mb-2 text-xl font-semibold">Periode Penilaian Aktif</h2>
                <p class="text-muted-foreground text-lg">{{ activePeriod.name }}</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid auto-rows-min gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-gray-600">Total Laporan</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.totalReports }}</div>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-gray-600">Watchlist</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">{{ stats.watchlistReports }}</div>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-gray-600">NAW Pending</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-orange-600">{{ stats.pendingNAW }}</div>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-gray-600">NAW Selesai</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">{{ stats.completedNAW }}</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Watchlist Reports -->
            <Card class="flex-1">
                <CardHeader>
                    <CardTitle class="text-lg font-semibold">Laporan Watchlist Terbaru</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="recentWatchlistReports.length === 0" class="py-8 text-center text-gray-500">
                        Tidak ada laporan watchlist terbaru.
                    </div>
                    <div v-else class="space-y-4">
                        <div 
                            v-for="report in recentWatchlistReports" 
                            :key="report.id"
                            class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50"
                        >
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900">{{ report.borrower?.name }}</h3>
                                <p class="text-sm text-gray-600">{{ report.period?.name }}</p>
                                <div class="flex items-center space-x-2 mt-2">
                                    <Badge class="bg-red-100 text-red-800">WATCHLIST</Badge>
                                    <Badge 
                                        :class="report.naw_status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800'"
                                    >
                                        NAW {{ report.naw_status === 'completed' ? 'Selesai' : 'Pending' }}
                                    </Badge>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <Button 
                                    @click="openNAW(report.id)" 
                                    variant="outline" 
                                    size="sm"
                                    class="text-orange-600 hover:text-orange-700"
                                >
                                    📝 {{ report.naw_status === 'completed' ? 'Lihat NAW' : 'Lengkapi NAW' }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
