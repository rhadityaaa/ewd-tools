<script setup lang="ts">
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Aspect, BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle2, Info, PlusCircle, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { useToast } from 'vue-toastification';

const toast = useToast();

const props = defineProps<{
    aspects: Aspect[];
}>();

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
        href: route('templates.store'),
    },
];

const selectedAspectIds = ref<number[]>([]);

const visibilityRules = ref<{
    description: string;
    source_type: string;
    source_field: string;
    operator: string;
    value: string;
}[]>([]);

const form = useForm({
    name: '',
    description: '',
    selected_aspects: [] as { id: number; weight: number }[], // Fixed: changed from 'aspects' to 'selected_aspects'
    visibility_rules: [] as any[],
});

// Computed untuk mengelola checkbox state per aspect
const getAspectChecked = (aspectId: number) => {
    return computed({
        get: () => selectedAspectIds.value.includes(aspectId),
        set: (checked: boolean) => {
            if (checked) {
                if (!selectedAspectIds.value.includes(aspectId)) {
                    selectedAspectIds.value.push(aspectId);
                }
            } else {
                const index = selectedAspectIds.value.indexOf(aspectId);
                if (index > -1) {
                    selectedAspectIds.value.splice(index, 1);
                }
            }
        }
    });
};

// Watcher untuk sinkronisasi form.selected_aspects
watch(
    selectedAspectIds,
    (newIds: number[]) => {
        // Hapus aspek yang tidak lagi dipilih
        form.selected_aspects = form.selected_aspects.filter(aspect => newIds.includes(aspect.id));
        
        // Tambah aspek baru yang dipilih
        newIds.forEach(id => {
            if (!form.selected_aspects.find(aspect => aspect.id === id)) {
                form.selected_aspects.push({ id, weight: 0 });
            }
        });
    },
    { deep: true, immediate: true }
);

// Fungsi helper untuk update weight
const updateAspectWeight = (aspectId: number, weight: number) => {
    const aspectIndex = form.selected_aspects.findIndex(a => a.id === aspectId);
    if (aspectIndex > -1) {
        form.selected_aspects[aspectIndex].weight = weight;
    }
};

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

const borrowerDetailFields = [
    { value: 'borrower_group', label: 'Grup Peminjam' },
    { value: 'purpose', label: 'Tujuan' },
    { value: 'economic_sector', label: 'Sektor Ekonomi' },
    { value: 'business_field', label: 'Bidang Usaha' },
    { value: 'collectibility', label: 'Kolektibilitas' },
    { value: 'restructuring', label: 'Restrukturisasi' },
];

const borrowerFacilityFields = [
    { value: 'facility_name', label: 'Nama Fasilitas' },
    { value: 'limit', label: 'Limit' },
    { value: 'outstanding', label: 'Outstanding' },
    { value: 'interest_rate', label: 'Suku Bunga' },
    { value: 'principal_arrears', label: 'Tunggakan Pokok' },
    { value: 'interest_arrears', label: 'Tunggakan Bunga' },
    { value: 'pdo_days', label: 'Hari PDO' },
];

const totalWeight = computed(() => {
    return form.selected_aspects.reduce((sum, aspect) => sum + Number(aspect.weight || 0), 0);
});

const isWeightValid = computed(() => {
    if (form.selected_aspects.length === 0) return false;
    return Math.abs(totalWeight.value - 100) < 0.01;
});

const weightAlert = computed(() => {
    if (form.selected_aspects.length === 0) {
        return { show: false, message: '', variant: 'default' };
    }

    const diff = 100 - totalWeight.value;

    if (isWeightValid.value) {
        return { show: true, message: 'Total bobot sudah tepat 100%. Anda siap untuk menyimpan.', variant: 'default' };
    } else if (diff > 0) {
        return { show: true, message: `Total bobot masih kurang ${diff.toFixed(1)}% untuk mencapai 100%.`, variant: 'destructive' };
    } else {
        return { show: true, message: `Total bobot berlebih ${Math.abs(diff).toFixed(1)}% dari 100%.`, variant: 'destructive' };
    }
});

const addVisibilityRule = () => {
    visibilityRules.value.push({
        description: '',
        source_type: 'borrower_detail',
        source_field: '',
        operator: '=',
        value: '',
    });
};

const removeVisibilityRule = (index: number) => {
    visibilityRules.value.splice(index, 1);
};

const getFieldOptions = (sourceType: string) => {
    switch (sourceType) {
        case 'borrower_detail':
            return borrowerDetailFields;
        case 'borrower_facility':
            return borrowerFacilityFields;
        default:
            return [];
    }
};

const distributeWeightEvenly = () => {
    if (form.selected_aspects.length === 0) return;
    
    const evenWeight = 100 / form.selected_aspects.length;
    form.selected_aspects.forEach(aspect => {
        aspect.weight = Math.round(evenWeight * 100) / 100;
    });
    
    // Adjust for rounding errors
    const currentTotal = form.selected_aspects.reduce((sum, aspect) => sum + aspect.weight, 0);
    const diff = 100 - currentTotal;
    if (Math.abs(diff) > 0.01 && form.selected_aspects.length > 0) {
        form.selected_aspects[0].weight += diff;
        form.selected_aspects[0].weight = Math.round(form.selected_aspects[0].weight * 100) / 100;
    }
};

