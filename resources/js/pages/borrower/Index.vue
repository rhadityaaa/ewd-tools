<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Borrower, BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Edit, Eye, PlusIcon, Trash2 } from 'lucide-vue-next';

defineProps<{
    borrowers: Borrower[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
    },
    {
        title: 'Debitur',
        href: route('borrowers.index'),
    },
];
</script>

<template>
    <Head title="Daftar Debitur" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-6 md:py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:max-w-7xl lg:px-8">
                <Card>
                    <CardHeader class="flex items-center justify-between">
                        <CardTitle class="text-xl font-bold md:text-2xl">Daftar Debitur</CardTitle>
                        <Link :href="route('borrowers.create')">
                            <Button>
                                <PlusIcon class="h-4 w-4" />
                                Tambah Debitur
                            </Button>
                        </Link>
                    </CardHeader>
                    <CardContent>
                        <div v-if="borrowers.length === 0" class="py-8 text-center text-gray-500">
                            Belum ada debitur yang terdaftar. Silakan tambahkan debitur baru.
                        </div>
                        <Table v-else>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama Debitur</TableHead>
                                    <TableHead>Divisi</TableHead>
                                    <TableHead class="text-right"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="borrower in borrowers" :key="borrower.id">
                                    <TableCell>{{ borrower.name }}</TableCell>
                                    <TableCell>{{ borrower.division?.code }}</TableCell>
                                    <TableCell class="flex justify-end space-x-3 text-right">
                                        <Link
                                            :href="route('borrowers.edit', borrower.id)"
                                            class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                            title="Edit"
                                        >
                                            <Edit class="h-5 w-5" />
                                        </Link>
                                        <Link
                                            :href="route('borrowers.show', borrower.id)"
                                            class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300"
                                            title="View"
                                        >
                                            <Eye class="h-5 w-5" />
                                        </Link>
                                        <Link
                                            :href="route('borrowers.destroy', borrower.id)"
                                            method="delete"
                                            as="button"
                                            type="button"
                                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                            onclick="return confirm('Are you sure you want to delete this user?')"
                                            title="Delete"
                                        >
                                            <Trash2 class="h-5 w-5" />
                                        </Link>
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
