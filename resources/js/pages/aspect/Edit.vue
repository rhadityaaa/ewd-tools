<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { PlusCircle, Save, Trash2 } from 'lucide-vue-next';
import { useToast } from 'vue-toastification';

const toast = useToast();

const props = defineProps({
    aspect: Object,
});

const aspect = props.aspect?.data;

console.log('Aspect Data:', aspect);

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
        title: 'Edit Aspek',
        href: route('aspects.edit', aspect.id),
    },
];

const form = useForm({
    code: aspect.code,
    name: aspect.name,
    description: aspect.description,
    questions: aspect.latest_version.questions.map((q: any) => ({
        id: q.id,
        question_text: q.question_text,
        weight: q.weight,
        max_score: q.max_score,
        min_score: q.min_score,
        is_mandatory: q.is_mandatory,
        options: q.options.map((o: any) => ({
            id: o.id,
            option_text: o.option_text,
            score: o.score,
        })),
        visibility_rules:
            q.visibility_rules?.map((vr: any) => ({
                id: vr.id,
                description: vr.description,
                source_type: vr.source_type,
                source_field: vr.source_field,
                operator: vr.operator,
                value: vr.value,
            })) || [],
    })),
});

const sourceTypeOptions = [
    { value: 'borrower_detail', label: 'Detail Peminjam' },
    { value: 'borrower_facility', label: 'Fasilitas Peminjam' },
    { value: 'answer', label: 'Jawaban' },
];

const operatorOptions = [
    { value: '=', label: 'Sama dengan (=)' },
    { value: '!=', label: 'Tidak sama dengan (!=)' },
    { value: '>', label: 'Lebih besar dari (>)' },
    { value: '<', label: 'Lebih kecil dari (<)' },
    { value: '>=', label: 'Lebih besar atau sama dengan (>=)' },
    { value: '<=', label: 'Lebih kecil atau sama dengan (<=)' },
    { value: 'in', label: 'Termasuk dalam (in)' },
    { value: 'not_in', label: 'Tidak termasuk dalam (not in)' },
];

const addQuestion = () => {
    form.questions.push({
        id: null,
        question_text: '',
        weight: 1,
        max_score: 100,
        min_score: 0,
        is_mandatory: false,
        options: [{ id: null, option_text: '', score: 0 }],
        visibility_rules: [],
    });
};

const removeQuestion = (index: number) => {
    form.questions.splice(index, 1);
};

const addOption = (questionIndex: number) => {
    form.questions[questionIndex].options.push({
        id: null,
        option_text: '',
        score: 0,
    });
};

const removeOption = (questionIndex: number, optionIndex: number) => {
    form.questions[questionIndex].options.splice(optionIndex, 1);
};

const addVisibilityRule = (questionIndex: number) => {
    form.questions[questionIndex].visibility_rules.push({
        id: null,
        description: '',
        source_type: 'borrower_detail',
        source_field: '',
        operator: '=',
        value: '',
    });
};

const removeVisibilityRule = (questionIndex: number, ruleIndex: number) => {
    form.questions[questionIndex].visibility_rules.splice(ruleIndex, 1);
};

const submit = () => {
    const formData = {
        ...form.data(),
        questions: form.questions.map((q: any) => ({
            ...q,
            weight: Number(q.weight),
            max_score: Number(q.max_score),
            min_score: Number(q.min_score),
            options: q.options.map((o: any) => ({
                ...o,
                score: Number(o.score),
            })),
        })),
    };

    form.put(route('aspects.update', aspect.id), {
        preserveScroll: true,
        onSuccess: () => {
            console.log('Data berhasil diperbarui');
        },
        onError: (errors) => {
            console.error('Error saat memperbarui:', errors);
        },
    });
};
</script>

