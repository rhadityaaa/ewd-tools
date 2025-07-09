<script setup lang="ts">
import DatePicker from '@/components/DatePicker.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem, Period } from '@/types'; // Import Period interface
import { Head, Link, useForm } from '@inertiajs/vue3';
// parseDate dari @internationalized/date hanya untuk tanggal (YYYY-MM-DD)
// Kita perlu parse tanggal dan waktu secara terpisah atau menggunakan Date objek standar
import { watch } from 'vue';
import { useToast } from 'vue-toastification';

const props = defineProps<{
    period: Period;
}>();

// Helper untuk memformat tanggal dan waktu dari string ISO 8601
const formatDatePart = (isoString: string | null): string => {
    if (!isoString) return '';
    try {
        const date = new Date(isoString);
        return date.toISOString().split('T')[0]; // YYYY-MM-DD
    } catch (e) {
        console.error('Error parsing date part:', e);
        return '';
    }
};

const formatTimePart = (isoString: string | null): string => {
    if (!isoString) return '';
    try {
        const date = new Date(isoString);
        // toISOString() menghasilkan "HH:mm:ss.sssZ" atau "HH:mm:ss.sss±HH:MM"
        // Kita hanya ingin HH:mm:ss
        return date.toTimeString().split(' ')[0]; // HH:mm:ss
    } catch (e) {
        console.error('Error parsing time part:', e);
        return '';
    }
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
    },
    {
        title: 'Periode',
        href: route('periods.index'),
    },
    {
        title: 'Edit Periode',
        href: route('periods.edit', props.period.id),
    },
];

// Inisialisasi form dengan memisahkan tanggal dan waktu
const form = useForm({
    name: props.period.name,
    start_date: formatDatePart(props.period.start_date),
    start_time: formatTimePart(props.period.start_date),
    end_date: formatDatePart(props.period.end_date),
    end_time: formatTimePart(props.period.end_date),
    status: props.period.status,
});

const toast = useToast();

// Watchers untuk DatePicker
// DatePicker akan mengembalikan string YYYY-MM-DD, jadi langsung tetapkan ke form
watch(
    () => form.start_date,
    (val) => {
        // Tidak perlu parsing ulang, DatePicker sudah memberikan format string yang benar
        // Jika perlu konversi ke objek Date, lakukan di sini:
        // form.start_date = val ? new Date(val) : '';
    },
);
watch(
    () => form.end_date,
    (val) => {
        // Tidak perlu parsing ulang
        // form.end_date = val ? new Date(val) : '';
    },
);

const submit = () => {
    console.log('Form data being sent for update:', form);
    form.put(route('periods.update', props.period.id), {
        onSuccess: () => {
            toast.success('Periode berhasil diperbarui');
        },
        onError: (errors) => {
            toast.error('Terjadi kesalahan saat memperbarui periode.');
            form.errors = errors;
            console.error('Form errors:', errors);
        },
    });
};
</script>

<template>
    <Head title="Edit Periode" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-6 md:py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:max-w-7xl lg:px-8">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-xl font-bold md:text-2xl">Edit Periode</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="grid gap-2">
                                <Label for="name">Nama Periode</Label>
                                <Input id="name" v-model="form.name" placeholder="Masukan nama periode" />
                                <InputError :message="form.errors.name" />
                            </div>

                            <div class="grid gap-2 md:grid-cols-2 md:gap-4">
                                <div>
                                    <Label for="start_date">Tanggal Mulai</Label>
                                    <DatePicker id="start_date" v-model="form.start_date" />
                                    <InputError :message="form.errors.start_date" />
                                </div>
                                <div>
                                    <Label for="start_time">Waktu Mulai</Label>
                                    <Input id="start_time" type="time" v-model="form.start_time" />
                                    <InputError :message="form.errors.start_time" />
                                </div>
                            </div>

                            <div class="grid gap-2 md:grid-cols-2 md:gap-4">
                                <div>
                                    <Label for="end_date">Tanggal Selesai</Label>
                                    <DatePicker id="end_date" v-model="form.end_date" />
                                    <InputError :message="form.errors.end_date" />
                                </div>
                                <div>
                                    <Label for="end_time">Waktu Selesai</Label>
                                    <Input id="end_time" type="time" v-model="form.end_time" />
                                    <InputError :message="form.errors.end_time" />
                                </div>
                            </div>

                            <div class="grid gap-2">
                                <Label for="status">Status</Label>
                                <select
                                    id="status"
                                    v-model="form.status"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="draft">Draft</option>
                                    <option value="active">Active</option>
                                    <option value="ended">Ended</option>
                                    <option value="expired">Expired</option>
                                </select>
                                <InputError :message="form.errors.status" />
                            </div>

                            <div class="flex justify-end space-x-2">
                                <Link :href="route('periods.index')">
                                    <Button type="button" variant="outline">Batal</Button>
                                </Link>
                                <Button type="submit" :disabled="form.processing">Simpan Perubahan</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
