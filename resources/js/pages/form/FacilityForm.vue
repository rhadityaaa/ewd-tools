<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useFormStore } from '@/stores/formStore';
import type { BorrowerFacility } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import { POSITION, useToast } from 'vue-toastification';

interface Props {
    facilities?: BorrowerFacility[];
}

withDefaults(defineProps<Props>(), {
    facilities: () => [],
});

const toast = useToast();
const formStore = useFormStore();

const isSubmitting = ref(false);

const purpose = computed(() => formStore.informationBorrower.purpose);
const showKieTable = computed(() => purpose.value === 'kie' || purpose.value === 'both');
const showKmkeTable = computed(() => purpose.value === 'kmke' || purpose.value === 'both');

const kieRows = ref([
    {
        id: null,
        name: 'KIE 1',
        limit: 0,
        outstanding: 0,
        interestRate: 0,
        principalArrears: 0,
        interestArrears: 0,
        pdo: 0,
        maturityDate: new Date().toISOString().split('T')[0],
    },
]);

const kmkeRows = ref([
    {
        id: null,
        name: 'KMKE 1',
        limit: 0,
        outstanding: 0,
        interestRate: 0,
        principalArrears: 0,
        interestArrears: 0,
        pdo: 0,
        maturityDate: new Date().toISOString().split('T')[0],
    },
]);

const form = useForm({
    facilities: [] as any[],
});

onMounted(() => {
    if (formStore.facilitiesBorrower.length > 0) {
        const existingFacilities = formStore.facilitiesBorrower;

        const kieFacilities = existingFacilities.filter((f) => f.name.toLowerCase().includes('kie'));
        const kmkeFacilities = existingFacilities.filter((f) => f.name.toLowerCase().includes('kmke'));

        if (kieFacilities.length > 0) {
            (kieRows as any).value = kieFacilities.map((f) => ({
                id: f.id,
                name: f.name,
                limit: f.limit,
                outstanding: f.outstanding,
                interestRate: f.interestRate,
                principalArrears: f.principalArrears,
                interestArrears: f.interestArrears,
                pdo: f.pdo,
                maturityDate: f.maturityDate instanceof Date ? f.maturityDate.toISOString().split('T')[0] : f.maturityDate,
            }));
        }

        if (kmkeFacilities.length > 0) {
            (kmkeRows as any).value = kmkeFacilities.map((f) => ({
                id: f.id,
                name: f.name,
                limit: f.limit,
                outstanding: f.outstanding,
                interestRate: f.interestRate,
                principalArrears: f.principalArrears,
                interestArrears: f.interestArrears,
                pdo: f.pdo,
                maturityDate: f.maturityDate instanceof Date ? f.maturityDate.toISOString().split('T')[0] : f.maturityDate,
            }));
        }
    }
});

watch(
    [kieRows, kmkeRows],
    () => {
        const allFacilities = [];

        if (showKieTable.value) {
            allFacilities.push(
                ...kieRows.value.map((row) => ({
                    id: row.id,
                    name: row.name,
                    limit: Number(row.limit),
                    outstanding: Number(row.outstanding),
                    interestRate: Number(row.interestRate),
                    principalArrears: Number(row.principalArrears),
                    interestArrears: Number(row.interestArrears),
                    pdo: Number(row.pdo),
                    maturityDate: new Date(row.maturityDate),
                })),
            );
        }

        if (showKmkeTable.value) {
            allFacilities.push(
                ...kmkeRows.value.map((row) => ({
                    id: row.id,
                    name: row.name,
                    limit: Number(row.limit),
                    outstanding: Number(row.outstanding),
                    interestRate: Number(row.interestRate),
                    principalArrears: Number(row.principalArrears),
                    interestArrears: Number(row.interestArrears),
                    pdo: Number(row.pdo),
                    maturityDate: new Date(row.maturityDate),
                })),
            );
        }

        formStore.facilitiesBorrower = allFacilities;
    },
    { deep: true },
);

