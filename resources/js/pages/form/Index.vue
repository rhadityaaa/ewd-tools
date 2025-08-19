<script setup>
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Stepper, StepperItem, StepperSeparator, StepperTitle, StepperTrigger } from '@/components/ui/stepper';
import AspectForm from '@/pages/form/AspectForm.vue';
import FacilityForm from '@/pages/form/FacilityForm.vue';
import InformationForm from '@/pages/form/InformationForm.vue';
import { useFormStore } from '@/stores/formStore';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { Calculator, IdCard, ListCheck } from 'lucide-vue-next';
import { computed, onMounted, provide, ref } from 'vue';
import { useToast } from 'vue-toastification';

const toast = useToast();
const page = usePage();

// Props dari controller
const props = defineProps({
    borrowers: {
        type: Array,
        default: () => [],
    },
    aspect_groups: {
        type: Array,
        default: () => [],
    },
    borrower_id: {
        type: Number,
        default: null,
    },
    template_id: {
        type: Number,
        default: null,
    },
    borrower_data: {
        type: Object,
        default: () => ({}),
    },
    facility_data: {
        type: Array,
        default: () => [],
    },
    active_period: {
        type: Object,
        default: () => ({}),
    }
});

console.log('Form props:', props);

const formState = useFormStore();
const isSaving = ref(false);
const lastSavedStep = ref(null);

// Set initial data ke formStore saat komponen dimount
onMounted(() => {
    // Set template_id dan period_id
    if (props.template_id) {
        formState.updateReportMeta({
            template_id: props.template_id
        });
    }
    
    if (props.active_period?.id) {
        formState.updateReportMeta({
            period_id: props.active_period.id
        });
    }

    // Load existing borrower data if available
    if (props.borrower_data && Object.keys(props.borrower_data).length > 0) {
        formState.updateInformationBorrower({
            borrowerId: props.borrower_data.borrower_id || props.borrower_id,
            borrowerGroup: props.borrower_data.borrower_group || '',
            purpose: props.borrower_data.purpose || 'kie',
            economicSector: props.borrower_data.economic_sector || '',
            businessField: props.borrower_data.business_field || '',
            borrowerBusiness: props.borrower_data.borrower_business || '',
            collectibility: props.borrower_data.collectibility || 1,
            restructuring: props.borrower_data.restructuring || false,
        });
    }

    // Load existing facility data if available
    if (props.facility_data && props.facility_data.length > 0) {
        formState.facilitiesBorrower = props.facility_data.map(facility => ({
            id: facility.id || null,
            name: facility.name || '',
            limit: facility.limit || 0,
            outstanding: facility.outstanding || 0,
            interestRate: facility.interest_rate || 0,
            principalArrears: facility.principal_arrears || 0,
            interestArrears: facility.interest_arrears || 0,
            pdo: facility.pdo || 0,
            maturityDate: facility.maturity_date ? new Date(facility.maturity_date) : new Date(),
        }));
    }
});

// Form untuk submit semua data
const form = useForm({
    informationBorrower: {},
    facilitiesBorrower: [],
    aspectsBorrower: [],
    reportMeta: {},
});

