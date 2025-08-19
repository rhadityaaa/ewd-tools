<script setup>
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useFormStore } from '@/stores/formStore';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch, nextTick } from 'vue';
import { useToast } from 'vue-toastification';

const toast = useToast();

const props = defineProps({
    aspect_groups: {
        type: Array,
        default: () => [],
    },
    reportId: {
        type: Number,
        default: null,
    },
});


console.log('Aspect groups received:', props.aspect_groups);

// Pinia store
const formStore = useFormStore();

const showReport = ref(false);
const isSubmitting = ref(false);
const lastSaved = ref(null);
const isMobile = ref(false);

// Initialize aspectGroups with proper reactive structure
const aspectGroups = ref([]);

// Function to initialize aspect groups
const initializeAspectGroups = (groups) => {
    return (groups || []).map((group) => ({
        ...group,
        aspects: (group.aspects || []).map((aspect) => {
            const existingAspect = formStore.aspectsBorrower.find((a) => a.questionId === aspect.id);
            return {
                ...aspect,
                value: existingAspect?.selectedOptionId || null,
                notes: existingAspect?.notes || '',
            };
        }),
    }));
};

// Initialize aspectGroups
aspectGroups.value = initializeAspectGroups(props.aspect_groups);

// Watch for props changes
watch(
    () => props.aspect_groups,
    (newAspectGroups) => {
        console.log('Props aspect_groups updated from backend:', newAspectGroups);
        aspectGroups.value = initializeAspectGroups(newAspectGroups);
        updateFormStore();
    },
    { deep: true, immediate: false }
);

// Function to update form store
const updateFormStore = () => {
    const aspectsData = [];
    if (aspectGroups.value && aspectGroups.value.length > 0) {
        aspectGroups.value.forEach((group) => {
            if (group.aspects && group.aspects.length > 0) {
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
            }
        });
    }
    formStore.updateAspectsBorrower(aspectsData);
};

// Initialize store on mount
onMounted(() => {
    updateFormStore();
    if (typeof window !== 'undefined') {
        checkScreenSize();
        window.addEventListener('resize', checkScreenSize);
    }
});

