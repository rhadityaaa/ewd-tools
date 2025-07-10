<script setup>
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useFormStore } from '@/stores/formStore';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useToast } from 'vue-toastification';

const toast = useToast();
// Props from controller
const props = defineProps({
    aspectGroups: {
        type: Array,
        default: () => [],
    },
    reportId: {
        type: Number,
        default: null,
    },
});

console.log(props.aspectGroups);

// Pinia store
const formStore = useFormStore();

const showReport = ref(false);
const isSubmitting = ref(false);

// Use reactive data from props and sync with store
const aspectGroups = reactive(
    props.aspectGroups.map((group) => ({
        ...group,
        aspects: group.aspects.map((aspect) => {
            // Check if aspect already exists in store
            const existingAspect = formStore.aspectsBorrower.find((a) => a.questionId === aspect.id);
            return {
                ...aspect,
                value: existingAspect?.selectedOptionId || null,
                notes: existingAspect?.notes || '',
            };
        }),
    })),
);

// Initialize store with aspect data
onMounted(() => {
    const aspectsData = [];
    aspectGroups.forEach((group) => {
        group.aspects.forEach((aspect) => {
            aspectsData.push({
                questionId: aspect.id,
                questionText: aspect.question,
                aspectName: group.name,
                aspectCode: group.code || `ASP_${group.id}`,
                options:
                    aspect.options && aspect.options.length > 0
                        ? aspect.options
                        : [
                              { id: 1, option_text: 'Ya', score: 1 },
                              { id: 0, option_text: 'Tidak', score: 0 },
                          ],
                selectedOptionId: aspect.value,
                notes: aspect.notes,
                isMandatory: aspect.is_mandatory || false,
                maxScore: aspect.max_score || 1,
                minScore: aspect.min_score || 0,
                weight: aspect.weight || 1,
                // Pastikan visibility_rules juga ada di sini jika perlu
                visibility_rules: aspect.visibility_rules || [],
            });
        });
    });
    formStore.updateAspectsBorrower(aspectsData);
});

// --- LOGIKA BARU UNTUK VISIBILITY RULES ---

/**
 * Fungsi helper untuk mengevaluasi apakah sebuah aspek/pertanyaan harus ditampilkan.
 * @param {object} aspect - Objek aspek/pertanyaan yang akan dievaluasi.
 * @returns {boolean} - True jika harus ditampilkan, false jika disembunyikan.
 */
const isAspectVisible = (aspect) => {
    // Jika tidak ada visibility_rules, selalu tampilkan pertanyaan.
    if (!aspect.visibility_rules || aspect.visibility_rules.length === 0) {
        return true;
    }

    // Evaluasi setiap aturan. Semua kondisi dalam aturan harus terpenuhi (logika AND).
    return aspect.visibility_rules.every((rule) => {
        let sourceValue;

        // 1. Ambil nilai dari sumber data yang sesuai (Pinia store atau jawaban lain).
        if (rule.source_type === 'borrower_detail') {
            // Mengambil data dari store 'informationBorrower'.
            sourceValue = formStore.informationBorrower[rule.source_field];
        } else if (rule.source_type === 'answer') {
            // Mencari jawaban dari pertanyaan lain di dalam form ini.
            // 'source_field' pada kasus ini berisi ID dari pertanyaan sumber.
            const sourceAspect = aspectGroups.flatMap((g) => g.aspects).find((a) => a.id == rule.source_field);
            sourceValue = sourceAspect ? sourceAspect.value : undefined;
        }
        // Anda bisa menambahkan 'else if' lain untuk source_type berbeda, misal 'borrower_facility'.

        // Jika nilai sumber tidak ditemukan, anggap aturan tidak terpenuhi (sembunyikan pertanyaan).
        if (sourceValue === undefined) {
            return false;
        }

        // 2. Lakukan perbandingan berdasarkan operator.
        // Konversi nilai ke string untuk perbandingan yang konsisten, kecuali untuk perbandingan numerik.
        const ruleValue = String(rule.value);
        const valueToCheck = String(sourceValue);

        switch (rule.operator) {
            case '=':
                return valueToCheck === ruleValue;
            case '!=':
                return valueToCheck !== ruleValue;
            case '>':
                return Number(valueToCheck) > Number(ruleValue);
            case '<':
                return Number(valueToCheck) < Number(ruleValue);
            case '>=':
                return Number(valueToCheck) >= Number(ruleValue);
            case '<=':
                return Number(valueToCheck) <= Number(ruleValue);
            case 'contains':
                return valueToCheck.includes(ruleValue);
            case 'not contains':
                return !valueToCheck.includes(ruleValue);
            default:
                return true; // Jika operator tidak dikenal, secara default tampilkan (atau bisa juga false).
        }
    });
};

