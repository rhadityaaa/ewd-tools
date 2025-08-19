<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ClockIcon, PlayIcon, PlusIcon, StopCircle, Trash2Icon } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useToast } from 'vue-toastification';

const toast = useToast();

const props = defineProps<{
    periods: any;
}>();

console.log(props.periods);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
    },
    {
        title: 'Periode',
        href: route('periods.index'),
    },
];

const isDeleteModalOpen = ref(false);
const periodToDelete = ref<number | null>(null);
const currentTime = ref(new Date());
const timerInterval = ref<number | null>(null);
const isStatusUpdateLoading = ref(false);

onMounted(() => {
    timerInterval.value = window.setInterval(() => {
        currentTime.value = new Date();
    }, 1000);
});

onBeforeUnmount(() => {
    if (timerInterval.value) {
        clearInterval(timerInterval.value);
    }
});

const latestPeriod = computed(() => {
    if (!props.periods || props.periods.length === 0) return null;

    return [...props.periods]
        .filter((period) => period.status && period.start_date && period.end_date)
        .sort((a, b) => {
            const aIsActive = a.status === 'active';
            const bIsActive = b.status === 'active';

            if (aIsActive && !bIsActive) {
                return -1;
            }
            if (!aIsActive && bIsActive) {
                return 1;
            }

            const dateA = new Date(a.start_date);
            const dateB = new Date(b.start_date);
            return dateB.getTime() - dateA.getTime();
        })[0];
});

const remainingTime = computed(() => {
    if (!latestPeriod.value || !latestPeriod.value.end_date) return null;

    const endDate = new Date(latestPeriod.value.end_date);
    const now = currentTime.value;

    const endDateUTC = Date.UTC(endDate.getFullYear(), endDate.getMonth(), endDate.getDate());
    const nowUTC = Date.UTC(now.getFullYear(), now.getMonth(), now.getDate());

    const diffDays = Math.floor((endDateUTC - nowUTC) / (1000 * 60 * 60 * 24));

    const diffMs = endDate.getTime() - now.getTime();
    const remainingMs = diffMs % (1000 * 60 * 60 * 24);
    const diffHours = Math.floor(remainingMs / (1000 * 60 * 60));
    const diffMinutes = Math.floor((remainingMs % (1000 * 60 * 60)) / (1000 * 60));
    const diffSeconds = Math.floor(((remainingMs % (1000 * 60 * 60)) % (1000 * 60)) / 1000);

    if (latestPeriod.value.status === 'draft') {
        return {
            status: 'draft',
            message: 'Periode masih dalam tahap draft',
        };
    }

    if (latestPeriod.value.status === 'ended') {
        return {
            status: 'ended',
            message: 'Waktu telah dihentikan admin',
        };
    }

    if (isNaN(endDate.getTime()) || isNaN(now.getTime())) {
        return {
            status: 'invalid',
            message: 'Tanggal periode tidak valid',
        };
    }

    if (endDate < now) {
        return {
            status: 'expired',
            message: `Periode telah selesai (${Math.abs(diffDays)} hari lalu)`,
        };
    }

    if (diffDays < 0) {
        return {
            status: 'expired',
            message: `Periode telah selesai (${Math.abs(diffDays)} hari lalu)`,
        };
    }

    return {
        status: 'active',
        days: diffDays,
        hours: diffHours,
        minutes: diffMinutes,
        seconds: diffSeconds,
    };
});

