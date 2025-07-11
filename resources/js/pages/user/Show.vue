<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, User } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Building, Edit, Mail, Shield, UserIcon } from 'lucide-vue-next';

const props = defineProps<{
    user: User;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
    },
    {
        title: 'Users',
        href: route('users.index'),
    },
    {
        title: props.user.name,
        href: route('users.show', props.user.id),
    },
];
</script>

<template>
    <Head title="Detail User" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-6 md:py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:max-w-7xl lg:px-8">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between">
                        <CardTitle class="text-xl font-bold md:text-2xl">
                            {{ user.name }}
                        </CardTitle>
                        <div class="flex gap-2">
                            <Link :href="route('users.edit', user.id)">
                                <Button>
                                    <Edit class="h-4 w-4" />
                                    Edit User
                                </Button>
                            </Link>
                            <Link :href="route('users.index')">
                                <Button type="button" variant="outline">Kembali</Button>
                            </Link>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <UserIcon class="h-4 w-4 text-gray-500" />
                                    <span class="text-sm font-medium text-gray-500">Nama User</span>
                                </div>
                                <p class="text-lg font-medium">{{ user.name }}</p>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <Mail class="h-4 w-4 text-gray-500" />
                                    <span class="text-sm font-medium text-gray-500">Email</span>
                                </div>
                                <p class="text-lg font-medium">{{ user.email }}</p>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <Shield class="h-4 w-4 text-gray-500" />
                                    <span class="text-sm font-medium text-gray-500">Role</span>
                                </div>
                                <Badge v-if="user.role_id" variant="secondary" class="text-sm">
                                    {{ user.role.name }}
                                </Badge>
                                <span v-else class="text-sm text-gray-500">N/A</span>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <Building class="h-4 w-4 text-gray-500" />
                                    <span class="text-sm font-medium text-gray-500">Divisi</span>
                                </div>
                                <Badge v-if="user.division_id" variant="outline" class="text-sm">
                                    {{ user.division.code }} - {{ user.division.name }}
                                </Badge>
                                <span v-else class="text-sm text-gray-500">N/A</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