// Visibility logic for individual questions
const isVisible = (entity) => {
    if (!entity.visibility_rules || entity.visibility_rules.length === 0) {
        return true;
    }

    return entity.visibility_rules.every((rule) => {
        let sourceValue;
        const sourceField = rule.source_field;

        try {
            if (rule.source_type === 'borrower_detail') {
                sourceValue = formStore.informationBorrower?.[sourceField];
            } else if (rule.source_type === 'borrower_facility') {
                if (formStore.getTotalByKey && typeof formStore.getTotalByKey === 'function') {
                    sourceValue = formStore.getTotalByKey(sourceField);
                } else {
                    sourceValue = formStore.facilitiesBorrower?.reduce((total, facility) => {
                        const value = parseFloat(facility[sourceField]) || 0;
                        return total + value;
                    }, 0) || 0;
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
        } catch (error) {
            console.error('Error in visibility check:', error, { rule });
            return false;
        }
    });
};

// Template visibility logic
const isTemplateVisible = (templateRules) => {
    if (!templateRules || templateRules.length === 0) {
        return true;
    }

    try {
        const result = templateRules.every((rule) => {
            let sourceValue;
            const sourceField = rule.source_field;

            if (rule.source_type === 'borrower_detail') {
                sourceValue = formStore.informationBorrower?.[sourceField];
            } else if (rule.source_type === 'borrower_facility') {
                if (formStore.getTotalByKey && typeof formStore.getTotalByKey === 'function') {
                    sourceValue = formStore.getTotalByKey(sourceField);
                } else {
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

            // Handle null/undefined values for numeric comparisons
            if (sourceValue === undefined || sourceValue === null) {
                if (['>', '<', '>=', '<='].includes(rule.operator)) {
                    sourceValue = 0;
                } else {
                    console.warn(`Template visibility: Source value for '${sourceField}' is undefined/null`, { rule });
                    return false;
                }
            }

            const ruleResult = compareValues(sourceValue, rule.operator, rule.value);
            
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
    } catch (error) {
        console.error('Error in template visibility check:', error);
        return false;
    }
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
                console.warn(`Unknown operator: ${operator}`);
                return false;
        }
    } catch (e) {
        console.error(`Error comparing values:`, { sourceValue, operator, targetValue, error: e });
        return false;
    }
};

// Computed property for visible aspect groups
const visibleAspectGroups = computed(() => {
    if (!aspectGroups.value || aspectGroups.value.length === 0) {
        return [];
    }

    console.log('Processing aspect groups:', aspectGroups.value.length);

    return aspectGroups.value
        .filter(group => {
            // Check template-level visibility rules first
            if (group.template_visibility_rules && group.template_visibility_rules.length > 0) {
                const templateVisible = isTemplateVisible(group.template_visibility_rules);
                console.log(`Template visibility for group ${group.id}:`, {
                    rules: group.template_visibility_rules,
                    visible: templateVisible
                });
                
                if (!templateVisible) {
                    return false;
                }
            }
            
            // Check question-level visibility
            const visibleAspects = group.aspects?.filter(aspect => {
                const aspectVisible = isVisible(aspect);
                console.log(`Question visibility for aspect ${aspect.id}:`, {
                    rules: aspect.visibility_rules,
                    visible: aspectVisible
                });
                return aspectVisible;
            }) || [];
            
            return visibleAspects.length > 0;
        })
        .map(group => ({
            ...group,
            aspects: group.aspects?.filter(aspect => isVisible(aspect)) || []
        }));
});

const getOptionsForQuestion = (aspect) => {
    if (aspect.options && aspect.options.length > 0) {
        return aspect.options;
    }
    return [
        { id: 1, option_text: 'Ya', score: 1 },
        { id: 0, option_text: 'Tidak', score: 0 },
    ];
};

// Watch for changes in aspectGroups and sync with store
watch(
    aspectGroups,
    (newAspectGroups) => {
        const updatedAspectsData = [];
        
        newAspectGroups.forEach((group) => {
            if (group.aspects && group.aspects.length > 0) {
                group.aspects.forEach((aspect) => {
                    const aspectData = {
                        questionId: aspect.id,
                        questionText: aspect.question,
                        aspectName: group.name,
                        aspectCode: group.code || `ASP_${group.id}`,
                        options: aspect.options && aspect.options.length > 0
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
                    };
                    
                    updatedAspectsData.push(aspectData);
                });
            }
        });
        
        formStore.updateAspectsBorrower(updatedAspectsData);
    },
    { deep: true, immediate: false }
);

const form = useForm({
    aspects: [],
    report_meta: {
        template_id: null,
        period_id: null,
        borrower_id: null,
    },
    report_id: props.reportId,
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

// Fixed function to get aspect from store
const getAspectFromStore = (questionId) => {
    const aspect = formStore.aspectsBorrower.find(a => a.questionId === questionId);
    return aspect || { selectedOptionId: null, notes: '' };
};

// Fixed function to update aspect in store
const updateAspectInStore = (questionId, selectedOptionId, notes = '') => {
    const aspectIndex = formStore.aspectsBorrower.findIndex(a => a.questionId === questionId);
    if (aspectIndex !== -1) {
        formStore.aspectsBorrower[aspectIndex].selectedOptionId = selectedOptionId;
        formStore.aspectsBorrower[aspectIndex].notes = notes;
    }
    
    // Also update local aspectGroups
    aspectGroups.value.forEach(group => {
        const aspect = group.aspects.find(a => a.id === questionId);
        if (aspect) {
            aspect.value = selectedOptionId;
            aspect.notes = notes;
        }
    });
};

// Fixed submit form function
const submitForm = async () => {
    if (isSubmitting.value || !isFormValid.value) {
        if (!isFormValid.value) {
            toast.error('Mohon lengkapi semua pertanyaan yang wajib diisi.');
        }
        return;
    }
    
    isSubmitting.value = true;

    try {
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

        const endpoint = props.reportId ? route('aspects.update', props.reportId) : route('aspects.store');
        const method = props.reportId ? 'put' : 'post';

        form[method](endpoint, {
            onSuccess: () => {
                toast.success(props.reportId ? 'Data aspek berhasil diperbarui' : 'Data aspek berhasil disimpan');
                if (!props.reportId) {
                    formStore.nextStep();
                }
            },
            onError: (errors) => {
                console.error('Submission errors:', errors);
                toast.error('Gagal menyimpan data aspek. Periksa kembali isian Anda.');
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        });
    } catch (error) {
        console.error('Error in submitForm:', error);
        toast.error('Terjadi kesalahan saat menyimpan data.');
        isSubmitting.value = false;
    }
};

const checkScreenSize = () => {
    isMobile.value = window.innerWidth < 768;
};

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
                                aspectGroups: props.aspect_groups?.length || 0,
                                borrowerData: formStore.informationBorrower,
                                facilityData: formStore.facilitiesBorrower,
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
                                            <Select 
                                                :model-value="aspect.value" 
                                                @update:model-value="(value) => updateAspectInStore(aspect.id, value, aspect.notes)"
                                            >
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
                                            <Textarea 
                                                :model-value="aspect.notes" 
                                                @update:model-value="(value) => updateAspectInStore(aspect.id, aspect.value, value)"
                                                placeholder="Masukkan keterangan..." 
                                                class="min-h-[60px]" 
                                            />
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
                                <div class="mb-3">
                                    <h3 class="font-medium text-gray-900">
                                        {{ aspect.id }}. {{ aspect.question }}
                                        <span v-if="aspect.is_mandatory" class="ml-1 text-red-500">*</span>
                                    </h3>
                                    <p v-if="aspect.description" class="mt-1 text-sm text-gray-600">
                                        {{ aspect.description }}
                                    </p>
                                </div>

                                <div class="mb-3">
                                    <label class="mb-2 block text-sm font-medium text-gray-700">Nilai</label>
                                    <Select 
                                        :model-value="aspect.value" 
                                        @update:model-value="(value) => updateAspectInStore(aspect.id, value, aspect.notes)"
                                    >
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
                                    <label class="mb-2 block text-sm font-medium text-gray-700">Keterangan</label>
                                    <Textarea 
                                        :model-value="aspect.notes" 
                                        @update:model-value="(value) => updateAspectInStore(aspect.id, aspect.value, value)"
                                        placeholder="Masukkan keterangan..." 
                                        class="min-h-[60px]" 
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Auto Save Indicator -->
                    <div v-if="lastSaved" class="mb-4 text-xs text-gray-500">Terakhir disimpan otomatis: {{ lastSaved.toLocaleTimeString() }}</div>
                    
                    <!-- Submit Button -->
                    <div class="mt-6 flex justify-end">
                        <Button 
                            type="submit" 
                            :disabled="isSubmitting || !isFormValid"
                            class="px-6 py-2"
                        >
                            {{ isSubmitting ? 'Menyimpan...' : (props.reportId ? 'Update Data' : 'Simpan Data') }}
                        </Button>
                    </div>
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
