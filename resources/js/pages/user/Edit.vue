<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem, Division, Role } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';

const toast = useToast();

const props = defineProps({
    user: Object,
    roles: Array<Role>,
    divisions: Array<Division>,
});

const user = props.user;

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
    },
    {
        title: 'Pengguna',
        href: route('users.index'),
    },
    {
        title: 'Edit Pengguna',
        href: route('users.edit', user?.id),
    },
];

const form = useForm({
    name: user?.name,
    email: user?.email,
    password: '',
    password_confirmation: '',
    role_id: user?.role_id,
    division_id: user?.division_id,
});

const submit = () => {
    form.put(route('users.update', user?.id), {
        onSuccess: () => {
            toast.success('Pengguna berhasil diperbarui');
        },
        onError: () => {
            toast.error('Gagal memperbarui pengguna. Silakan periksa kembali data Anda.');
        },
    });
};

const resetForm = () => {
    form.reset();
};
</script>

<template>
    <Head title="Edit Pengguna" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-6 md:py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:max-w-7xl lg:px-8">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between">
                        <CardTitle class="text-xl font-bold md:text-2xl">Edit Pengguna</CardTitle>
                        <Link :href="route('users.index')">
                            <Button type="button" variant="outline">Kembali</Button>
                        </Link>
                    </CardHeader>
                    <form @submit.prevent="submit" class="space-y-6">
                        <CardContent class="space-y-6">
                            <div class="grid gap-2">
                                <Label for="name">Nama</Label>
                                <Input id="name" v-model="form.name" type="text" placeholder="Masukkan nama pengguna" required />
                                <InputError :message="form.errors.name" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="email">Email</Label>
                                <Input id="email" v-model="form.email" type="email" placeholder="Masukkan email pengguna" required />
                                <InputError :message="form.errors.email" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="password">Password</Label>
                                <Input id="password" v-model="form.password" type="password" placeholder="Masukkan password pengguna" />
                                <InputError :message="form.errors.password" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="password_confirmation">Konfirmasi Password</Label>
                                <Input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    type="password"
                                    placeholder="Konfirmasi password pengguna"
                                />
                                <InputError :message="form.errors.password_confirmation" class="mt-2" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="role">Role</Label>
                                <Select v-model="form.role_id">
                                    <SelectTrigger class="w-full">
                                        <SelectValue placeholder="Pilih role pengguna" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-if="roles" v-for="role in roles" :key="role.id" :value="role.id">
                                            {{ role.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="grid gap-2">
                                <Label for="divisi">Divisi</Label>
                                <Select v-model="form.division_id">
                                    <SelectTrigger class="w-full">
                                        <SelectValue placeholder="Pilih divisi pengguna" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-if="divisions" v-for="division in divisions" :key="division.id" :value="division.id">
                                            {{ division.code }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </CardContent>

                        <CardFooter class="flex justify-end space-x-3">
                            <Button type="button" variant="outline" @click.prevent="resetForm" v-if="form.isDirty">Reset</Button>
                            <Button type="submit" :disabled="form.processing || !form.isDirty">Simpan Data</Button>
                        </CardFooter>
                    </form>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
