<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem, Template } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { EditIcon, ArrowLeftIcon, CalendarIcon, UserIcon, WeightIcon } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    template: Template;
}>();

console.log(props.template)

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
        title: props.template.name,
        href: route('templates.show', props.template.id),
    },
];

const latestVersion = computed(() => props.template.latest_version);
const aspectVersions = computed(() => latestVersion.value?.aspect_versions || []);
const visibilityRules = computed(() => latestVersion.value?.visibility_rules || []);

const totalWeight = computed(() => {
    return aspectVersions.value.reduce((sum, av) => sum + (av.pivot?.weight || 0), 0);
});

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Head :title="`Detail Template - ${template.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-6 lg:py-8">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 lg:text-3xl">{{ template.name }}</h1>
                            <p class="mt-2 text-sm text-gray-600">
                                Template penilaian dengan {{ aspectVersions.length }} aspek
                            </p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <Badge :variant="totalWeight === 100 ? 'default' : 'destructive'" class="text-xs">
                                <WeightIcon class="mr-1 h-3 w-3" />
                                Total Bobot: {{ totalWeight.toFixed(1) }}%
                            </Badge>
                            <Link :href="route('templates.edit', template.id)">
                                <Button>
                                    <EditIcon class="mr-2 h-4 w-4" />
                                    Edit Template
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Template Information -->
                <Card class="mb-6">
                    <CardHeader>
                        <CardTitle class="flex items-center">
                            <UserIcon class="mr-2 h-5 w-5" />
                            Informasi Template
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-sm font-medium text-gray-500">Nama Template</label>
                                <p class="text-base font-medium">{{ template.name }}</p>
                            </div>
                            <div v-if="latestVersion">
                                <label class="text-sm font-medium text-gray-500">Versi</label>
                                <p class="text-base font-medium">{{ latestVersion.version_number }}</p>
                            </div>
                        </div>
                        <div v-if="latestVersion?.description">
                            <label class="text-sm font-medium text-gray-500">Deskripsi</label>
                            <p class="text-base">{{ latestVersion.description }}</p>
                        </div>
                        <div v-if="latestVersion?.effective_from">
                            <label class="text-sm font-medium text-gray-500">Berlaku Sejak</label>
                            <p class="flex items-center text-base">
                                <CalendarIcon class="mr-2 h-4 w-4" />
                                {{ formatDate(latestVersion.effective_from) }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Aspects -->
                <Card class="mb-6">
                    <CardHeader>
                        <CardTitle>Aspek Penilaian ({{ aspectVersions.length }})</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="aspectVersions.length === 0" class="py-8 text-center text-gray-500">
                            Tidak ada aspek yang terdaftar dalam template ini.
                        </div>
                        <Table v-else>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Kode Aspek</TableHead>
                                    <TableHead>Nama Aspek</TableHead>
                                    <TableHead>Versi</TableHead>
                                    <TableHead class="text-right">Bobot (%)</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="aspectVersion in aspectVersions" :key="aspectVersion.id">
                                    <TableCell class="font-mono text-sm">
                                        {{ aspectVersion.aspect?.code }}
                                    </TableCell>
                                    <TableCell class="font-medium">
                                        {{ aspectVersion.name }}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="outline">v{{ aspectVersion.version_number }}</Badge>
                                    </TableCell>
                                    <TableCell class="text-right font-medium">
                                        {{ aspectVersion.pivot?.weight || 0 }}%
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <!-- Visibility Rules -->
                <Card class="mb-6">
                    <CardHeader>
                        <CardTitle>Aturan Visibilitas ({{ visibilityRules.length }})</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="visibilityRules.length === 0" class="py-8 text-center text-gray-500">
                            Tidak ada aturan visibilitas. Template akan selalu ditampilkan.
                        </div>
                        <div v-else class="space-y-4">
                            <div v-for="(rule, index) in visibilityRules" :key="rule.id || index" class="rounded-lg border p-4">
                                <div class="mb-3">
                                    <h4 class="font-medium">Aturan {{ index + 1 }}</h4>
                                    <p v-if="rule.description" class="text-sm text-gray-600">{{ rule.description }}</p>
                                </div>
                                <div class="grid gap-2 text-sm md:grid-cols-4">
                                    <div>
                                        <span class="font-medium text-gray-500">Sumber:</span>
                                        <p>{{ rule.source_type }}</p>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-500">Field:</span>
                                        <p>{{ rule.source_field }}</p>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-500">Operator:</span>
                                        <p>{{ rule.operator }}</p>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-500">Nilai:</span>
                                        <p>{{ rule.value }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Actions -->
                <div class="flex justify-between">
                    <Link :href="route('templates.index')">
                        <Button variant="outline">
                            <ArrowLeftIcon class="mr-2 h-4 w-4" />
                            Kembali ke Daftar
                        </Button>
                    </Link>
                    <Link :href="route('templates.edit', template.id)">
                        <Button>
                            <EditIcon class="mr-2 h-4 w-4" />
                            Edit Template
                        </Button>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>