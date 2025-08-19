<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem, User } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { EditIcon, EyeIcon, PlusIcon, Trash2Icon } from 'lucide-vue-next';
import { ref } from 'vue';
import { useToast } from 'vue-toastification';

const toast = useToast();

defineProps<{
    users: User[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
    },
    {
        title: 'Pengguna',
        href: route('users.index'),
    },
];

const isDeleteModalOpen = ref(false);
const userToDelete = ref<number | null>(null);

const openDeleteModal = (id: number) => {
    userToDelete.value = id;
    isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;
    userToDelete.value = null;
};

const handleDelete = (id: number) => {
    router.delete(route('users.destroy', id), {
        onSuccess: () => {
            toast.success('Pengguna berhasil dihapus');
        },
        onError: () => {
            toast.error('Gagal menghapus pengguna. Silahkan coba lagi.');
        },
        onFinish: () => {
            closeDeleteModal();
        },
    });
};
</script>

<template>
    <Head title="Pengguna" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-6 md:py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:max-w-7xl lg:px-8">
                <Card>
                    <CardHeader class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <CardTitle class="text-xl font-bold md:text-2xl">Daftar Pengguna</CardTitle>
                        <Link :href="route('users.create')">
                            <Button class="w-full sm:w-auto">
                                <PlusIcon class="h-4 w-4" />
                                Tambah Pengguna
                            </Button>
                        </Link>
                    </CardHeader>
                    <CardContent>
                        <div v-if="users.length === 0" class="py-8 text-center text-gray-500">
                            Belum ada pengguna yang terdaftar. Silahkan tambahkan pengguna baru.
                        </div>
                        <Table v-else class="w-full overflow-x-auto">
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama</TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead>Role</TableHead>
                                    <TableHead>Divisi</TableHead>
                                    <TableHead class="text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="user in users" :key="user.id">
                                    <TableCell class="whitespace-nowrap">{{ user.name }}</TableCell>
                                    <TableCell>{{ user.email }}</TableCell>
                                    <TableCell>{{ user.role?.name }}</TableCell>
                                    <TableCell>{{ user.division?.code }}</TableCell>
                                    <TableCell class="flex justify-end space-x-2 text-right">
                                        <Link
                                            :href="route('users.edit', user.id)"
                                            class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                            title="Edit Pengguna"
                                        >
                                            <EditIcon class="h-5 w-5" />
                                        </Link>
                                        <Link
                                            :href="route('users.show', user.id)"
                                            class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300"
                                            title="Lihat Pengguna"
                                        >
                                            <EyeIcon class="h-5 w-5" />
                                        </Link>
                                        <button
                                            @click="openDeleteModal(user.id)"
                                            method="delete"
                                            as="button"
                                            type="button"
                                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                            title="Hapus Pengguna"
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
    </AppLayout>

    <!-- Modal Delete -->
    <div v-if="isDeleteModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4">
        <Card class="w-full max-w-sm animate-in fade-in zoom-in">
            <CardHeader class="items-center text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900">
                    <Trash2Icon class="h-6 w-6 text-red-600 dark:text-red-400" />
                </div>
                <CardTitle class="text-xl">Konfirmasi Penghapusan</CardTitle>
            </CardHeader>
            <CardContent class="text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Apakah Anda yakin ingin menghapus data ini?<br />
                    Tindakan ini bersifat permanen dan tidak dapat dibatalkan.
                </p>
            </CardContent>
            <CardFooter class="flex flex-col-reverse gap-3 bg-gray-50 px-6 sm:flex-row sm:justify-end dark:bg-gray-900/50">
                <Button variant="outline" @click="closeDeleteModal"> Batal </Button>
                <Button variant="destructive" @click="userToDelete && handleDelete(userToDelete)"> Ya, Hapus </Button>
            </CardFooter>
        </Card>
    </div>
</template>