/**
 * Computed property untuk mendapatkan grup aspek yang pertanyaannya sudah difilter
 * berdasarkan visibility rules. Ini akan otomatis berjalan setiap kali ada perubahan
 * pada data yang menjadi trigger (misal: jawaban pertanyaan lain).
 */
const visibleAspectGroups = computed(() => {
    return aspectGroups
        .map((group) => {
            return {
                ...group,
                // Filter aspek/pertanyaan di dalam setiap grup menggunakan fungsi isAspectVisible
                aspects: group.aspects.filter((aspect) => isAspectVisible(aspect)),
            };
        })
        .filter((group) => group.aspects.length > 0); // Sembunyikan grup jika tidak ada pertanyaan yang terlihat.
});

// --- AKHIR DARI LOGIKA BARU ---

// Get available options for each question
const getOptionsForQuestion = (aspect) => {
    if (aspect.options && aspect.options.length > 0) {
        return aspect.options;
    }
    return [
        { id: 1, option_text: 'Ya', score: 1 },
        { id: 0, option_text: 'Tidak', score: 0 },
    ];
};

// Watch for changes and sync with store
watch(
    () => aspectGroups,
    (newAspectGroups) => {
        newAspectGroups.forEach((group) => {
            group.aspects.forEach((aspect) => {
                formStore.updateAspectAnswer(aspect.id, aspect.value, aspect.notes);
            });
        });
    },
    { deep: true },
);

// Form untuk submit data
const form = useForm({
    aspects: [],
    report_meta: {
        template_id: null,
        period_id: null,
        borrower_id: null,
    },
    report_id: props.reportId,
});

// Helper function to get selected option score
const getSelectedOptionScore = (aspect) => {
    const options = getOptionsForQuestion(aspect);
    const selectedOption = options.find((opt) => opt.id === aspect.value);
    return selectedOption ? selectedOption.score : 0;
};

// Computed untuk validasi form
const isFormValid = computed(() => {
    // Validasi hanya pada pertanyaan yang terlihat (visible)
    return visibleAspectGroups.value.every((group) =>
        group.aspects.every((aspect) => !aspect.is_mandatory || (aspect.value !== null && aspect.value !== undefined)),
    );
});

// Computed untuk progress
const completionProgress = computed(() => {
    const totalQuestions = visibleAspectGroups.value.reduce((total, group) => total + group.aspects.length, 0);
    if (totalQuestions === 0) return 0;
    const answeredQuestions = visibleAspectGroups.value.reduce(
        (total, group) => total + group.aspects.filter((aspect) => aspect.value !== null && aspect.value !== undefined).length,
        0,
    );
    return Math.round((answeredQuestions / totalQuestions) * 100);
});

