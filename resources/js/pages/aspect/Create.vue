<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription } from '@/components/ui/alert/index';
import { Badge } from '@/components/ui/badge/index';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea/index';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AlertCircle, HelpCircle, Info, PlusCircle, Save, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';
import { useToast } from 'vue-toastification';

const toast = useToast();

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
        title: 'Tambah Aspek',
        href: route('aspects.create'),
    },
];

const form = useForm({
    code: '',
    name: '',
    description: '',
    questions: [
        {
            question_text: '',
            weight: 100,
            max_score: 100,
            min_score: 0,
            is_mandatory: false,
            options: [
                {
                    option_text: '',
                    score: 0,
                },
            ],
            visibility_rules: [
                {
                    description: '',
                    source_type: '',
                    source_field: '',
                    operator: '',
                    value: '',
                },
            ],
        },
    ],
});

const sourceTypeOptions = [
    {
        value: 'borrower_detail',
        label: 'Informasi Debitur',
    },
    {
        value: 'borrower_facility',
        label: 'Fasilitas Debitur',
    },
    {
        value: 'answer',
        label: 'Jawaban',
    },
];

// Map source types to their corresponding field options
const sourceFieldOptionsMap = {
    borrower_detail: [
        { value: 'borrower_group', label: 'Grup Debitur' },
        { value: 'purpose', label: 'Tujuan Penggunaan' },
        { value: 'economic_sector', label: 'Sektor Ekonomi' },
        { value: 'business_field', label: 'Bidang Usaha' },
        { value: 'borrower_business', label: 'Jenis Usaha Debitur' },
        { value: 'collectibility', label: 'Kolektibilitas' },
        { value: 'restructuring', label: 'Restrukturisasi' },
    ],
    borrower_facility: [
        { value: 'facility_name', label: 'Nama Fasilitas' },
        { value: 'limit', label: 'Limit' },
        { value: 'outstanding', label: 'Outstanding' },
        { value: 'interest_rate', label: 'Suku Bunga' },
        { value: 'principal_arrears', label: 'Tunggakan Pokok' },
        { value: 'interest_arrears', label: 'Tunggakan Bunga' },
        { value: 'pdo_days', label: 'Hari PDO' },
        { value: 'maturity_date', label: 'Tanggal Jatuh Tempo' },
    ],
};

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

// Function to get the correct source field options based on source type and question context
const getSourceFieldOptions = (sourceType: string, currentQuestionIndex: number) => {
    if (sourceType === 'borrower_detail' || sourceType === 'borrower_facility') {
        return sourceFieldOptionsMap[sourceType] || [];
    }
    if (sourceType === 'answer') {
        // Return other questions as options, excluding the current one
        return form.questions
            .map((q, index) => ({
                // The backend will need to resolve this index to the actual question_id upon saving
                value: `question_id_${index}`,
                label: q.question_text.trim() ? `Jawaban untuk: "${q.question_text.substring(0, 30)}..."` : `Jawaban Pertanyaan ${index + 1}`,
            }))
            .filter((_, index) => index !== currentQuestionIndex);
    }
    return [];
};

const totalWeight = computed(() => {
    return form.questions.reduce((sum, q) => sum + Number(q.weight), 0);
});

const isWeightValid = computed(() => {
    return Math.abs(totalWeight.value - 100) <= 0.01;
});

const weightStatus = computed(() => {
    if (totalWeight.value < 100) return 'kurang';
    if (totalWeight.value > 100) return 'lebih';
    return 'sesuai';
});

const addQuestion = () => {
    form.questions.push({
        question_text: '',
        weight: 0, // Default to 0 to encourage manual adjustment
        max_score: 100,
        min_score: 0,
        is_mandatory: false,
        options: [{ option_text: '', score: 0 }],
        visibility_rules: [],
    });
};

const addOption = (questionIndex: number) => {
    form.questions[questionIndex].options.push({
        option_text: '',
        score: 0,
    });
};

const removeOption = (questionIndex: number, optionIndex: number) => {
    if (form.questions[questionIndex].options.length > 1) {
        form.questions[questionIndex].options.splice(optionIndex, 1);
    }
};

const removeQuestion = (index: number) => {
    if (form.questions.length > 1) {
        form.questions.splice(index, 1);
    }
};

const addVisibilityRule = (questionIndex: number) => {
    form.questions[questionIndex].visibility_rules.push({
        description: '',
        source_type: '',
        source_field: '',
        operator: '=',
        value: '',
    });
};

