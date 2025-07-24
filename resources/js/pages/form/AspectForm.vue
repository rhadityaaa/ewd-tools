<script setup>
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useFormStore } from '@/stores/formStore';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import { useToast } from 'vue-toastification';

const toast = useToast();

// Props from controller
const props = defineProps({
    template_id: {
        type: Number,
        default: null,
    },
    aspect_groups: {
        type: Array,
        default: () => [],
    },
    report_id: {
        type: Number,
        default: null,
    },
    active_period: {
        type: Array,
        default: () => [],
    },
});

console.log('aspectform', props.active_period)

const formStore = useFormStore();

const showReport = ref(false);
const isSubmitting = ref(false);
const lastSaved = ref(null);

// Use ref instead of reactive for aspectGroups
const aspectGroups = ref(
    props.aspect_groups.map((group) => ({
        ...group,
        aspects: group.aspects.map((aspect) => {
            const existingAspect = formStore.aspectsBorrower.find((a) => a.questionId === aspect.id);
            return {
                ...aspect,
                value: existingAspect?.selectedOptionId || null,
                notes: existingAspect?.notes || '',
            };
        }),
    }))
);

// Initialize store with aspect data
onMounted(() => {
    const aspectsData = [];
    aspectGroups.value.forEach((group) => {
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
                visibility_rules: aspect.visibility_rules || [],
            });
        });
    });
    formStore.updateAspectsBorrower(aspectsData);
});

// Visibility logic for individual questions
const isVisible = (entity) => {
    if (!entity.visibility_rules || entity.visibility_rules.length === 0) {
        return true;
    }

    return entity.visibility_rules.every((rule) => {
        let sourceValue;
        const sourceField = rule.source_field;

        if (rule.source_type === 'borrower_detail') {
            sourceValue = formStore.informationBorrower?.[sourceField];
        } else if (rule.source_type === 'borrower_facility') {
            // Use getTotalByKey if available, otherwise check individual facilities
            if (formStore.getTotalByKey && typeof formStore.getTotalByKey === 'function') {
                sourceValue = formStore.getTotalByKey(sourceField);
            } else {
                // Fallback: check if any facility matches
                const facility = formStore.facilitiesBorrower?.find(f => f[sourceField] !== undefined);
                sourceValue = facility ? facility[sourceField] : 0;
            }
        } else if (rule.source_type === 'answer') {
            const sourceAspect = aspectGroups.value.flatMap((g) => g.aspects).find((a) => a.id == sourceField);
            sourceValue = sourceAspect?.value;
        }

        if (sourceValue === undefined || sourceValue === null) {
            console.warn(`Visibility check failed: Source value for '${sourceField}' is undefined.`, { rule });
            return false;
        }

        return compareValues(sourceValue, rule.operator, rule.value);
    });
};

// Template visibility logic - PERBAIKAN
const isTemplateVisible = (templateRules) => {
    if (!templateRules || templateRules.length === 0) {
        return true;
    }

    // Semua rules harus terpenuhi (AND logic)
    const result = templateRules.every((rule) => {
        let sourceValue;
        const sourceField = rule.source_field;

        if (rule.source_type === 'borrower_detail') {
            sourceValue = formStore.informationBorrower?.[sourceField];
        } else if (rule.source_type === 'borrower_facility') {
            // PERBAIKAN: Gunakan getTotalByKey untuk menghitung total dari semua fasilitas
            if (formStore.getTotalByKey && typeof formStore.getTotalByKey === 'function') {
                sourceValue = formStore.getTotalByKey(sourceField);
            } else {
                // Fallback: hitung manual total dari semua fasilitas
                sourceValue = formStore.facilitiesBorrower?.reduce((total, facility) => {
                    const value = parseFloat(facility[sourceField]) || 0;
                    return total + value;
                }, 0) || 0;
            }
        } else if (rule.source_type === 'answer') {
            const answeredAspect = formStore.aspectsBorrower?.find(aspect => 
                aspect.questionId?.toString() === rule.source_field
            );
            sourceValue = answeredAspect ? answeredAspect.selectedOptionId : null;
        }

        // PERBAIKAN: Pastikan sourceValue tidak undefined/null untuk numeric comparison
        if (sourceValue === undefined || sourceValue === null) {
            // Untuk numeric fields, treat as 0
            if (['>', '<', '>=', '<='].includes(rule.operator)) {
                sourceValue = 0;
            } else {
                console.warn(`Template visibility: Source value for '${sourceField}' is undefined/null`, { rule });
                return false;
            }
        }

        const ruleResult = compareValues(sourceValue, rule.operator, rule.value);
        
        // Debug log untuk setiap rule
        console.log('Template rule evaluation:', {
            rule: {
                source_type: rule.source_type,
                source_field: rule.source_field,
                operator: rule.operator,
                value: rule.value
            },
            sourceValue,
            result: ruleResult
        });

        return ruleResult;
    });

    console.log('Template visibility final result:', result);
    return result;
};