const submit = () => {
    if (form.selected_aspects.length === 0) {
        toast.error('Pilih minimal satu aspek.');
        return;
    }
    if (!isWeightValid.value) {
        toast.error('Total bobot semua aspek yang dipilih harus tepat 100%.');
        return;
    }

    form.visibility_rules = visibilityRules.value;

    form.post(route('templates.store'), {
        onSuccess: () => {
            toast.success('Template berhasil dibuat!');
        },
        onError: (errors) => {
            console.error('Submission Error:', errors);
            toast.error('Terjadi kesalahan. Periksa kembali isian Anda.');
        },
    });
};
</script>

<template>
    <Head title="Tambah Template" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-6 lg:py-8">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 lg:text-3xl">Tambah Template Baru</h1>
                            <p class="mt-2 text-sm text-gray-600">
                                Buat template penilaian baru dengan memilih aspek, menentukan bobot, dan aturan visibilitas
                            </p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <Badge :variant="isWeightValid ? 'default' : 'destructive'" class="text-xs">
                                Total Bobot: {{ totalWeight.toFixed(1) }}%
                            </Badge>
                        </div>
                    </div>
                </div>

                <Alert v-if="weightAlert.show" :variant="weightAlert.variant as any" class="mb-6">
                    <CheckCircle2 v-if="isWeightValid" class="h-4 w-4" />
                    <AlertCircle v-else class="h-4 w-4" />
                    <AlertDescription>
                        {{ weightAlert.message }}
                    </AlertDescription>
                </Alert>

                <form @submit.prevent="submit" class="space-y-8">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center">
                                <Info class="mr-2 h-5 w-5" />
                                Informasi Dasar
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="name" class="text-sm font-medium">Judul Template</Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        placeholder="Masukan judul template"
                                        class="transition-all duration-200 focus:ring-2"
                                    />
                                    <InputError :message="form.errors.name" />
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label for="description" class="text-sm font-medium">Deskripsi Template</Label>
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    placeholder="Masukan deskripsi template (opsional)"
                                    rows="3"
                                    class="transition-all duration-200 focus:ring-2"
                                />
                                <InputError :message="form.errors.description" />
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Aspect Selection with Weights -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center justify-between">
                                <span>Pemilihan Aspek & Pembobotan</span>
                                <Button type="button" variant="outline" size="sm" @click="distributeWeightEvenly" :disabled="form.selected_aspects.length === 0">
                                    Bagi Rata Bobot
                                </Button>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <div v-if="props.aspects.length > 0">
                                <div class="space-y-4">
                                    <div v-for="aspect in props.aspects" :key="aspect.id" class="flex items-center justify-between rounded-lg border p-4">
                                        <div class="flex items-center space-x-3">
                                            <Checkbox
                                                :id="`aspect-${aspect.id}`"
                                                v-model="getAspectChecked(aspect.id).value"
                                            />
                                            <Label :for="`aspect-${aspect.id}`" class="font-medium">
                                                {{ aspect.latest_aspect_version?.name || aspect.code }}
                                            </Label>
                                        </div>
                                        <div v-if="selectedAspectIds.includes(aspect.id)" class="flex items-center space-x-2">
                                            <Label class="text-sm">Bobot (%):</Label>
                                            <Input
                                                type="number"
                                                :value="form.selected_aspects.find(a => a.id === aspect.id)?.weight || 0"
                                                @input="(e: Event) => {
                                                    const target = e.target as HTMLInputElement;
                                                    updateAspectWeight(aspect.id, Number(target.value));
                                                }"
                                                min="0"
                                                max="100"
                                                step="0.1"
                                                class="w-20"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Visibility Rules -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center justify-between">
                                <span>Aturan Visibilitas Template</span>
                                <Button type="button" variant="outline" size="sm" @click="addVisibilityRule">
                                    <PlusCircle class="mr-2 h-4 w-4" />
                                    Tambah Aturan
                                </Button>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <div v-if="visibilityRules.length === 0" class="rounded-lg border border-dashed border-gray-200 bg-gray-50 p-6 text-center">
                                <p class="text-sm text-gray-500">Tidak ada aturan visibilitas. Template akan selalu ditampilkan.</p>
                            </div>

                            <div v-for="(rule, index) in visibilityRules" :key="index" class="space-y-4 rounded-lg border p-4">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-medium">Aturan {{ index + 1 }}</h4>
                                    <Button type="button" variant="destructive" size="sm" @click="removeVisibilityRule(index)">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label>Deskripsi (Opsional)</Label>
                                        <Input v-model="rule.description" placeholder="Deskripsi aturan" />
                                    </div>
                                </div>

                                <div class="grid gap-4 md:grid-cols-3">
                                    <div class="space-y-2">
                                        <Label>Tipe Sumber</Label>
                                        <Select v-model="rule.source_type" @update:model-value="rule.source_field = ''">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Pilih tipe sumber" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="option in sourceTypeOptions" :key="option.value" :value="option.value">
                                                    {{ option.label }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div class="space-y-2">
                                        <Label>Field Sumber</Label>
                                        <Select v-model="rule.source_field" :disabled="!rule.source_type">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Pilih field" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="field in getFieldOptions(rule.source_type)" :key="field.value" :value="field.value">
                                                    {{ field.label }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div class="space-y-2">
                                        <Label>Operator</Label>
                                        <Select v-model="rule.operator">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Pilih operator" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="operator in operatorOptions" :key="operator.value" :value="operator.value">
                                                    {{ operator.label }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <Label>Nilai</Label>
                                    <Input v-model="rule.value" placeholder="Masukan nilai untuk perbandingan" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <div class="flex justify-end space-x-4">
                        <Button type="button" variant="outline" @click="$inertia.visit(route('templates.index'))">Batal</Button>
                        <Button type="submit" :disabled="form.processing || !isWeightValid">Simpan Template</Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