<template>
    <Head title="Edit Aspek" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-xl font-bold md:text-2xl">Edit Aspek</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-8">
                            <!-- Aspect Information -->
                            <div class="space-y-4 rounded-lg border p-4">
                                <h3 class="text-lg font-semibold">Informasi Aspek</h3>

                                <div class="space-y-2">
                                    <Label for="code" class="text-sm font-medium"> Kode Aspek <span class="text-red-500">*</span> </Label>
                                    <Input
                                        id="code"
                                        v-model="form.code"
                                        placeholder="Masukkan kode aspek"
                                        required
                                        class="transition-all duration-200 focus:ring-2"
                                    />
                                    <InputError :message="form.errors.code" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="name">Nama Aspek</Label>
                                    <Input id="name" v-model="form.name" required />
                                    <InputError :message="form.errors.name" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="description">Deskripsi</Label>
                                    <Textarea id="description" v-model="form.description" rows="3" />
                                    <InputError :message="form.errors.description" />
                                </div>
                            </div>

                            <!-- Questions -->
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-semibold">Pertanyaan</h3>
                                    <Button type="button" variant="outline" size="sm" @click="addQuestion">
                                        <PlusCircle class="mr-2 h-4 w-4" />
                                        Tambah Pertanyaan
                                    </Button>
                                </div>

                                <div v-if="form.errors.questions" class="text-sm text-red-500">
                                    {{ form.errors.questions }}
                                </div>

                                <div v-for="(question, questionIndex) in form.questions" :key="questionIndex" class="space-y-6 rounded-lg border p-6">
                                    <div class="flex items-center justify-between">
                                        <h4 class="font-medium">Pertanyaan {{ questionIndex + 1 }}</h4>
                                        <Button
                                            type="button"
                                            variant="destructive"
                                            size="sm"
                                            @click="removeQuestion(questionIndex)"
                                            v-if="form.questions.length > 1"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>

                                    <div class="grid gap-2">
                                        <Label :for="`question-text-${questionIndex}`">Teks Pertanyaan</Label>
                                        <Textarea :id="`question-text-${questionIndex}`" v-model="question.question_text" rows="2" required />
                                        <InputError :message="(form.errors as any)[`questions.${questionIndex}.question_text`]" />
                                    </div>

                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                        <div class="grid gap-2">
                                            <Label :for="`question-weight-${questionIndex}`">Bobot</Label>
                                            <Input
                                                :id="`question-weight-${questionIndex}`"
                                                v-model="question.weight"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                required
                                            />
                                            <InputError :message="(form.errors as any)[`questions.${questionIndex}.weight`]" />
                                        </div>

                                        <div class="grid gap-2">
                                            <Label :for="`question-max-score-${questionIndex}`">Skor Maksimal</Label>
                                            <Input
                                                :id="`question-max-score-${questionIndex}`"
                                                v-model="question.max_score"
                                                type="number"
                                                min="0"
                                                required
                                            />
                                            <InputError :message="(form.errors as any)[`questions.${questionIndex}.max_score`]" />
                                        </div>

                                        <div class="grid gap-2">
                                            <Label :for="`question-min-score-${questionIndex}`">Skor Minimal</Label>
                                            <Input
                                                :id="`question-min-score-${questionIndex}`"
                                                v-model="question.min_score"
                                                type="number"
                                                min="0"
                                                required
                                            />
                                            <InputError :message="(form.errors as any)[`questions.${questionIndex}.min_score`]" />
                                        </div>
                                    </div>

                                    <div class="flex items-center space-x-2">
                                        <Checkbox :id="`question-mandatory-${questionIndex}`" v-model="question.is_mandatory" />
                                        <Label :for="`question-mandatory-${questionIndex}`">Pertanyaan Wajib</Label>
                                    </div>

                                    <!-- Options -->
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between">
                                            <Label class="text-sm font-medium">Opsi Jawaban</Label>
                                            <Button type="button" variant="outline" size="sm" @click="addOption(questionIndex)">
                                                <PlusCircle class="mr-2 h-3 w-3" />
                                                Tambah Opsi
                                            </Button>
                                        </div>

                                        <div v-for="(option, optionIndex) in question.options" :key="optionIndex" class="flex items-center space-x-2">
                                            <div class="flex-1">
                                                <Input v-model="option.option_text" placeholder="Teks opsi" required />
                                            </div>
                                            <div class="w-24">
                                                <Input v-model="option.score" type="number" placeholder="Skor" min="0" required />
                                            </div>
                                            <Button
                                                type="button"
                                                variant="destructive"
                                                size="sm"
                                                @click="removeOption(questionIndex, optionIndex)"
                                                v-if="question.options.length > 1"
                                            >
                                                <Trash2 class="h-3 w-3" />
                                            </Button>
                                        </div>
                                    </div>

                                    <!-- Visibility Rules -->
                                    <div class="space-y-4 rounded-lg border border-dashed p-4">
                                        <div class="flex items-center justify-between">
                                            <Label class="text-sm font-medium">Aturan Visibilitas</Label>
                                            <Button type="button" variant="outline" size="sm" @click="addVisibilityRule(questionIndex)">
                                                <PlusCircle class="mr-2 h-3 w-3" />
                                                Tambah Aturan
                                            </Button>
                                        </div>

                                        <div v-if="question.visibility_rules.length === 0" class="py-4 text-center text-sm text-gray-500">
                                            Tidak ada aturan visibilitas. Pertanyaan akan selalu ditampilkan.
                                        </div>

                                        <div
                                            v-for="(rule, ruleIndex) in question.visibility_rules"
                                            :key="ruleIndex"
                                            class="space-y-3 rounded border bg-gray-50 p-3"
                                        >
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm font-medium">Aturan {{ ruleIndex + 1 }}</span>
                                                <Button
                                                    type="button"
                                                    variant="destructive"
                                                    size="sm"
                                                    @click="removeVisibilityRule(questionIndex, ruleIndex)"
                                                >
                                                    <Trash2 class="h-3 w-3" />
                                                </Button>
                                            </div>

                                            <div class="grid gap-3">
                                                <div class="grid gap-2">
                                                    <Label :for="`rule-description-${questionIndex}-${ruleIndex}`">Deskripsi</Label>
                                                    <Input
                                                        :id="`rule-description-${questionIndex}-${ruleIndex}`"
                                                        v-model="rule.description"
                                                        placeholder="Deskripsi aturan visibilitas"
                                                    />
                                                </div>

                                                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                                    <div class="grid gap-2">
                                                        <Label :for="`rule-source-type-${questionIndex}-${ruleIndex}`">Tipe Sumber</Label>
                                                        <Select v-model="rule.source_type">
                                                            <SelectTrigger :id="`rule-source-type-${questionIndex}-${ruleIndex}`">
                                                                <SelectValue placeholder="Pilih tipe sumber" />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                <SelectItem
                                                                    v-for="option in sourceTypeOptions"
                                                                    :key="option.value"
                                                                    :value="option.value"
                                                                >
                                                                    {{ option.label }}
                                                                </SelectItem>
                                                            </SelectContent>
                                                        </Select>
                                                    </div>

                                                    <div class="grid gap-2">
                                                        <Label :for="`rule-source-field-${questionIndex}-${ruleIndex}`">Field Sumber</Label>
                                                        <Input
                                                            :id="`rule-source-field-${questionIndex}-${ruleIndex}`"
                                                            v-model="rule.source_field"
                                                            placeholder="Nama field"
                                                            required
                                                        />
                                                    </div>
                                                </div>

                                                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                                    <div class="grid gap-2">
                                                        <Label :for="`rule-operator-${questionIndex}-${ruleIndex}`">Operator</Label>
                                                        <Select v-model="rule.operator">
                                                            <SelectTrigger :id="`rule-operator-${questionIndex}-${ruleIndex}`">
                                                                <SelectValue placeholder="Pilih operator" />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                <SelectItem
                                                                    v-for="option in operatorOptions"
                                                                    :key="option.value"
                                                                    :value="option.value"
                                                                >
                                                                    {{ option.label }}
                                                                </SelectItem>
                                                            </SelectContent>
                                                        </Select>
                                                    </div>

                                                    <div class="grid gap-2">
                                                        <Label :for="`rule-value-${questionIndex}-${ruleIndex}`">Nilai</Label>
                                                        <Input
                                                            :id="`rule-value-${questionIndex}-${ruleIndex}`"
                                                            v-model="rule.value"
                                                            placeholder="Nilai untuk perbandingan"
                                                            required
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end space-x-2">
                                <Link :href="route('aspects.index')">
                                    <Button type="button" variant="outline"> Batal </Button>
                                </Link>
                                <Button type="submit" :disabled="form.processing">
                                    <Save class="mr-2 h-4 w-4" />
                                    Simpan Perubahan
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