const compareValues = (sourceValue, operator, targetValue) => {
    try {
        switch (operator) {
            case '=':
                return sourceValue == targetValue;
            case '!=':
                return sourceValue != targetValue;
            case '>':
                return parseFloat(sourceValue) > parseFloat(targetValue);
            case '<':
                return parseFloat(sourceValue) < parseFloat(targetValue);
            case '>=':
                return parseFloat(sourceValue) >= parseFloat(targetValue);
            case '<=':
                return parseFloat(sourceValue) <= parseFloat(targetValue);
            case 'in':
                const values = targetValue.split(',').map(v => v.trim());
                return values.includes(sourceValue?.toString());
            case 'not_in':
                const notValues = targetValue.split(',').map(v => v.trim());
                return !notValues.includes(sourceValue?.toString());
            case 'contains':
                return String(sourceValue).includes(String(targetValue));
            case 'not_contains':
                return !String(sourceValue).includes(String(targetValue));
            default:
                return false;
        }
    } catch (e) {
        console.error(`Error comparing values:`, { sourceValue, operator, targetValue, error: e });
        return false;
    }
};

// Update visibleAspectGroups to properly handle visibility hierarchy
const visibleAspectGroups = computed(() => {
    if (!aspectGroups.value || aspectGroups.value.length === 0) {
        return [];
    }

    // Debug log untuk troubleshooting
    console.log('Processing aspect groups:', aspectGroups.value.length);

    return aspectGroups.value
        .filter(group => {
            // PRIORITAS 1: Cek template-level visibility rules terlebih dahulu
            if (group.template_visibility_rules && group.template_visibility_rules.length > 0) {
                const templateVisible = isTemplateVisible(group.template_visibility_rules);
                console.log(`Template visibility for group ${group.id}:`, {
                    rules: group.template_visibility_rules,
                    visible: templateVisible
                });
                
                // Jika template tidak visible, langsung return false tanpa cek question visibility
                if (!templateVisible) {
                    return false;
                }
            }
            
            // PRIORITAS 2: Jika template visible (atau tidak ada template rules), 
            // baru cek question-level visibility
            const visibleAspects = group.aspects?.filter(aspect => {
                const aspectVisible = isVisible(aspect);
                console.log(`Question visibility for aspect ${aspect.id}:`, {
                    rules: aspect.visibility_rules,
                    visible: aspectVisible
                });
                return aspectVisible;
            }) || [];
            
            // Group hanya ditampilkan jika ada minimal 1 question yang visible
            return visibleAspects.length > 0;
        })
        .map(group => ({
            ...group,
            // Filter aspects berdasarkan question visibility (setelah template visibility passed)
            aspects: group.aspects?.filter(aspect => isVisible(aspect)) || []
        }));
});

// PERBAIKAN: Hapus implementasi isTemplateVisible yang lama dan bertentangan
// Hapus kode yang di-comment dari baris ~223-267
const getOptionsForQuestion = (aspect) => {
    if (aspect.options && aspect.options.length > 0) {
        return aspect.options;
    }
    return [
        { id: 1, option_text: 'Ya', score: 1 },
        { id: 0, option_text: 'Tidak', score: 0 },
    ];
};

