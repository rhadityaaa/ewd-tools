<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select/index';
import AppLayout from '@/layouts/AppLayout.vue';
import { Division, type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';

const toast = useToast();

const props = defineProps({
    borrower: Object,
    divisions: Array<Division>,
});

const borrower = props.borrower?.data;

const divisions = props.divisions;

console.log('Borrower:', borrower);
console.log('Divisions:', divisions);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
    },
    {
        title: 'Debitur',
        href: route('borrowers.index'),
    },
    {
        title: 'Edit',
        href: route('borrowers.edit', borrower.id),
    },
];

const form = useForm({
    name: borrower.name,
    division_id: borrower.division.id.toString(),
});

const submit = () => {
    form.put(route('borrowers.update', borrower.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Debitur berhasil diperbarui.');
        },
        onError: () => {
            toast.error('Gagal memperbarui debitur. Silakan periksa kembali data Anda.');
        },
        onFinish: () => {
            resetForm();
        },
    });
};

const resetForm = () => {
    form.reset();
};
</script>

<template>
    <Head title="Edit Borrower" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-6 md:py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:max-w-7xl lg:px-8">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-xl font-bold md:text-2xl">Edit Debitur</CardTitle>
                        <Link :href="route('borrowers.index')">
                            <Button type="button" variant="outline">Kembali</Button>
                        </Link>
                    </CardHeader>
                    <form @submit.prevent="submit" class="space-y-6">
                        <CardContent class="space-y-6">
                            <div class="grid gap-2">
                                <Label for="name">Nama Debitur</Label>
                                <Input id="name" v-model="form.name" required autocomplete="name" />
                                <InputError :message="form.errors.name" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="division_id">Divisi</Label>
                                <Select v-model="form.division_id">
                                    <SelectTrigger class="w-full">
                                        <SelectValue placeholder="Pilih divisi" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-if="divisions"
                                            v-for="division in props.divisions"
                                            :key="division.id"
                                            :value="division.id.toString()"
                                        >
                                            {{ division.code }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.division_id" />
                            </div>
                        </CardContent>

                        <CardFooter class="flex items-center justify-end gap-4">
                            <Button type="button" variant="outline" @click="resetForm" v-if="form.isDirty">Reset</Button>
                            <Button type="submit" :disabled="form.processing || !form.isDirty">Update</Button>
                        </CardFooter>
                    </form>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
