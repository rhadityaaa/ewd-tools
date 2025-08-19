<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';

const toast = useToast();

const props = defineProps({
    role: Object,
});

const role = props.role?.data;

console.log('Editing role:', props.role?.data);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
    },
    {
        title: 'Role',
        href: route('roles.index'),
    },
    {
        title: `Edit - ${role.name}`,
        href: route('roles.edit', role.id),
    },
];

const form = useForm({
    code: role.code,
    name: role.name,
});

const submit = () => {
    form.put(route('roles.update', role.id), {
        onSuccess: () => {
            toast.success('Role berhasil diperbarui.');
        },
        onError: () => {
            toast.error('Gagal memperbarui Role. Silahkan periksa kembali data Anda.');
        },
    });
};
</script>

<template>
    <Head title="Edit Role" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-6 md:py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:max-w-7xl lg:px-8">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between">
                        <CardTitle class="text-xl font-bold md:text-2xl">Edit Role</CardTitle>
                    </CardHeader>
                    <form @submit.prevent="submit" class="space-y-6">
                        <CardContent class="space-y-6">
                            <div class="grid gap-2">
                                <Label for="name">Nama Role</Label>
                                <Input id="name" v-model="form.name" />
                                <InputError :message="form.errors.name" />
                            </div>
                        </CardContent>

                        <CardFooter class="flex items-center justify-end gap-4">
                            <Link :href="route('roles.index')">
                                <Button type="button" variant="outline">Batal</Button>
                            </Link>
                            <Button type="submit" :disabled="form.processing || !form.isDirty">Simpan Data</Button>
                        </CardFooter>
                    </form>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