const getStatusBadgeClass = (status: string) => {
    switch (status) {
        case 'draft':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        case 'active':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'ended':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        case 'expired':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
};

const formatDate = (dateString: string | null): string => {
    if (!dateString) return '-';

    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const openDeleteModal = (id: number) => {
    periodToDelete.value = id;
    isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;
    periodToDelete.value = null;
};

const handleDelete = (id: number) => {
    router.delete(route('periods.destroy', id), {
        onSuccess: () => {
            toast.success('Periode berhasil dihapus');
            closeDeleteModal();
        },
        onError: () => {
            toast.error('Gagal menghapus periode. Silahkan coba lagi.');
            closeDeleteModal();
        },
    });
};

const startPeriod = (id: number) => {
    isStatusUpdateLoading.value = true;
    router.post(
        route('periods.start', id),
        {},
        {
            onSuccess: () => {
                toast.success('Periode berhasil dimulai');
                isStatusUpdateLoading.value = false;
            },
            onError: () => {
                toast.error('Gagal memulai periode. Silahkan coba lagi.');
                isStatusUpdateLoading.value = false;
            },
        },
    );
};

const endPeriod = (id: number) => {
    isStatusUpdateLoading.value = true;
    router.post(
        route('periods.stop', id),
        {},
        {
            onSuccess: () => {
                toast.success('Periode berhasil diakhiri');
                isStatusUpdateLoading.value = false;
            },
            onError: () => {
                toast.error('Gagal mengakhiri periode. Silakan coba lagi.');
                isStatusUpdateLoading.value = false;
            },
        },
    );
};
</script>

<template>
    <Head title="Daftar Periode" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-6 md:py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:max-w-7xl lg:px-8">
                <Card class="mb-6">
                    <CardContent class="p-6">
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div class="flex items-center">
                                <ClockIcon class="mr-6 h-10 w-10 text-blue-500" />
                                <div>
                                    <div class="font-regular text-xl">
                                        Periode Aktif:
                                        <h3 class="text-lg font-semibold">{{ latestPeriod.name }}</h3>
                                    </div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatDate(latestPeriod.start_date?.toString()) }} - {{ formatDate(latestPeriod.end_date?.toString()) }}
                                    </p>
                                    <div class="mt-1">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="getStatusBadgeClass(latestPeriod.status)"
                                            >{{ latestPeriod.status }}</span
                                        >
                                    </div>
                                </div>
                            </div>

                            <div v-if="remainingTime" class="flex flex-col space-y-4">
                                <div v-if="remainingTime.status === 'active'" class="flex justify-center space-x-4 md:justify-end">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold">{{ remainingTime.days }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Hari</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold">{{ remainingTime.hours }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Jam</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold">{{ remainingTime.minutes }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Menit</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold">{{ remainingTime.seconds }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Detik</div>
                                    </div>
                                </div>

                                <div v-else>
                                    <div class="text-center">
                                        <div class="text-lg font-medium text-gray-700 dark:text-gray-300">
                                            {{ remainingTime.message }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-center space-x-2 md:justify-end">
                                    <Button
                                        v-if="latestPeriod.status === 'draft'"
                                        class="bg-green-600 hover:bg-green-700"
                                        @click="startPeriod(latestPeriod.id)"
                                        :disabled="isStatusUpdateLoading"
                                    >
                                        <PlayIcon class="mr-2 h-4 w-4" />
                                        Mulai
                                    </Button>
                                    <Button
                                        v-if="latestPeriod.status === 'active'"
                                        variant="destructive"
                                        @click="endPeriod(latestPeriod.id)"
                                        :disabled="isStatusUpdateLoading"
                                    >
                                        <StopCircle class="mr-2 h-4 w-4" />
                                        Akhiri
                                    </Button>
                                    <Button v-if="['ended', 'expired'].includes(latestPeriod.status)" variant="outline" disabled>
                                        Periode Selesai
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-xl font-bold md:text-2xl">Periode</CardTitle>
                        <Link :href="route('periods.create')">
                            <Button>
                                <PlusIcon class="mr-2 h-4 w-4" />
                                Buat Periode Baru
                            </Button>
                        </Link>
                    </CardHeader>
                    <CardContent>
                        <div v-if="periods.length === 0" class="py-8 text-center text-gray-500">
                            Belum ada periode yang terdaftar. Silakan mulai periode baru.
                        </div>
                        <Table v-else>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Periode</TableHead>
                                    <TableHead>Tanggal Mulai</TableHead>
                                    <TableHead>Tanggal Selesai</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead class="text-right"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="period in periods" :key="period.id">
                                    <TableCell>{{ period.name }}</TableCell>
                                    <TableCell>{{ formatDate(period.start_date?.toString()) }}</TableCell>
                                    <TableCell>{{ formatDate(period.end_date?.toString()) }}</TableCell>
                                    <TableCell>
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="getStatusBadgeClass(period.status)"
                                            >{{ period.status }}</span
                                        >
                                    </TableCell>
                                    <TableCell class="flex justify-end space-x-3 text-right">
                                        <Link
                                            :href="route('periods.edit', period.id)"
                                            class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                            title="Edit"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="h-5 w-5"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 0L11.828 15.1l-3.414.586.586-3.414 9.586-9.586z"
                                                />
                                            </svg>
                                        </Link>
                                        <Link
                                            :href="route('periods.show', period.id)"
                                            class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300"
                                            title="View"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="h-5 w-5"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                                />
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                                />
                                            </svg>
                                        </Link>
                                        <button
                                            @click="openDeleteModal(period.id)"
                                            method="delete"
                                            as="button"
                                            type="button"
                                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                            title="Delete"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="h-5 w-5"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                />
                                            </svg>
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

    <div v-if="isDeleteModalOpen" class="bg-opacity-50 fixed inset-0 z-50 flex items-center justify-center bg-black p-4">
        <div class="w-full max-w-md animate-in rounded-lg bg-white p-6 shadow-lg fade-in zoom-in dark:bg-gray-800">
            <div class="mb-4 flex flex-col items-center justify-center">
                <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900">
                    <Trash2Icon class="h-6 w-6 text-red-600 dark:text-red-400" />
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Konfirmasi Penghapusan</h3>
                <p class="mt-2 text-center text-sm text-gray-500 dark:text-gray-400">
                    Apakah Anda yakin ingin menghapus periode ini? <br />Tindakan ini tidak dapat dibatalkan.
                </p>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <Button variant="outline" @click="closeDeleteModal"> Batal </Button>
                <Button variant="destructive" @click="periodToDelete && handleDelete(periodToDelete)"> Hapus </Button>
            </div>
        </div>
    </div>
</template>
