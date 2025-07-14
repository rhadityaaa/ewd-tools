<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Aspect, BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Info } from 'lucide-vue-next';
import { useToast } from 'vue-toastification';

const toast = useToast();

const props = defineProps<{
    aspects: Aspect[];
}>();

console.log(props.aspects);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
    },
    {
        title: 'Template',
        href: route('templates.index'),
    },
    {
        title: 'Tambah Template',
        href: route('templates.create'),
    },
];

const form = useForm({
    name: '',
    description: '',
    aspect_ids: [] as number[],
});

form.transform((data) => ({
    ...data,
    aspect_ids: data.aspect_ids.map((id) => Number(id)),
}));

const handleAspectSelection = (aspectId: number, checked: boolean): void => {
    console.log(`Checkbox unit ${aspectId} is now ${checked}`);
};
</script>

<template>
    <Head title="Tambah Template" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-6 md:py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:max-w-7xl lg:px-8">
                <!-- Header Section -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 lg:text-3xl">Tambah Template Baru</h1>
                            <p class="mt-2 text-sm text-gray-600">Buat template penilaian baru dengan pemilihan ganda aspek dan aturan visibilitas</p>
                        </div>
                    </div>
                </div>
                <form @submit.prevent class="space-y-8">
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between">
                            <CardTitle class="flex items-center text-lg">
                                <Info class="mr-2 h-5 w-5" />
                                Informasi Dasar
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <div class="grid gap-2">
                                <Label for="name" class="text-sm font-medium">Nama Template</Label>
                                <Input id="name" v-model="form.name" placeholder="Masukan nama template" />
                                <InputError :message="form.errors.name" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="description">Deskripsi</Label>
                                <Textarea id="description" v-model="form.description" placeholder="Masukan deskripsi template" />
                                <InputError :message="form.errors.description" />
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Aspect Section -->
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <CardTitle class="flex items-center text-lg"> Aspek Template </CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div v-if="props.aspects.length > 0">
                                    <div class="divide-y rounded-md border">
                                        <div v-for="aspect in props.aspects" :key="aspect.id">
                                            <!-- <Checkbox
                                                :id="`aspect-${aspect.id}`
                                                :modelValue -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
