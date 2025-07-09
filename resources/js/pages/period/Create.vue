<script setup lang="ts">
import DatePicker from '@/components/DatePicker.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';

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
        title: 'Buat Periode',
        href: route('periods.create'),
    },
];

const form = useForm({
    name: '',
    start_date: '',
    start_time: '',
    end_date: '',
    end_time: '',
    status: 'draft',
});

const toast = useToast();

const submit = () => {
    console.log(form);
    form.post(route('periods.store'), {
        onSuccess: () => {
            toast.success('Periode berhasil dibuat');
            form.reset();
        },
        onError: (errors) => {
            toast.error('Terjadi kesalahan saat membuat periode.');
            form.errors = errors;
        },
    });
};
</script>

<template>
    <Head title="Buat Periode Baru" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-6 md:py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:max-w-7xl lg:px-8">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-xl font-bold md:text-2xl">Buat Periode Baru</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="grid gap-2">
                                <Label for="name">Nama Periode</Label>
                                <Input id="name" v-model="form.name" placeholder="Masukan nama periode" />
                                <InputError :message="form.errors.name" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="start_date">Tanggal Mulai</Label>
                                <DatePicker id="start_date" v-model="form.start_date" />
                                <InputError :message="form.errors.start_date" />
                            </div>
                            <div>
                                <Label for="start_time">Waktu Mulai</Label>
                                <Input id="start_time" type="time" v-model="form.start_time" />
                                <InputError :message="form.errors.start_time" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="end_date">Tanggal Selesai</Label>
                                <DatePicker id="end_date" v-model="form.end_date" />
                                <InputError :message="form.errors.end_date" />
                            </div>
                            <div>
                                <Label for="end_time">Waktu Selesai</Label>
                                <Input id="end_time" type="time" v-model="form.end_time" />
                                <InputError :message="form.errors.end_time" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="status">Status</Label>
                                <Input id="status" v-model="form.status" placeholder="draft" disabled />
                                <InputError :message="form.errors.status" />
                            </div>

                            <div class="flex justify-end gap-2">
                                <Link :href="route('periods.index')">
                                    <Button variant="outline">Batal</Button>
                                </Link>
                                <Button type="submit" :disabled="form.processing">
                                    {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