const addKieRow = () => {
    const newIndex = kieRows.value.length + 1;
    kieRows.value.push({
        id: null,
        name: `KIE ${newIndex}`,
        limit: 0,
        outstanding: 0,
        interestRate: 0,
        principalArrears: 0,
        interestArrears: 0,
        pdo: 0,
        maturityDate: new Date().toISOString().split('T')[0],
    });
};

const removeKieRow = (index: number) => {
    if (kieRows.value.length > 1) {
        kieRows.value.splice(index, 1);
        kieRows.value.forEach((row, idx) => {
            row.name = `KIE ${idx + 1}`;
        });
    }
};

const addKmkeRow = () => {
    const newIndex = kmkeRows.value.length + 1;
    kmkeRows.value.push({
        id: null,
        name: `KMKE ${newIndex}`,
        limit: 0,
        outstanding: 0,
        interestRate: 0,
        principalArrears: 0,
        interestArrears: 0,
        pdo: 0,
        maturityDate: new Date().toISOString().split('T')[0],
    });
};

const removeKmkeRow = (index: number) => {
    if (kmkeRows.value.length > 1) {
        kmkeRows.value.splice(index, 1);
        kmkeRows.value.forEach((row, idx) => {
            row.name = `KMKE ${idx + 1}`;
        });
    }
};

// Submit function for external call
const submitForm = async () => {
    isSubmitting.value = true;

    try {
        // Prepare facilities data based on purpose
        const facilitiesData = [];

        if (showKieTable.value) {
            facilitiesData.push(
                ...kieRows.value.map((row) => ({
                    facility_name: row.name,
                    limit: Number(row.limit),
                    outstanding: Number(row.outstanding),
                    interest_rate: Number(row.interestRate),
                    principal_arrears: Number(row.principalArrears),
                    interest_arrears: Number(row.interestArrears),
                    pdo_days: Number(row.pdo),
                    maturity_date: row.maturityDate,
                })),
            );
        }

        if (showKmkeTable.value) {
            facilitiesData.push(
                ...kmkeRows.value.map((row) => ({
                    facility_name: row.name,
                    limit: Number(row.limit),
                    outstanding: Number(row.outstanding),
                    interest_rate: Number(row.interestRate),
                    principal_arrears: Number(row.principalArrears),
                    interest_arrears: Number(row.interestArrears),
                    pdo_days: Number(row.pdo),
                    maturity_date: row.maturityDate,
                })),
            );
        }

        form.facilities = facilitiesData;

        await new Promise((resolve, reject) => {
            form.post(route('borrower-facilities.store'), {
                onSuccess: () => {
                    toast.success('Data fasilitas berhasil disimpan', {
                        timeout: 3000,
                        position: POSITION.TOP_RIGHT,
                        closeOnClick: true,
                    });
                    resolve(true);
                },
                onError: (errors) => {
                    toast.error('Terjadi kesalahan saat menyimpan data', {
                        timeout: 3000,
                        position: POSITION.TOP_RIGHT,
                        closeOnClick: true,
                    });
                    console.error(errors);
                    reject(errors);
                },
            });
        });
    } catch (error) {
        console.error('Submit error:', error);
    } finally {
        isSubmitting.value = false;
    }
};

// Computed properties for KIE totals
const totalKieLimit = computed(() => {
    return kieRows.value.reduce((sum, row) => sum + Number(row.limit || 0), 0);
});

const totalKieOutstanding = computed(() => {
    return kieRows.value.reduce((sum, row) => sum + Number(row.outstanding || 0), 0);
});

const avgKieInterestRate = computed(() => {
    if (kieRows.value.length === 0) return '0.00';
    const total = kieRows.value.reduce((sum, row) => sum + Number(row.interestRate || 0), 0);
    return (total / kieRows.value.length).toFixed(2);
});

