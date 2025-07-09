<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { PencilIcon } from 'lucide-vue-next';

const props = defineProps({
    role: Object,
});

const role = props.role?.data;

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
    },
    {
        title: 'Divisi',
        href: route('roles.index'),
    },
    {
        title: role.code,
        href: route('roles.show', role.id),
    },
];
</script>

<template>
    <Head title="Tambah Divisi" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-6 md:py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:max-w-7xl lg:px-8">
                <Card>
                    <CardHeader class="flex items-center justify-between">
                        <CardTitle class="text-xl font-bold md:text-2xl">Divisi {{ role.code }}</CardTitle>
                        <Link :href="route('roles.edit', role.id)">
                            <Button>
                                <PencilIcon class="h-4 w-4" />
                                Edit Unit
                            </Button>
                        </Link>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                            <div class="space-y-2">
                                <div class="text-sm text-gray-500">Nama Divisi</div>
                                <div class="flex items-center font-medium">
                                    {{ role.name }}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