watch(
    () => aspectGroups.value,
    (newAspectGroups) => {
        if (newAspectGroups) {
            newAspectGroups.forEach((group) => {
                group.aspects?.forEach((aspect) => {
                    formStore.updateAspectAnswer(aspect.id, aspect.value, aspect.notes);
                });
            });
        }
    },
    { deep: true }
);

const form = useForm({
    aspects: [],
    report_meta: {
        template_id: null,
        period_id: null,
        borrower_id: null,
    },
    report_id: props.report_id,
});

const getSelectedOptionScore = (aspect) => {
    const options = getOptionsForQuestion(aspect);
    const selectedOption = options.find((opt) => opt.id === aspect.value);
    return selectedOption ? selectedOption.score : 0;
};

const isFormValid = computed(() => {
    return visibleAspectGroups.value.every((group) =>
        group.aspects.every((aspect) => !aspect.is_mandatory || (aspect.value !== null && aspect.value !== undefined))
    );
});

const completionProgress = computed(() => {
    const totalQuestions = visibleAspectGroups.value.reduce((total, group) => total + group.aspects.length, 0);
    if (totalQuestions === 0) return 0;
    const answeredQuestions = visibleAspectGroups.value.reduce(
        (total, group) => total + group.aspects.filter((aspect) => aspect.value !== null && aspect.value !== undefined).length,
        0
    );
    return Math.round((answeredQuestions / totalQuestions) * 100);
});

const submitForm = async () => {
    if (isSubmitting.value || !isFormValid.value) {
        if (!isFormValid.value) toast.error('Mohon lengkapi semua pertanyaan yang wajib diisi.');
        return;
    }
    isSubmitting.value = true;

    const aspectsData = visibleAspectGroups.value.flatMap((group) =>
        group.aspects
            .filter((aspect) => aspect.value !== null && aspect.value !== undefined)
            .map((aspect) => ({
                question_id: aspect.id,
                selected_option_id: aspect.value,
                notes: aspect.notes || null,
                score: getSelectedOptionScore(aspect),
                aspect_group_id: group.id,
            }))
    );

    form.aspects = aspectsData;
    form.report_meta = {
        template_id: formStore.reportMeta?.template_id,
        period_id: formStore.reportMeta?.period_id,
        borrower_id: formStore.informationBorrower?.borrowerId,
    };

    const endpoint = props.report_id ? route('aspects.update', props.report_id) : route('aspects.store');
    const method = props.report_id ? 'put' : 'post';

    form[method](endpoint, {
        onSuccess: () => {
            toast.success(props.report_id ? 'Data aspek berhasil diperbarui' : 'Data aspek berhasil disimpan');
            if (!props.report_id) formStore.nextStep();
        },
        onError: (errors) => {
            console.error('Submission errors:', errors);
            toast.error('Gagal menyimpan data aspek. Periksa kembali isian Anda.');
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};

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

// Other methods
const generateReport = () => (showReport.value = true);
const closeReport = () => (showReport.value = false);
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
                    <!-- Debug info -->
                    <div v-if="visibleAspectGroups.length === 0" class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded">
                        <p class="text-yellow-800">Tidak ada aspek yang ditampilkan. Periksa data borrower dan facility untuk memastikan template visibility rules terpenuhi.</p>
                        <details class="mt-2">
                            <summary class="cursor-pointer text-sm text-yellow-600">Debug Info</summary>
                            <pre class="mt-2 text-xs bg-white p-2 rounded">{{ JSON.stringify({ 
                                aspectGroups: props.aspect_groups.map(
                                    (group) => ({
                                        id: group.id,
                                        name: group.name,
                                        description: group.description,
                                        aspects: group.aspects.map(
                                            (aspect) => ({
                                                id: aspect.id,
                                                question: aspect.question,
                                                description: aspect.description,
                                                is_mandatory: aspect.is_mandatory,
                                            })
                                        ),
                                    })
                                ),
                                borrowerData: formStore.informationBorrower,
                                facilityData: formStore.facilitiesBorrower,
                                template_id: props.template_id,
                                period_id: props.active_period
                            }, null, 2) }}</pre>
                        </details>
                    </div>

                    <!-- Render aspect groups -->
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
                </form>
            </CardContent>
        </Card>
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