const totalKiePrincipalArrears = computed(() => {
    return kieRows.value.reduce((sum, row) => sum + Number(row.principalArrears || 0), 0);
});

const totalKieInterestArrears = computed(() => {
    return kieRows.value.reduce((sum, row) => sum + Number(row.interestArrears || 0), 0);
});

const avgKiePdo = computed(() => {
    if (kieRows.value.length === 0) return 0;
    const total = kieRows.value.reduce((sum, row) => sum + Number(row.pdo || 0), 0);
    return Math.round(total / kieRows.value.length);
});

// Computed properties for KMKE totals
const totalKmkeLimit = computed(() => {
    return kmkeRows.value.reduce((sum, row) => sum + Number(row.limit || 0), 0);
});

const totalKmkeOutstanding = computed(() => {
    return kmkeRows.value.reduce((sum, row) => sum + Number(row.outstanding || 0), 0);
});

const avgKmkeInterestRate = computed(() => {
    if (kmkeRows.value.length === 0) return '0.00';
    const total = kmkeRows.value.reduce((sum, row) => sum + Number(row.interestRate || 0), 0);
    return (total / kmkeRows.value.length).toFixed(2);
});

const totalKmkePrincipalArrears = computed(() => {
    return kmkeRows.value.reduce((sum, row) => sum + Number(row.principalArrears || 0), 0);
});

const totalKmkeInterestArrears = computed(() => {
    return kmkeRows.value.reduce((sum, row) => sum + Number(row.interestArrears || 0), 0);
});

const avgKmkePdo = computed(() => {
    if (kmkeRows.value.length === 0) return 0;
    const total = kmkeRows.value.reduce((sum, row) => sum + Number(row.pdo || 0), 0);
    return Math.round(total / kmkeRows.value.length);
});

const validateNumber = (event: Event, row: any, field: string) => {
    const input = event.target as HTMLInputElement;
    let value = input.value;

    value = value.replace(/[^0-9.]/g, '');

    const parts = value.split('.');
    if (parts.length > 2) {
        value = parts[0] + '.' + parts.slice(1).join('');
    }

    if (parts.length === 2 && parts[1].length > 2) {
        value = parts[0] + '.' + parts[1].slice(0, 2);
    }

    row[field] = value === '' ? 0 : parseFloat(value);
};

// Format currency helper
const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID').format(value);
};

// Expose form and submit function for parent component
defineExpose({
    form,
    submitForm,
    isSubmitting,
});
</script>