// Fungsi untuk submit form ke backend
const submitForm = async () => {
    if (isSubmitting.value) return;

    if (!isFormValid.value) {
        toast.error('Mohon lengkapi semua pertanyaan yang wajib diisi');
        return;
    }

    isSubmitting.value = true;

    try {
        // Prepare data for submission from visible aspects only
        const aspectsData = [];
        visibleAspectGroups.value.forEach((group) => {
            group.aspects.forEach((aspect) => {
                if (aspect.value !== null && aspect.value !== undefined) {
                    aspectsData.push({
                        question_id: aspect.id,
                        selected_option_id: aspect.value,
                        notes: aspect.notes || null,
                        score: getSelectedOptionScore(aspect),
                        aspect_group_id: group.id,
                    });
                }
            });
        });

        // Update form data
        form.aspects = aspectsData;
        form.report_meta = {
            template_id: formStore.reportMeta.template_id,
            period_id: formStore.reportMeta.period_id,
            borrower_id: formStore.informationBorrower.borrowerId,
        };

        // Submit to backend
        const endpoint = props.reportId ? route('aspects.update', props.reportId) : route('aspects.store');
        const method = props.reportId ? 'put' : 'post';

        form[method](endpoint, {
            onSuccess: (response) => {
                toast.success(props.reportId ? 'Data aspek berhasil diperbarui' : 'Data aspek berhasil disimpan');
                if (!props.reportId) {
                    formStore.nextStep();
                }
            },
            onError: (errors) => {
                console.error('Submission errors:', errors);
                let errorMessage = 'Gagal menyimpan data aspek. Periksa kembali isian Anda.';
                toast.error(errorMessage);
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        });
    } catch (error) {
        console.error('Submit error:', error);
        toast.error('Terjadi kesalahan saat menyimpan data');
        isSubmitting.value = false;
    }
};

// Mendeteksi ukuran layar untuk responsivitas
const isMobile = ref(false);
const checkScreenSize = () => {
    isMobile.value = window.innerWidth < 768;
};
onMounted(() => {
    if (typeof window !== 'undefined') {
        checkScreenSize();
        window.addEventListener('resize', checkScreenSize);
    }
});

// Methods
const generateReport = () => {
    showReport.value = true;
};
const closeReport = () => {
    showReport.value = false;
};

// Auto save functionality
const autoSaveTimer = ref(null);
const lastSaved = ref(null);

const autoSave = () => {
    if (autoSaveTimer.value) {
        clearTimeout(autoSaveTimer.value);
    }
    autoSaveTimer.value = setTimeout(() => {
        const aspectsBackup = {
            timestamp: Date.now(),
            data: aspectGroups.map((group) => ({
                id: group.id,
                aspects: group.aspects.map((aspect) => ({
                    id: aspect.id,
                    value: aspect.value,
                    notes: aspect.notes,
                })),
            })),
        };
        localStorage.setItem('aspects_backup', JSON.stringify(aspectsBackup));
        lastSaved.value = new Date();
    }, 2000);
};

watch(() => aspectGroups, autoSave, { deep: true });

onMounted(() => {
    const backup = localStorage.getItem('aspects_backup');
    if (backup) {
        try {
            const parsedBackup = JSON.parse(backup);
            if (Date.now() - parsedBackup.timestamp < 24 * 60 * 60 * 1000) {
                parsedBackup.data.forEach((groupBackup) => {
                    const group = aspectGroups.find((g) => g.id === groupBackup.id);
                    if (group) {
                        groupBackup.aspects.forEach((aspectBackup) => {
                            const aspect = group.aspects.find((a) => a.id === aspectBackup.id);
                            if (aspect && (aspect.value === null || aspect.value === undefined)) {
                                aspect.value = aspectBackup.value;
                                aspect.notes = aspectBackup.notes;
                            }
                        });
                    }
                });
            }
        } catch (error) {
            console.error('Error loading backup:', error);
        }
    }
});

// Expose methods for parent component
defineExpose({
    submitForm,
    isFormValid,
    completionProgress,
});
</script>

<template>
    <Head title="Input Aspek" />

    <!-- Progress Bar -->
    <div class="mb-4">
        <div class="mb-2 flex items-center justify-between">
            <span class="text-sm font-medium text-gray-700">Progress Pengisian</span>
            <span class="text-sm text-gray-500">{{ completionProgress }}%</span>
        </div>
        <div class="h-2 w-full rounded-full bg-gray-200">
            <div class="h-2 rounded-full bg-blue-600 transition-all duration-300" :style="{ width: completionProgress + '%' }"></div>
        </div>
    </div>

    <!-- Form -->
    <div class="container mx-auto px-4 py-8">
        <Card>
            <CardContent>
                <form @submit.prevent="submitForm">
                    <!-- Gunakan `visibleAspectGroups` untuk me-render grup dan pertanyaan yang sudah difilter -->
                    <div v-for="group in visibleAspectGroups" :key="group.id" class="mb-8">
                        <!-- Judul Kelompok Aspek -->
                        <div class="mb-4 rounded bg-gray-200 p-2">
                            <h2 class="font-bold">{{ group.id }}. {{ group.name }}</h2>
                            <p v-if="group.description" class="mt-1 text-sm text-gray-600">{{ group.description }}</p>
                        </div>

                        <!-- Tampilan Desktop -->
                        <div v-if="!isMobile" class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="bg-blue-600 text-white">
                                        <th class="w-16 border border-gray-300 p-2 text-center">NO</th>
                                        <th class="border border-gray-300 p-2 text-left">ASPEK</th>
                                        <th class="w-32 border border-gray-300 p-2 text-center">NILAI</th>
                                        <th class="w-64 border border-gray-300 p-2 text-center">KETERANGAN</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Loop ini sekarang hanya akan menampilkan pertanyaan yang visible -->
                                    <tr
                                        v-for="aspect in group.aspects"
                                        :key="aspect.id"
                                        class="hover:bg-gray-50"
                                        :class="{
                                            'bg-red-50': aspect.is_mandatory && (aspect.value === null || aspect.value === undefined),
                                            'bg-green-50': aspect.value !== null && aspect.value !== undefined,
                                        }"
                                    >
                                        <td class="border border-gray-300 p-2 text-center">
                                            {{ aspect.id }}
                                            <span v-if="aspect.is_mandatory" class="ml-1 text-red-500">*</span>
                                        </td>
                                        <td class="border border-gray-300 p-2">
                                            {{ aspect.question }}
                                            <div v-if="aspect.description" class="mt-1 text-xs text-gray-500">
                                                {{ aspect.description }}
                                            </div>
                                        </td>
                                        <td class="border border-gray-300 p-2">
                                            <Select v-model="aspect.value">
                                                <SelectTrigger class="w-full">
                                                    <SelectValue placeholder="Pilih nilai" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="option in getOptionsForQuestion(aspect)" :key="option.id" :value="option.id">
                                                        {{ option.option_text }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </td>
                                        <td class="border border-gray-300 p-2">
                                            <Textarea v-model="aspect.notes" placeholder="Masukkan keterangan..." class="min-h-[60px]" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Tampilan Mobile -->
                        <div v-else class="space-y-6">
                            <!-- Loop ini juga sudah otomatis terfilter -->
                            <div
                                v-for="aspect in group.aspects"
                                :key="aspect.id"
                                class="rounded-md border border-gray-300 bg-white p-4"
                                :class="{
                                    'border-red-300 bg-red-50': aspect.is_mandatory && (aspect.value === null || aspect.value === undefined),
                                    'border-green-300 bg-green-50': aspect.value !== null && aspect.value !== undefined,
                                }"
                            >
                                <div class="mb-2 font-semibold text-blue-600">
                                    {{ aspect.id }}
                                    <span v-if="aspect.is_mandatory" class="ml-1 text-red-500">*</span>
                                </div>
                                <div class="mb-3">
                                    {{ aspect.question }}
                                    <div v-if="aspect.description" class="mt-1 text-xs text-gray-500">
                                        {{ aspect.description }}
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Nilai:</label>
                                    <Select v-model="aspect.value">
                                        <SelectTrigger class="w-full">
                                            <SelectValue placeholder="Pilih nilai" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="option in getOptionsForQuestion(aspect)" :key="option.id" :value="option.id">
                                                {{ option.option_text }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Keterangan:</label>
                                    <Textarea v-model="aspect.notes" placeholder="Masukkan keterangan..." class="min-h-[80px] w-full" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Auto Save Indicator -->
                    <div v-if="lastSaved" class="mb-4 text-xs text-gray-500">Terakhir disimpan otomatis: {{ lastSaved.toLocaleTimeString() }}</div>

                    <!-- Tombol Submit -->
                    <div class="mt-6 flex justify-end space-x-4">
                        <Button type="button" @click="generateReport" variant="outline" class="w-full md:w-auto" :disabled="completionProgress === 0">
                            Preview Report
                        </Button>
                        <Button
                            type="submit"
                            :disabled="isSubmitting || !isFormValid"
                            class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-50 md:w-auto"
                        >
                            <span v-if="isSubmitting" class="flex items-center">
                                <svg
                                    class="mr-3 -ml-1 h-4 w-4 animate-spin text-white"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    ></path>
                                </svg>
                                Menyimpan...
                            </span>
                            <span v-else>
                                {{ props.reportId ? 'Update Data' : 'Simpan Data' }}
                            </span>
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>

    <!-- Modal Report -->
    <div v-if="showReport" class="fixed inset-0 z-10 overflow-y-auto" role="dialog" aria-labelledby="modal-title" aria-modal="true">
        <div class="flex min-h-screen items-end justify-center px-4 pt-4 text-center sm:block sm:p-0">
            <div @click="closeReport" class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>
            <div
                class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-4xl sm:align-middle"
            >
                <div class="bg-white p-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 w-full text-center sm:mt-0 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium" id="modal-title">Hasil Penilaian Aspek</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Ringkasan hasil penilaian dari semua pertanyaan yang terlihat ({{ completionProgress }}% selesai).
                                </p>
                            </div>
                            <div class="mt-4 max-h-[60vh] overflow-y-auto pr-2">
                                <!-- Gunakan `visibleAspectGroups` juga di modal report -->
                                <div v-for="group in visibleAspectGroups" :key="group.id" class="mb-6 last:mb-0">
                                    <h4 class="mb-3 text-lg font-semibold">{{ group.name }}</h4>
                                    <div class="grid gap-4">
                                        <div v-for="item in group.aspects" :key="`report-${item.id}`" class="rounded-lg border p-4">
                                            <div class="mb-2 flex items-center justify-between">
                                                <h5 class="font-medium">{{ item.id }}. {{ item.question }}</h5>
                                                <span
                                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                                    :class="{
                                                        'bg-green-100 text-green-800': item.value !== null && item.value !== undefined,
                                                        'bg-gray-100 text-gray-800': item.value === null || item.value === undefined,
                                                    }"
                                                >
                                                    {{
                                                        item.value !== null && item.value !== undefined
                                                            ? getOptionsForQuestion(item).find((opt) => opt.id === item.value)?.option_text ||
                                                              'Tidak diketahui'
                                                            : 'Belum dinilai'
                                                    }}
                                                </span>
                                            </div>
                                            <div v-if="item.notes" class="rounded-md bg-gray-100 p-3 text-sm">
                                                <span class="font-medium">Keterangan:</span> {{ item.notes }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button
                        type="button"
                        @click="closeReport"
                        class="inline-flex w-full justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
table {
    border-spacing: 0;
}

@media (max-width: 768px) {
    .container {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
</style>
