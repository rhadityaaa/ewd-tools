<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem, Division } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Edit } from 'lucide-vue-next';

const props = defineProps<{
    division: Division;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
    },
    {
        title: 'Divisi',
        href: route('divisions.index'),
    },
    {
        title: props.division.code,
        href: route('divisions.show', props.division.id),
    },
];
</script>

<template>
    <Head title="Tambah Divisi" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-6 md:py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:max-w-7xl lg:px-8">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between">
                        <CardTitle class="text-xl font-bold md:text-2xl"> Divisi {{ division.code }} </CardTitle>
                        <div class="flex gap-2">
                            <Link :href="route('divisions.edit', division.id)">
                                <Button>
                                    <Edit class="h-4 w-4" />
                                    Edit Unit
                                </Button>
                            </Link>
                            <Link :href="route('divisions.index')">
                                <Button type="button" variant="outline">Kembali</Button>
                            </Link>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                            <div class="space-y-2">
                                <div class="text-sm text-gray-500">Kode Divisi</div>
                                <div class="flex items-center font-medium">
                                    {{ division.code }}
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="text-sm text-gray-500">Nama Divisi</div>
                                <div class="flex items-center font-medium">
                                    {{ division.name }}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
