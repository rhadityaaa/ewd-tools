<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle, Edit, XCircle } from 'lucide-vue-next';

const props = defineProps({
    aspect: Object,
});

const aspect = props.aspect?.data;

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
    },
    {
        title: 'Aspek',
        href: route('aspects.index'),
    },
    {
        title: aspect.name,
        href: route('aspects.show', aspect.id),
    },
];
</script>

<template>
    <Head :title="`${aspect.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-6 md:py-12">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:max-w-7xl lg:px-8">
                <!-- Detail Aspek -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle class="text-xl font-bold md:text-2xl">
                                {{ aspect.latest_version.name }}
                            </CardTitle>
                            <div class="mt-2 flex items-center gap-2">
                                <Badge variant="outline">{{ aspect.code }}</Badge>
                                <Badge variant="secondary">v{{ aspect.latest_version.version_number }}</Badge>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <Link :href="route('aspects.edit', aspect.id)">
                                <Button>
                                    <Edit class="h-4 w-4" />
                                    Edit Aspek
                                </Button>
                            </Link>
                            <Link :href="route('aspects.index')">
                                <Button type="button" variant="outline">Kembali</Button>
                            </Link>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-lg font-medium">Deskripsi</h3>
                                <p class="mt-1 text-gray-600">{{ aspect.latest_version.description || 'Tidak ada deskripsi' }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Daftar Pertanyaan -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-lg font-bold"> Daftar Pertanyaan ({{ aspect.latest_version.questions.length }}) </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="aspect.latest_version.questions.length === 0" class="py-8 text-center text-gray-500">
                            Belum ada pertanyaan untuk aspek ini.
                        </div>
                        <div v-else class="space-y-6">
                            <div
                                v-for="(question, index) in aspect.latest_version.questions"
                                :key="question.id"
                                class="rounded-lg border bg-gray-50 p-6 dark:bg-gray-800"
                            >
                                <div class="space-y-4">
                                    <!-- Question Header -->
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="mb-2 flex items-center gap-2">
                                                <h4 class="text-lg font-semibold">Pertanyaan {{ index + 1 }}</h4>
                                                <Badge v-if="question.is_mandatory" variant="destructive" class="text-xs">
                                                    <CheckCircle class="mr-1 h-3 w-3" />
                                                    Wajib
                                                </Badge>
                                                <Badge v-else variant="secondary" class="text-xs">
                                                    <XCircle class="mr-1 h-3 w-3" />
                                                    Opsional
                                                </Badge>
                                            </div>
                                            <p class="mb-3 text-gray-700 dark:text-gray-300">{{ question.question_text }}</p>
                                        </div>
                                    </div>

                                    <!-- Question Details -->
                                    <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-3">
                                        <div class="rounded border bg-white p-3 dark:bg-gray-700">
                                            <span class="font-medium text-gray-500">Bobot:</span>
                                            <span class="ml-2 font-semibold">{{ question.weight }}</span>
                                        </div>
                                        <div class="rounded border bg-white p-3 dark:bg-gray-700">
                                            <span class="font-medium text-gray-500">Skor Maksimal:</span>
                                            <span class="ml-2 font-semibold">{{ question.max_score }}</span>
                                        </div>
                                        <div class="rounded border bg-white p-3 dark:bg-gray-700">
                                            <span class="font-medium text-gray-500">Skor Minimal:</span>
                                            <span class="ml-2 font-semibold">{{ question.min_score }}</span>
                                        </div>
                                    </div>

                                    <!-- Question Options -->
                                    <div v-if="question.options && question.options.length > 0" class="mt-4">
                                        <h5 class="mb-3 font-medium text-gray-700 dark:text-gray-300">Pilihan Jawaban:</h5>
                                        <div class="grid gap-2">
                                            <div
                                                v-for="(option, optionIndex) in question.options"
                                                :key="option.id"
                                                class="flex items-center justify-between rounded border bg-white p-3 dark:bg-gray-700"
                                            >
                                                <div class="flex items-center">
                                                    <span
                                                        class="mr-3 flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                                                    >
                                                        {{ String.fromCharCode(65 + optionIndex) }}
                                                    </span>
                                                    <span>{{ option.option_text }}</span>
                                                </div>
                                                <Badge variant="outline" class="ml-2"> {{ option.score }} poin </Badge>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                    <CardFooter>
                        <div class="flex w-full justify-between">
                            <Link :href="route('aspects.index')">
                                <Button variant="outline">
                                    <ArrowLeft class="mr-2 h-4 w-4" />
                                    Kembali
                                </Button>
                            </Link>
                        </div>
                    </CardFooter>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
