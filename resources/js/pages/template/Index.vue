<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem, Template } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { EditIcon, EyeIcon, PlusIcon, Trash2Icon } from 'lucide-vue-next';
import { ref } from 'vue';
import { useToast } from 'vue-toastification';

const toast = useToast();

defineProps<{
    templates: Template[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
    },
    {
        title: 'Template',
        href: route('templates.index'),
    },
];

const isDeleteModalOpen = ref(false);
const templateToDelete = ref<Template | null>(null);

const openDeleteModal = (template: Template) => {
    templateToDelete.value = template;
    isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;
    templateToDelete.value = null;
};

const handleDelete = () => {
    if (!templateToDelete.value) return;
    
    router.delete(route('templates.destroy', templateToDelete.value.id), {
        onSuccess: () => {
            toast.success('Template berhasil dihapus');
            closeDeleteModal();
        },
        onError: () => {
            toast.error('Gagal menghapus template. Silahkan coba lagi.');
        },
    });
};
</script>

<template>
    <Head title="Daftar Template" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-6 md:py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:max-w-7xl lg:px-8">
                <Card>
                    <CardHeader class="flex items-center justify-between">
                        <CardTitle class="text-xl font-bold md:text-2xl">Daftar Template</CardTitle>
                        <Link :href="route('templates.create')">
                            <Button>
                                <PlusIcon class="h-4 w-4" />
                                Tambah Template
                            </Button>
                        </Link>
                    </CardHeader>
                    <CardContent>
                        <div v-if="templates.length === 0" class="py-8 text-center text-gray-500">
                            Belum ada template yang terdaftar. Silakan tambahkan template baru.
                        </div>
                        <Table v-else>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama Template</TableHead>
                                    <TableHead class="text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="template in templates" :key="template.id">
                                    <TableCell>{{ template.name }}</TableCell>
                                    <TableCell class="flex justify-end space-x-3 text-right">
                                        <Link
                                            :href="route('templates.show', template.id)"
                                            class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300"
                                            title="Lihat Detail"
                                        >
                                            <EyeIcon class="h-5 w-5" />
                                        </Link>
                                        <Link
                                            :href="route('templates.edit', template.id)"
                                            class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                            title="Edit"
                                        >
                                            <EditIcon class="h-5 w-5" />
                                        </Link>
                                        <button
                                            @click="openDeleteModal(template)"
                                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                            title="Hapus"
                                        >
                                            <Trash2Icon class="h-5 w-5" />
                                        </button>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="isDeleteModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="mx-4 w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                <h3 class="mb-4 text-lg font-semibold">Konfirmasi Hapus</h3>
                <p class="mb-6 text-gray-600">
                    Apakah Anda yakin ingin menghapus template <strong>{{ templateToDelete?.name }}</strong>?
                    Tindakan ini tidak dapat dibatalkan.
                </p>
                <div class="flex justify-end space-x-3">
                    <Button variant="outline" @click="closeDeleteModal">Batal</Button>
                    <Button variant="destructive" @click="handleDelete">Hapus</Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