<template>
    <Head title="Fasilitas Debitur" />

    <div class="container mx-auto px-4 py-8">
        <!-- Purpose Information -->
        <div class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4">
            <h3 class="mb-2 text-lg font-semibold text-gray-900">Tujuan Kredit</h3>
            <p class="text-sm text-gray-600">
                <span class="font-medium">Dipilih:</span>
                <span class="capitalize">{{ purpose === 'both' ? 'KIE dan KMKE' : purpose.toUpperCase() }}</span>
            </p>
        </div>

        <div class="w-full overflow-x-auto">
            <div class="inline-block min-w-full align-middle">
                <Table class="min-w-full border border-gray-200">
                    <TableHeader class="bg-muted">
                        <TableRow class="border-b border-gray-200">
                            <TableHead
                                class="border border-gray-200 px-2 py-2 text-center text-xs sm:text-sm md:px-4 md:py-2 md:text-base"
                                rowspan="2"
                                >Rincian Fasilitas</TableHead
                            >
                            <TableHead
                                class="border border-gray-200 px-2 py-2 text-center text-xs sm:text-sm md:px-4 md:py-2 md:text-base"
                                rowspan="2"
                                >Limit (Juta)</TableHead
                            >
                            <TableHead
                                class="border border-gray-200 px-2 py-2 text-center text-xs sm:text-sm md:px-4 md:py-2 md:text-base"
                                rowspan="2"
                                >Outstanding (Juta)</TableHead
                            >
                            <TableHead
                                class="border border-gray-200 px-2 py-2 text-center text-xs sm:text-sm md:px-4 md:py-2 md:text-base"
                                rowspan="2"
                                >Suku Bunga Efektif (%)</TableHead
                            >
                            <TableHead
                                class="border border-gray-200 px-2 py-2 text-center text-xs sm:text-sm md:px-4 md:py-2 md:text-base"
                                colspan="2"
                                >Tunggakan (Juta)</TableHead
                            >
                            <TableHead
                                class="border border-gray-200 px-2 py-2 text-center text-xs sm:text-sm md:px-4 md:py-2 md:text-base"
                                rowspan="2"
                                >PDO (Hari)</TableHead
                            >
                            <TableHead
                                class="border border-gray-200 px-2 py-2 text-center text-xs sm:text-sm md:px-4 md:py-2 md:text-base"
                                rowspan="2"
                                >Jatuh Tempo</TableHead
                            >
                            <TableHead
                                class="border border-gray-200 px-2 py-2 text-center text-xs sm:text-sm md:px-4 md:py-2 md:text-base"
                                rowspan="2"
                                >Aksi</TableHead
                            >
                        </TableRow>
                        <TableRow class="border-b border-gray-200">
                            <TableHead class="border border-gray-200 px-2 py-2 text-center text-xs sm:text-sm md:px-4 md:py-2 md:text-base"
                                >Pokok</TableHead
                            >
                            <TableHead class="border border-gray-200 px-2 py-2 text-center text-xs sm:text-sm md:px-4 md:py-2 md:text-base"
                                >Bunga</TableHead
                            >
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <!-- KIE Section - Only show if purpose is 'kie' or 'both' -->
                        <template v-if="showKieTable">
                            <!-- KIE Summary Row -->
                            <TableRow class="bg-blue-50">
                                <TableCell
                                    class="min-w-[120px] border border-gray-200 px-2 py-2 text-center text-xs font-medium sm:text-sm md:px-4 md:py-3 md:text-base"
                                    >KIE (IDR Juta)</TableCell
                                >
                                <TableCell
                                    class="min-w-[120px] border border-gray-200 px-2 py-2 text-center text-xs font-semibold sm:text-sm md:px-4 md:py-3 md:text-base"
                                    >{{ formatCurrency(totalKieLimit) }}</TableCell
                                >
                                <TableCell
                                    class="min-w-[120px] border border-gray-200 px-2 py-2 text-center text-xs font-semibold sm:text-sm md:px-4 md:py-3 md:text-base"
                                    >{{ formatCurrency(totalKieOutstanding) }}</TableCell
                                >
                                <TableCell
                                    class="min-w-[120px] border border-gray-200 px-2 py-2 text-center text-xs font-semibold sm:text-sm md:px-4 md:py-3 md:text-base"
                                    >{{ avgKieInterestRate }}%</TableCell
                                >
                                <TableCell
                                    class="min-w-[120px] border border-gray-200 px-2 py-2 text-center text-xs font-semibold sm:text-sm md:px-4 md:py-3 md:text-base"
                                    >{{ formatCurrency(totalKiePrincipalArrears) }}</TableCell
                                >
                                <TableCell
                                    class="min-w-[120px] border border-gray-200 px-2 py-2 text-center text-xs font-semibold sm:text-sm md:px-4 md:py-3 md:text-base"
                                    >{{ formatCurrency(totalKieInterestArrears) }}</TableCell
                                >
                                <TableCell
                                    class="min-w-[120px] border border-gray-200 px-2 py-2 text-center text-xs font-semibold sm:text-sm md:px-4 md:py-3 md:text-base"
                                    >{{ avgKiePdo }}</TableCell
                                >
                                <TableCell
                                    class="min-w-[120px] border border-gray-200 px-2 py-2 text-center text-xs sm:text-sm md:px-4 md:py-3 md:text-base"
                                    >-</TableCell
                                >
                                <TableCell class="border border-gray-200 px-2 py-2 text-center text-xs sm:text-sm md:px-4 md:py-3 md:text-base">
                                    <button
                                        type="button"
                                        @click="addKieRow"
                                        class="rounded-md bg-green-600 px-2 py-1 text-white shadow-none transition-colors hover:bg-green-700 sm:px-3 sm:py-1.5"
                                        title="Tambah KIE"
                                    >
                                        <Plus class="h-3 w-3 sm:h-4 sm:w-4" />
                                    </button>
                                </TableCell>
                            </TableRow>

                            <!-- KIE Detail Rows -->
                            <TableRow v-for="(row, index) in kieRows" :key="`kie-${index}`">
                                <TableCell class="border border-gray-200 px-2 py-2 text-center text-xs sm:text-sm md:px-4 md:py-3 md:text-base">
                                    <Input
                                        type="text"
                                        v-model="row.name"
                                        class="w-full text-xs sm:text-sm md:text-base"
                                        placeholder="Nama fasilitas"
                                    />
                                </TableCell>
                                <TableCell class="border border-gray-200 px-2 py-2 text-center md:px-4 md:py-3">
                                    <Input
                                        type="number"
                                        v-model.number="row.limit"
                                        class="w-full text-xs sm:text-sm md:text-base"
                                        min="0"
                                        step="0.01"
                                    />
                                </TableCell>
                                <TableCell class="border border-gray-200 px-2 py-2 text-center md:px-4 md:py-3">
                                    <Input
                                        type="number"
                                        v-model.number="row.outstanding"
                                        class="w-full text-xs sm:text-sm md:text-base"
                                        min="0"
                                        step="0.01"
                                    />
                                </TableCell>
                                <TableCell class="border border-gray-200 px-2 py-2 text-center md:px-4 md:py-3">
                                    <Input
                                        type="number"
                                        v-model.number="row.interestRate"
                                        class="w-full text-xs sm:text-sm md:text-base"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                    />
                                </TableCell>
                                <TableCell class="border border-gray-200 px-2 py-2 text-center md:px-4 md:py-3">
                                    <Input
                                        type="number"
                                        v-model.number="row.principalArrears"
                                        class="w-full text-xs sm:text-sm md:text-base"
                                        min="0"
                                        step="0.01"
                                    />
                                </TableCell>
                                <TableCell class="border border-gray-200 px-2 py-2 text-center md:px-4 md:py-3">
                                    <Input
                                        type="number"
                                        v-model.number="row.interestArrears"
                                        class="w-full text-xs sm:text-sm md:text-base"
                                        min="0"
                                        step="0.01"
                                    />
                                </TableCell>
                                <TableCell class="border border-gray-200 px-2 py-2 text-center md:px-4 md:py-3">
                                    <Input type="number" v-model.number="row.pdo" class="w-full text-xs sm:text-sm md:text-base" min="0" />
                                </TableCell>
                                <TableCell class="border border-gray-200 px-2 py-2 text-center md:px-4 md:py-3">
                                    <Input type="date" v-model="row.maturityDate" class="w-full text-xs sm:text-sm md:text-base" />
                                </TableCell>
                                <TableCell class="border border-gray-200 px-2 py-2 text-center md:px-4 md:py-3">
                                    <button
                                        type="button"
                                        @click="removeKieRow(index)"
                                        :disabled="kieRows.length <= 1"
                                        class="rounded-md bg-red-600 px-2 py-1 text-white shadow-none transition-colors hover:bg-red-700 disabled:cursor-not-allowed disabled:bg-gray-400 sm:px-3 sm:py-1.5"
                                        title="Hapus KIE"
                                    >
                                        <Trash2 class="h-3 w-3 sm:h-4 sm:w-4" />
                                    </button>
                                </TableCell>
                            </TableRow>
                        </template>

                        <!-- KMKE Section - Only show if purpose is 'kmke' or 'both' -->
                        <template v-if="showKmkeTable">
                            <!-- KMKE Summary Row -->
                            <TableRow class="bg-green-50">
                                <TableCell
                                    class="min-w-[120px] border border-gray-200 px-2 py-2 text-center text-xs font-medium sm:text-sm md:px-4 md:py-3 md:text-base"
                                    >KMKE (IDR Juta)</TableCell
                                >
                                <TableCell
                                    class="min-w-[120px] border border-gray-200 px-2 py-2 text-center text-xs font-semibold sm:text-sm md:px-4 md:py-3 md:text-base"
                                    >{{ formatCurrency(totalKmkeLimit) }}</TableCell
                                >
                                <TableCell
                                    class="min-w-[120px] border border-gray-200 px-2 py-2 text-center text-xs font-semibold sm:text-sm md:px-4 md:py-3 md:text-base"
                                    >{{ formatCurrency(totalKmkeOutstanding) }}</TableCell
                                >
                                <TableCell
                                    class="min-w-[120px] border border-gray-200 px-2 py-2 text-center text-xs font-semibold sm:text-sm md:px-4 md:py-3 md:text-base"
                                    >{{ avgKmkeInterestRate }}%</TableCell
                                >
                                <TableCell
                                    class="min-w-[120px] border border-gray-200 px-2 py-2 text-center text-xs font-semibold sm:text-sm md:px-4 md:py-3 md:text-base"
                                    >{{ formatCurrency(totalKmkePrincipalArrears) }}</TableCell
                                >
                                <TableCell
                                    class="min-w-[120px] border border-gray-200 px-2 py-2 text-center text-xs font-semibold sm:text-sm md:px-4 md:py-3 md:text-base"
                                    >{{ formatCurrency(totalKmkeInterestArrears) }}</TableCell
                                >
                                <TableCell
                                    class="min-w-[120px] border border-gray-200 px-2 py-2 text-center text-xs font-semibold sm:text-sm md:px-4 md:py-3 md:text-base"
                                    >{{ avgKmkePdo }}</TableCell
                                >
                                <TableCell
                                    class="min-w-[120px] border border-gray-200 px-2 py-2 text-center text-xs sm:text-sm md:px-4 md:py-3 md:text-base"
                                    >-</TableCell
                                >
                                <TableCell class="min-w-[120px] border border-gray-200 px-2 py-2 text-center md:px-4 md:py-3">
                                    <button
                                        type="button"
                                        @click="addKmkeRow"
                                        class="rounded-md bg-green-600 px-2 py-1 text-white shadow-none transition-colors hover:bg-green-700 sm:px-3 sm:py-1.5"
                                        title="Tambah KMKE"
                                    >
                                        <Plus class="h-3 w-3 sm:h-4 sm:w-4" />
                                    </button>
                                </TableCell>
                            </TableRow>

                            <!-- KMKE Detail Rows -->
                            <TableRow v-for="(row, index) in kmkeRows" :key="`kmke-${index}`">
                                <TableCell class="border border-gray-200 px-2 py-2 text-center text-xs sm:text-sm md:px-4 md:py-3 md:text-base">
                                    <Input
                                        type="text"
                                        v-model="row.name"
                                        class="w-full text-xs sm:text-sm md:text-base"
                                        placeholder="Nama fasilitas"
                                    />
                                </TableCell>
                                <TableCell class="border border-gray-200 px-2 py-2 text-center md:px-4 md:py-3">
                                    <Input
                                        type="number"
                                        v-model.number="row.limit"
                                        class="w-full text-right text-xs sm:text-sm md:text-base"
                                        min="0"
                                        step="1"
                                        @input="validateNumber($event, row, 'limit')"
                                    />
                                </TableCell>
                                <TableCell class="border border-gray-200 px-2 py-2 text-center md:px-4 md:py-3">
                                    <Input
                                        type="number"
                                        v-model.number="row.outstanding"
                                        class="w-full text-xs sm:text-sm md:text-base"
                                        min="0"
                                        step="1"
                                    />
                                </TableCell>
                                <TableCell class="border border-gray-200 px-2 py-2 text-center md:px-4 md:py-3">
                                    <Input
                                        type="number"
                                        v-model.number="row.interestRate"
                                        class="w-full text-xs sm:text-sm md:text-base"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                    />
                                </TableCell>
                                <TableCell class="border border-gray-200 px-2 py-2 text-center md:px-4 md:py-3">
                                    <Input
                                        type="number"
                                        v-model.number="row.principalArrears"
                                        class="w-full text-xs sm:text-sm md:text-base"
                                        min="0"
                                        step="0.01"
                                    />
                                </TableCell>
                                <TableCell class="border border-gray-200 px-2 py-2 text-center md:px-4 md:py-3">
                                    <Input
                                        type="number"
                                        v-model.number="row.interestArrears"
                                        class="w-full text-xs sm:text-sm md:text-base"
                                        min="0"
                                        step="0.01"
                                    />
                                </TableCell>
                                <TableCell class="border border-gray-200 px-2 py-2 text-center md:px-4 md:py-3">
                                    <Input type="number" v-model.number="row.pdo" class="w-full text-xs sm:text-sm md:text-base" min="0" />
                                </TableCell>
                                <TableCell class="border border-gray-200 px-2 py-2 text-center md:px-4 md:py-3">
                                    <Input type="date" v-model="row.maturityDate" class="w-full text-xs sm:text-sm md:text-base" />
                                </TableCell>
                                <TableCell class="border border-gray-200 px-2 py-2 text-center md:px-4 md:py-3">
                                    <button
                                        type="button"
                                        @click="removeKmkeRow(index)"
                                        :disabled="kmkeRows.length <= 1"
                                        class="rounded-md bg-red-600 px-2 py-1 text-white shadow-none transition-colors hover:bg-red-700 disabled:cursor-not-allowed disabled:bg-gray-400 sm:px-3 sm:py-1.5"
                                        title="Hapus KMKE"
                                    >
                                        <Trash2 class="h-3 w-3 sm:h-4 sm:w-4" />
                                    </button>
                                </TableCell>
                            </TableRow>
                        </template>
                    </TableBody>
                </Table>
            </div>
        </div>

        <!-- Summary Information - Only show relevant summaries -->
        <div class="mt-6 grid grid-cols-1 gap-4" :class="{ 'md:grid-cols-2': showKieTable && showKmkeTable }">
            <div v-if="showKieTable" class="rounded-lg border border-gray-200 p-4">
                <h3 class="mb-3 text-lg font-semibold text-gray-900">Ringkasan KIE</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span>Total Limit:</span>
                        <span class="font-medium">Rp {{ formatCurrency(totalKieLimit) }} Juta</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Total Outstanding:</span>
                        <span class="font-medium">Rp {{ formatCurrency(totalKieOutstanding) }} Juta</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Rata-rata Suku Bunga:</span>
                        <span class="font-medium">{{ avgKieInterestRate }}%</span>
                    </div>
                </div>
            </div>

            <div v-if="showKmkeTable" class="rounded-lg border border-gray-200 p-4">
                <h3 class="mb-3 text-lg font-semibold text-gray-900">Ringkasan KMKE</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span>Total Limit:</span>
                        <span class="font-medium">Rp {{ formatCurrency(totalKmkeLimit) }} Juta</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Total Outstanding:</span>
                        <span class="font-medium">Rp {{ formatCurrency(totalKmkeOutstanding) }} Juta</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Rata-rata Suku Bunga:</span>
                        <span class="font-medium">{{ avgKmkeInterestRate }}%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
