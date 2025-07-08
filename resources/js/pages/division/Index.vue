<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem, Division } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { EditIcon, EyeIcon, PlusIcon, Trash2Icon } from 'lucide-vue-next';

defineProps<{
    divisions: Division[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
    },
    {
        title: 'Divisi',
        href: route('divisions.index'),
    },
];
</script>

<template>
    <Head title="Divisi" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-6 md:py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:max-w-7xl lg:px-8">
                <Card>
                    <CardHeader class="flex items-center justify-between">
                        <CardTitle class="text-xl font-bold md:text-2xl">Daftar Divisi</CardTitle>
                        <Link :href="route('divisions.create')">
                            <Button>
                                <PlusIcon class="h-4 w-4" />
                                Tambah Divisi
                            </Button>
                        </Link>
                    </CardHeader>
                    <CardContent>
                        <div v-if="divisions.length === 0" class="py-8 text-center text-gray-500">
                            Belum ada divisi yang terdaftar. Silahkan tambahkan divisi baru.
                        </div>
                        <Table v-else>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Kode</TableHead>
                                    <TableHead>Nama</TableHead>
                                    <TableHead class="text-right"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="division in divisions" :key="division.id">
                                    <TableCell>{{ division.code }}</TableCell>
                                    <TableCell>{{ division.name }}</TableCell>
                                    <TableCell class="flex justify-end space-x-3 text-right">
                                        <Link
                                            :href="route('divisions.edit', division.id)"
                                            class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                            title="Edit Divisi"
                                        >
                                            <EditIcon class="h-5 w-5" />
                                        </Link>
                                        <Link
                                            :href="route('divisions.show', division.id)"
                                            class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300"
                                            title="View"
                                        >
                                            <EyeIcon class="h-5 w-5" />
                                        </Link>
                                        <Link
                                            :href="route('divisions.destroy', division.id)"
                                            method="delete"
                                            as="button"
                                            type="button"
                                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                            onclick="return confirm('Are you sure you want to delete this division?')"
                                            title="Delete"
                                        >
                                            <Trash2Icon class="h-5 w-5" />
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
