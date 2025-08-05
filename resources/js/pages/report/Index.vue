<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { BreadcrumbItem, Paginated, Report } from '@/types'

defineProps<{
    reports: Paginated<Report>;
}>();
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
                                    <TableHead>Klasifikasi Akhir</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="report in reports.data" :key="report.id">
                                    <TableCell>{{ report.borrower?.name }}</TableCell>
                                    <TableCell>{{ report.period?.name }}</TableCell>
                                    <TableCell class="capitalize">{{ report.summary?.final_classification }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>