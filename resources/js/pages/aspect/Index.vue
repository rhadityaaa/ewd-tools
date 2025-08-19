<script setup lang="ts">
import { Badge } from '@/components/ui/badge/index';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Edit, Eye, Plus, Trash2 } from 'lucide-vue-next';

const props = defineProps({
    aspects: Object,
});

console.log(props.aspects);

const aspects = props.aspects?.data ?? [];

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
    },
    {
        title: 'Aspek',
        href: route('aspects.index'),
    },
];
</script>

<template>
    <Head title="Daftar Aspek" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-6 md:py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:max-w-7xl lg:px-8">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between">
                        <CardTitle class="text-xl font-bold md:text-2xl"> Daftar Aspek </CardTitle>
                        <Link :href="route('aspects.create')">
                            <Button>
                                <Plus class="h-4 w-4" />
                                Tambah Aspek
                            </Button>
                        </Link>
                    </CardHeader>
                    <CardContent>
                        <div v-if="aspects.length === 0" class="py-8 text-center text-gray-500">
                            Belum ada aspek yang tersedia. Silakan tambahkan aspek baru.
                        </div>
                        <Table v-else>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama Aspek</TableHead>
                                    <TableHead>Kode</TableHead>
                                    <TableHead>Versi</TableHead>
                                    <TableHead>Jumlah Pertanyaan</TableHead>
                                    <TableHead>Deskripsi</TableHead>
                                    <TableHead class="text-right"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="aspect in aspects" :key="aspect.id">
                                    <TableCell>{{ aspect.name }}</TableCell>
                                    <TableCell>
                                        <Badge variant="outline">{{ aspect.code }}</Badge>
                                    </TableCell>
                                    <TableCell>v{{ aspect.version_number }}</TableCell>
                                    <TableCell>{{ aspect.questions_count }}</TableCell>
                                    <TableCell class="max-w-xs truncate">{{ aspect.description || 'Tidak ada deskripsi' }}</TableCell>
                                    <TableCell class="flex justify-end space-x-3 text-right">
                                        <Link
                                            :href="route('aspects.show', aspect.id)"
                                            class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300"
                                            title="Lihat"
                                        >
                                            <Eye class="h-5 w-5" />
                                        </Link>
                                        <Link
                                            :href="route('aspects.edit', aspect.id)"
                                            class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                            title="Edit"
                                        >
                                            <Edit class="h-5 w-5" />
                                        </Link>
                                        <Link
                                            :href="route('aspects.destroy', aspect.id)"
                                            method="delete"
                                            as="button"
                                            type="button"
                                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                            onclick="return confirm('Apakah Anda yakin ingin menghapus aspek ini?')"
                                            title="Hapus"
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