const removeVisibilityRule = (questionIndex: number, ruleIndex: number) => {
    form.questions[questionIndex].visibility_rules.splice(ruleIndex, 1);
};

const submit = () => {
    // Validate total weight
    if (!isWeightValid.value) {
        toast.error('Total bobot pertanyaan harus 100%');
        return;
    }

    // Clean up empty options
    form.questions.forEach((question) => {
        question.options = question.options.filter((option) => option.option_text.trim() !== '');
    });

    form.post(route('aspects.store'), {
        onSuccess: () => {
            toast.success('Aspek berhasil dibuat!');
        },
        onError: (errors) => {
            console.error('Submission Error:', errors);
            toast.error('Terjadi kesalahan. Periksa kembali isian form Anda.');
        },
    });
};
</script>

<template>
    <Head title="Buat Aspek" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-6 lg:py-8">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 lg:text-3xl">Tambah Aspek Baru</h1>
                            <p class="mt-2 text-sm text-gray-600">Buat aspek penilaian baru dengan pertanyaan dan aturan visibilitas</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <Badge :variant="isWeightValid ? 'default' : 'destructive'" class="text-xs">
                                Total Bobot: {{ totalWeight.toFixed(1) }}%
                            </Badge>
                        </div>
                    </div>
                </div>

                <Alert v-if="!isWeightValid" class="mb-6" variant="destructive">
                    <AlertCircle class="h-4 w-4" />
                    <AlertDescription>
                        Total bobot pertanyaan {{ weightStatus }} dari 100%. Sisa {{ Math.abs(100 - totalWeight).toFixed(1) }}%
                        {{ weightStatus === 'kurang' ? 'lagi' : 'berlebih' }}.
                    </AlertDescription>
                </Alert>

                <form @submit.prevent="submit" class="space-y-8">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center text-lg">
                                <Info class="mr-2 h-5 w-5" />
                                Informasi Dasar
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <div class="grid gap-4 md:grid-cols-2">
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
                                <div class="space-y-2">
                                    <Label for="name" class="text-sm font-medium"> Nama Aspek <span class="text-red-500">*</span> </Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        placeholder="Masukkan nama aspek"
                                        required
                                        class="transition-all duration-200 focus:ring-2"
                                    />
                                    <InputError :message="form.errors.name" />
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <Label for="description" class="text-sm font-medium"> Deskripsi </Label>
                                    <Textarea
                                        id="description"
                                        v-model="form.description"
                                        placeholder="Deskripsi singkat tentang aspek ini"
                                        rows="3"
                                        class="transition-all duration-200 focus:ring-2"
                                    />
                                    <InputError :message="form.errors.description" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <CardTitle class="flex items-center text-lg">
                                    <HelpCircle class="mr-2 h-5 w-5" />
                                    Pertanyaan ({{ form.questions.length }})
                                </CardTitle>
                                <Button
                                    type="button"
                                    @click="addQuestion"
                                    variant="outline"
                                    size="sm"
                                    class="transition-all duration-200 hover:scale-105"
                                >
                                    <PlusCircle class="mr-2 h-4 w-4" />
                                    Tambah Pertanyaan
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-6">
                                <div
                                    v-for="(question, qIndex) in form.questions"
                                    :key="qIndex"
                                    class="group relative rounded-lg border-2 border-dashed border-gray-200 p-6 transition-all duration-200 hover:border-gray-300 hover:bg-gray-50/50"
                                >
                                    <div class="mb-6 flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <Badge variant="outline" class="text-xs"> Pertanyaan {{ qIndex + 1 }} </Badge>
                                            <Badge v-if="question.is_mandatory" variant="destructive" class="text-xs"> Wajib </Badge>
                                            <Badge variant="secondary" class="text-xs"> Bobot: {{ question.weight }}% </Badge>
                                        </div>
                                        <Button
                                            v-if="form.questions.length > 1"
                                            type="button"
                                            @click="removeQuestion(qIndex)"
                                            variant="destructive"
                                            size="icon"
                                            class="h-7 w-7 opacity-0 transition-all duration-200 group-hover:opacity-100"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>

                                    <div class="mb-6 space-y-2">
                                        <Label class="text-sm font-medium"> Teks Pertanyaan <span class="text-red-500">*</span> </Label>
                                        <Textarea
                                            v-model="question.question_text"
                                            placeholder="Masukkan pertanyaan yang akan ditampilkan"
                                            rows="3"
                                            required
                                            class="transition-all duration-200 focus:ring-2"
                                        />
                                        <InputError :message="(form.errors as any)[`questions.${qIndex}.question_text`]" />
                                    </div>

                                    <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-4">
                                        <div class="space-y-2">
                                            <Label class="text-sm font-medium">Bobot (%)</Label>
                                            <Input
                                                type="number"
                                                v-model.number="question.weight"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                placeholder="0"
                                                class="transition-all duration-200 focus:ring-2"
                                            />
                                            <InputError :message="(form.errors as any)[`questions.${qIndex}.weight`]" />
                                        </div>
                                        <div class="space-y-2">
                                            <Label class="text-sm font-medium">Skor Maksimal</Label>
                                            <Input
                                                type="number"
                                                v-model.number="question.max_score"
                                                min="0"
                                                placeholder="100"
                                                class="transition-all duration-200 focus:ring-2"
                                            />
                                            <InputError :message="(form.errors as any)[`questions.${qIndex}.max_score`]" />
                                        </div>
                                        <div class="space-y-2">
                                            <Label class="text-sm font-medium">Skor Minimal</Label>
                                            <Input
                                                type="number"
                                                v-model.number="question.min_score"
                                                min="0"
                                                placeholder="0"
                                                class="transition-all duration-200 focus:ring-2"
                                            />
                                            <InputError :message="(form.errors as any)[`questions.${qIndex}.min_score`]" />
                                        </div>
                                        <div class="flex items-center justify-start space-x-2 pt-6">
                                            <Checkbox :id="`mandatory-${qIndex}`" v-model:checked="question.is_mandatory" />
                                            <Label :for="`mandatory-${qIndex}`" class="text-sm font-medium"> Pertanyaan Wajib </Label>
                                        </div>
                                    </div>

                                    <Separator class="my-6" />

                                    <div class="mb-6 space-y-4">
                                        <div class="flex items-center justify-between">
                                            <Label class="text-sm font-medium">Opsi Jawaban</Label>
                                            <Button
                                                type="button"
                                                @click="addOption(qIndex)"
                                                variant="outline"
                                                size="sm"
                                                class="transition-all duration-200 hover:scale-105"
                                            >
                                                <PlusCircle class="mr-2 h-3 w-3" />
                                                Tambah Opsi
                                            </Button>
                                        </div>
                                        <div class="space-y-3">
                                            <div
                                                v-for="(option, oIndex) in question.options"
                                                :key="oIndex"
                                                class="flex items-center space-x-3 rounded-lg border bg-white p-3 transition-all duration-200 hover:shadow-sm"
                                            >
                                                <Badge variant="outline" class="text-xs"> {{ oIndex + 1 }} </Badge>
                                                <Input
                                                    v-model="option.option_text"
                                                    placeholder="Teks opsi jawaban"
                                                    class="flex-1 transition-all duration-200 focus:ring-2"
                                                />
                                                <div class="flex items-center space-x-2">
                                                    <Label class="text-xs text-gray-500">Skor:</Label>
                                                    <Input
                                                        type="number"
                                                        v-model.number="option.score"
                                                        placeholder="0"
                                                        class="w-20 transition-all duration-200 focus:ring-2"
                                                    />
                                                </div>
                                                <Button
                                                    v-if="question.options.length > 1"
                                                    type="button"
                                                    @click="removeOption(qIndex, oIndex)"
                                                    variant="ghost"
                                                    size="icon"
                                                    class="h-7 w-7 text-red-500 hover:bg-red-50 hover:text-red-600"
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    </div>

                                    <Separator class="my-6" />

                                    <div class="space-y-4">
                                        <div class="flex items-center justify-between">
                                            <Label class="text-sm font-medium">Aturan Visibilitas</Label>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                @click="addVisibilityRule(qIndex)"
                                                class="transition-all duration-200 hover:scale-105"
                                            >
                                                <PlusCircle class="mr-2 h-3 w-3" />
                                                Tambah Aturan
                                            </Button>
                                        </div>

                                        <div
                                            v-if="question.visibility_rules.length === 0"
                                            class="rounded-lg border border-dashed border-gray-200 bg-gray-50 p-6 text-center"
                                        >
                                            <p class="text-sm text-gray-500">Tidak ada aturan visibilitas. Pertanyaan akan selalu ditampilkan.</p>
                                        </div>

                                        <div class="space-y-3">
                                            <div
                                                v-for="(rule, ruleIndex) in question.visibility_rules"
                                                :key="ruleIndex"
                                                class="rounded-lg border bg-blue-50/50 p-4 transition-all duration-200 hover:bg-blue-50"
                                            >
                                                <div class="mb-4 flex items-center justify-between">
                                                    <Badge variant="secondary" class="text-xs"> Aturan {{ ruleIndex + 1 }} </Badge>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        @click="removeVisibilityRule(qIndex, ruleIndex)"
                                                        class="h-7 w-7 text-red-500 hover:bg-red-50 hover:text-red-600"
                                                    >
                                                        <Trash2 class="h-4 w-4" />
                                                    </Button>
                                                </div>

                                                <div class="space-y-4">
                                                    <div class="space-y-2">
                                                        <Label :for="`rule-description-${qIndex}-${ruleIndex}`" class="text-sm font-medium">
                                                            Deskripsi
                                                        </Label>
                                                        <Input
                                                            :id="`rule-description-${qIndex}-${ruleIndex}`"
                                                            v-model="rule.description"
                                                            placeholder="Deskripsi aturan visibilitas (opsional)"
                                                            class="transition-all duration-200 focus:ring-2"
                                                        />
                                                    </div>

                                                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                                        <div class="space-y-2">
                                                            <Label :for="`rule-source-type-${qIndex}-${ruleIndex}`" class="text-sm font-medium">
                                                                Tipe Sumber
                                                            </Label>
                                                            <Select
                                                                :model-value="rule.source_type"
                                                                @update:model-value="
                                                                    (newValue) => {
                                                                        rule.source_type = newValue as string;
                                                                        rule.source_field = ''; // Reset dependent field
                                                                    }
                                                                "
                                                            >
                                                                <SelectTrigger
                                                                    :id="`rule-source-type-${qIndex}-${ruleIndex}`"
                                                                    class="transition-all duration-200 focus:ring-2"
                                                                >
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

                                                        <div class="space-y-2">
                                                            <Label :for="`rule-source-field-${qIndex}-${ruleIndex}`" class="text-sm font-medium">
                                                                Field Sumber
                                                            </Label>
                                                            <template v-if="getSourceFieldOptions(rule.source_type, qIndex).length > 0">
                                                                <Select v-model="rule.source_field">
                                                                    <SelectTrigger
                                                                        :id="`rule-source-field-${qIndex}-${ruleIndex}`"
                                                                        class="transition-all duration-200 focus:ring-2"
                                                                    >
                                                                        <SelectValue placeholder="Pilih field sumber" />
                                                                    </SelectTrigger>
                                                                    <SelectContent>
                                                                        <SelectItem
                                                                            v-for="option in getSourceFieldOptions(rule.source_type, qIndex)"
                                                                            :key="option.value"
                                                                            :value="option.value"
                                                                        >
                                                                            {{ option.label }}
                                                                        </SelectItem>
                                                                    </SelectContent>
                                                                </Select>
                                                            </template>
                                                            <template v-else>
                                                                <Input
                                                                    :id="`rule-source-field-${qIndex}-${ruleIndex}`"
                                                                    v-model="rule.source_field"
                                                                    placeholder="Pilih Tipe Sumber Dahulu"
                                                                    required
                                                                    :disabled="!rule.source_type"
                                                                    class="transition-all duration-200 focus:ring-2"
                                                                />
                                                            </template>
                                                        </div>
                                                    </div>

                                                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                                        <div class="space-y-2">
                                                            <Label :for="`rule-operator-${qIndex}-${ruleIndex}`" class="text-sm font-medium">
                                                                Operator
                                                            </Label>
                                                            <Select v-model="rule.operator">
                                                                <SelectTrigger
                                                                    :id="`rule-operator-${qIndex}-${ruleIndex}`"
                                                                    class="transition-all duration-200 focus:ring-2"
                                                                >
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

                                                        <div class="space-y-2">
                                                            <Label :for="`rule-value-${qIndex}-${ruleIndex}`" class="text-sm font-medium">
                                                                Nilai
                                                            </Label>
                                                            <Input
                                                                :id="`rule-value-${qIndex}-${ruleIndex}`"
                                                                v-model="rule.value"
                                                                placeholder="Nilai untuk perbandingan"
                                                                required
                                                                class="transition-all duration-200 focus:ring-2"
                                                            />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <div class="flex flex-col-reverse gap-4 sm:flex-row sm:justify-end">
                        <Link :href="route('aspects.index')">
                            <Button type="button" variant="outline" class="w-full transition-all duration-200 hover:scale-105 sm:w-auto">
                                Batal
                            </Button>
                        </Link>
                        <Button
                            type="submit"
                            :disabled="form.processing || !isWeightValid"
                            class="w-full transition-all duration-200 hover:scale-105 sm:w-auto"
                        >
                            <Save class="mr-2 h-4 w-4" />
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Aspek' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
