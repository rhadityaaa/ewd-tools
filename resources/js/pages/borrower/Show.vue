<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Edit } from 'lucide-vue-next';

const props = defineProps({
    borrower: Object,
});

const borrower = props.borrower?.data;

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
        title: borrower.name,
        href: route('borrowers.show', borrower.id),
    },
];
</script>

<template>
    <Head title="Detail Debitur" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-6 md:py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:max-w-7xl lg:px-8">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-xl font-bold md:text-2xl">Detail Debitur</CardTitle>
                        <div class="flex gap-2">
                            <Link :href="route('borrowers.edit', borrower.id)">
                                <Button>
                                    <Edit class="h-4 w-4" />
                                    Edit Debitur
                                </Button>
                            </Link>
                            <Link :href="route('borrowers.index')">
                                <Button type="button" variant="outline">Kembali</Button>
                            </Link>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Nama Debitur</h3>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ borrower.name }}</p>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Divisi</h3>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ borrower.division?.name }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