// Fungsi untuk menyimpan data step secara background
const saveStepData = async (stepType, data, showToast = true) => {
    if (isSaving.value) {
        console.log('Already saving, skipping...');
        return;
    }

    isSaving.value = true;
    
    try {
        console.log(`Saving step data for ${stepType}:`, data);
        
        const payload = {
            step_type: stepType,
            data: data,
            template_id: props.template_id,
            borrower_id: formState.informationBorrower.borrowerId || props.borrower_id,
            period_id: props.active_period?.id
        };

        const response = await axios.post(route('forms.save-step'), payload, {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        console.log('Save step response:', response.data);

        if (response.data.success) {
            lastSavedStep.value = stepType;
            
            if (showToast) {
                toast.success(`Data ${getStepName(stepType)} berhasil disimpan`);
            }

            // Update aspect groups jika ada perubahan template
            if (response.data.aspect_groups && response.data.aspect_groups.length > 0) {
                // Update props aspect_groups untuk re-render AspectForm
                Object.assign(props, {
                    aspect_groups: response.data.aspect_groups,
                    template_id: response.data.template_id
                });
                
                console.log('Updated aspect groups:', response.data.aspect_groups.length);
                
                // Tampilkan notifikasi jika template berubah
                if (response.data.template_changed) {
                    toast.info(`Template berubah ke Template ${response.data.template_id}`);
                }
            }

            return response.data;
        } else {
            throw new Error(response.data.message || 'Gagal menyimpan data');
        }
    } catch (error) {
        console.error('Error saving step data:', error);
        
        let errorMessage = 'Gagal menyimpan data step';
        if (error.response?.data?.message) {
            errorMessage = error.response.data.message;
        } else if (error.response?.data?.errors) {
            const errors = Object.values(error.response.data.errors).flat();
            errorMessage = errors.join(', ');
        } else if (error.message) {
            errorMessage = error.message;
        }
        
        if (showToast) {
            toast.error(errorMessage);
        }
        
        throw error;
    } finally {
        isSaving.value = false;
    }
};

// Helper function untuk mendapatkan nama step
const getStepName = (stepType) => {
    const stepNames = {
        'borrower': 'Informasi Debitur',
        'facility': 'Fasilitas Debitur',
        'aspect': 'Aspek Debitur'
    };
    return stepNames[stepType] || stepType;
};

// Auto-save functionality dengan debounce
const autoSaveTimeout = ref(null);
const scheduleAutoSave = (stepType, data, delay = 2000) => {
    if (autoSaveTimeout.value) {
        clearTimeout(autoSaveTimeout.value);
    }
    
    autoSaveTimeout.value = setTimeout(async () => {
        try {
            await saveStepData(stepType, data, false); // false = don't show toast for auto-save
            console.log(`Auto-saved ${stepType} data`);
        } catch (error) {
            console.error(`Auto-save failed for ${stepType}:`, error);
        }
    }, delay);
};

// Modified step navigation dengan save data
const nextStep = async () => {
    if (formState.activeStep < steps.length) {
        try {
            let stepType, data;
            
            // Tentukan data yang akan disimpan berdasarkan step saat ini
            if (formState.activeStep === 1) {
                stepType = 'borrower';
                data = {
                    borrower_id: formState.informationBorrower.borrowerId,
                    borrower_group: formState.informationBorrower.borrowerGroup,
                    purpose: formState.informationBorrower.purpose,
                    economic_sector: formState.informationBorrower.economicSector,
                    business_field: formState.informationBorrower.businessField,
                    borrower_business: formState.informationBorrower.borrowerBusiness,
                    collectibility: formState.informationBorrower.collectibility,
                    restructuring: formState.informationBorrower.restructuring,
                };
            } else if (formState.activeStep === 2) {
                stepType = 'facility';
                data = formState.facilitiesBorrower.map(facility => ({
                    id: facility.id,
                    name: facility.name,
                    limit: facility.limit,
                    outstanding: facility.outstanding,
                    interest_rate: facility.interestRate,
                    principal_arrears: facility.principalArrears,
                    interest_arrears: facility.interestArrears,
                    pdo: facility.pdo,
                    maturity_date: facility.maturityDate instanceof Date 
                        ? facility.maturityDate.toISOString().split('T')[0] 
                        : facility.maturityDate,
                }));
            } else if (formState.activeStep === 3) {
                stepType = 'aspect';
                data = formState.aspectsBorrower.map(aspect => ({
                    question_id: aspect.questionId,
                    selected_option_id: aspect.selectedOptionId,
                    notes: aspect.notes,
                }));
            }

            // Validasi data sebelum save
            if (!validateStepData(stepType, data)) {
                return;
            }

            // Save data step saat ini
            const result = await saveStepData(stepType, data);
            
            // Jika ada redirect URL (untuk template change), redirect
            if (result?.redirect_url) {
                window.location.href = result.redirect_url;
                return;
            }
            
            // Lanjut ke step berikutnya
            formState.activeStep++;
            
        } catch (error) {
            console.error('Error in nextStep:', error);
            // Tetap lanjut ke step berikutnya meskipun save gagal
            // formState.activeStep++;
        }
    }
};

const prevStep = async () => {
    if (formState.activeStep > 1) {
        try {
            // Kembali ke step sebelumnya
            formState.activeStep--;
            
            // Jika kembali ke step 3 (AspectForm), reload template data
            if (formState.activeStep === 3) {
                const borrowerData = {
                    borrower_id: formState.informationBorrower.borrowerId,
                    borrower_group: formState.informationBorrower.borrowerGroup,
                    purpose: formState.informationBorrower.purpose,
                    economic_sector: formState.informationBorrower.economicSector,
                    business_field: formState.informationBorrower.businessField,
                    borrower_business: formState.informationBorrower.borrowerBusiness,
                    collectibility: formState.informationBorrower.collectibility,
                    restructuring: formState.informationBorrower.restructuring,
                };
                
                const facilityData = formState.facilitiesBorrower.map(facility => ({
                    id: facility.id,
                    name: facility.name,
                    limit: facility.limit,
                    outstanding: facility.outstanding,
                    interest_rate: facility.interestRate,
                    principal_arrears: facility.principalArrears,
                    interest_arrears: facility.interestArrears,
                    pdo: facility.pdo,
                    maturity_date: facility.maturityDate instanceof Date 
                        ? facility.maturityDate.toISOString().split('T')[0] 
                        : facility.maturityDate,
                }));
                
                // Reload template data
                const result = await saveStepData('facility', facilityData, false);
                
                // Update aspect groups jika template berubah
                if (result?.aspect_groups) {
                    Object.assign(props, {
                        aspect_groups: result.aspect_groups,
                        template_id: result.template_id
                    });
                }
            }
        } catch (error) {
            console.error('Error in prevStep:', error);
            // Tetap kembali ke step sebelumnya meskipun reload gagal
        }
    }
};

// Validasi data step
const validateStepData = (stepType, data) => {
    if (stepType === 'borrower') {
        if (!data.borrower_id) {
            toast.error('Pilih debitur terlebih dahulu');
            return false;
        }
        if (!data.purpose) {
            toast.error('Pilih tujuan kredit');
            return false;
        }
    } else if (stepType === 'facility') {
        if (!data || data.length === 0) {
            toast.error('Tambahkan minimal satu fasilitas');
            return false;
        }
        // Validasi setiap fasilitas
        for (let facility of data) {
            if (!facility.name || facility.limit <= 0) {
                toast.error('Lengkapi data fasilitas dengan benar');
                return false;
            }
        }
    }
    return true;
};

// Fungsi untuk submit semua data ke backend
const submitAllData = async () => {
    try {
        // Kumpulkan data dari formStore
        form.informationBorrower = formState.informationBorrower;
        form.facilitiesBorrower = formState.facilitiesBorrower;
        form.aspectsBorrower = formState.aspectsBorrower;
        form.reportMeta = formState.reportMeta;
        
        console.log("DATA FORM: ", form);

        // Validasi data
        if (!form.informationBorrower.borrowerId) {
            toast.error('Data informasi debitur belum lengkap');
            return;
        }

        if (form.facilitiesBorrower.length === 0) {
            toast.error('Data fasilitas debitur belum diisi');
            return;
        }

        if (form.aspectsBorrower.length === 0) {
            toast.error('Data aspek debitur belum diisi');
            return;
        }

        // Validasi aspek - pastikan semua pertanyaan terjawab
        const unansweredAspects = form.aspectsBorrower.filter(aspect => 
            !aspect.selectedOptionId || aspect.selectedOptionId === null
        );
        
        if (unansweredAspects.length > 0) {
            const aspectNames = unansweredAspects.map(aspect => aspect.aspectName).join(', ');
            toast.error(`Silakan jawab semua pertanyaan aspek: ${aspectNames}`);
            return;
        }

        // Validasi reportMeta
        if (!form.reportMeta.template_id) {
            toast.error('Template ID tidak ditemukan');
            return;
        }

        if (!form.reportMeta.period_id) {
            toast.error('Period ID tidak ditemukan');
            return;
        }

        // Submit ke backend
        form.post(route('forms.submit'), {
            onSuccess: (page) => {
                toast.success('Data berhasil disimpan');
                console.log(page.props);
                const reportId = page.props.reportId || page.props.flash?.reportId;
                router.visit(route('summary', { reportId }));
            },
            onError: (errors) => {
                console.error('Submit errors:', errors);
                if (typeof errors === 'object' && errors !== null) {
                    const errorMessages = Object.values(errors).flat();
                    toast.error(errorMessages.join(', ') || 'Gagal menyimpan data');
                } else {
                    toast.error('Gagal menyimpan data');
                }
            },
        });
    } catch (error) {
        console.error('Unexpected error:', error);
        toast.error('Terjadi kesalahan yang tidak terduga');
    }
};

const steps = [
    {
        id: 1,
        title: 'Informasi Debitur',
        component: InformationForm,
        icon: IdCard,
        required: true,
        props: {
            borrowers: props.borrowers,
            borrower_id: props.borrower_id,
        },
    },
    {
        id: 2,
        title: 'Fasilitas Debitur',
        component: FacilityForm,
        icon: Calculator,
        required: true,
        props: {},
    },
    {
        id: 3,
        title: 'Aspek Debitur',
        component: AspectForm,
        icon: ListCheck,
        required: true,
        props: {
            aspect_groups: props.aspect_groups,
            template_id: props.template_id,
        },
    },
];

const currentStep = computed(() => steps[formState.activeStep - 1]);
const currentComponent = computed(() => currentStep.value.component);
const currentProps = computed(() => currentStep.value.props || {});

// Expose functions untuk digunakan oleh child components
const exposedFunctions = {
    saveStepData,
    scheduleAutoSave,
    isSaving: computed(() => isSaving.value),
    lastSavedStep: computed(() => lastSavedStep.value)
};

// Provide functions ke child components
provide('formActions', exposedFunctions);
</script>

<template>
    <div class="min-h-screen">
        <!-- Header -->
        <div class="bg-[#2E3192] p-4 text-white shadow-md dark:bg-[#1A1D68] dark:text-gray-200">
            <Label class="pl-2 text-2xl font-bold">Form Penilaian Debitur</Label>
            <div v-if="isSaving" class="text-sm opacity-75 mt-1">
                <span class="animate-pulse">💾 Menyimpan data...</span>
            </div>
            <div v-else-if="lastSavedStep" class="text-sm opacity-75 mt-1">
                <span>✅ {{ getStepName(lastSavedStep) }} tersimpan</span>
            </div>
        </div>

        <div class="container mx-auto px-4 py-8 sm:px-6 lg:px-8">
            <!-- Stepper -->
            <Stepper class="mx-auto mb-6 flex w-full max-w-4xl items-start gap-4">
                <StepperItem v-for="step in steps" :key="step.id" :step="step.id" class="relative flex w-full flex-col items-center justify-center">
                    <StepperSeparator
                        v-if="step.id < steps.length"
                        class="absolute top-5 right-[calc(-50%+16px)] left-[calc(50%+24px)] mx-1 h-1 rounded-full transition-all duration-300"
                        :class="{
                            'bg-[#2E3192]': step.id < formState.activeStep,
                            'bg-gray-300': step.id >= formState.activeStep,
                        }"
                    />

                    <StepperTrigger as-child>
                        <div
                            class="z-10 h-10 w-10 items-center justify-center rounded-full border-2 transition-all duration-300"
                            :class="{
                                'bg-[#1A1D68] text-white shadow-lg dark:text-gray-200': formState.activeStep === step.id,
                                'border-gray-300 bg-transparent text-gray-500': formState.activeStep < step.id,
                                'bg-[#2E3192] text-white dark:text-gray-200': formState.activeStep > step.id,
                            }"
                        >
                            <component :is="step.icon" class="h-5 w-5" />
                        </div>
                    </StepperTrigger>

                    <div class="flex flex-col items-center text-center">
                        <StepperTitle
                            :class="{
                                'text-[#1A1D68]': formState.activeStep === step.id,
                                'text-gray-500': formState.activeStep !== step.id,
                            }"
                            class="text-sm font-medium"
                        >
                            {{ step.title }}
                        </StepperTitle>
                    </div>
                </StepperItem>
            </Stepper>

            <!-- Component dengan props -->
            <component
                :is="currentComponent"
                v-bind="currentProps"
                @next="nextStep"
                @prev="formState.prevStep"
                class="container mx-auto mb-4 sm:mb-6"
            />

            <div class="mx-auto mt-6 flex max-w-4xl flex-col justify-end gap-4 sm:flex-row lg:max-w-6xl lg:gap-6 lg:px-8">
                <Link v-if="formState.activeStep === 1" :href="route('dashboard')">
                    <Button variant="outline" class="w-full min-w-2 sm:w-auto lg:min-w-32 lg:px-8 lg:py-3">Beranda</Button>
                </Link>

                <Button
                    v-if="formState.activeStep > 1"
                    @click="prevStep"
                    variant="outline"
                    class="w-full min-w-24 sm:w-auto lg:min-w-32 lg:px-8 lg:py-3"
                    :disabled="isSaving"
                >Back</Button>

                <Button
                    v-if="formState.activeStep < formState.totalSteps"
                    @click="nextStep"
                    class="w-full min-w-24 sm:w-auto lg:min-w-32 lg:px-8 lg:py-3"
                    :disabled="isSaving"
                >
                    {{ isSaving ? 'Menyimpan...' : 'Next' }}
                </Button>

                <Button
                    v-if="formState.activeStep === formState.totalSteps"
                    @click="submitAllData"
                    :disabled="form.processing || isSaving"
                    class="w-full min-w-24 sm:w-auto lg:min-w-32 lg:px-8 lg:py-3"
                >
                    {{ form.processing || isSaving ? 'Menyimpan...' : 'Submit' }}
                </Button>
            </div>
        </div>
    </div>
</template>
