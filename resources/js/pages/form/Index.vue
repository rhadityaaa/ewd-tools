<script setup>
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Stepper, StepperItem, StepperSeparator, StepperTitle, StepperTrigger } from '@/components/ui/stepper';
import AspectForm from '@/pages/form/AspectForm.vue';
import FacilityForm from '@/pages/form/FacilityForm.vue';
import InformationForm from '@/pages/form/InformationForm.vue';
import { useFormStore } from '@/stores/formStore';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Calculator, IdCard, ListCheck } from 'lucide-vue-next';
import { computed, onMounted } from 'vue';
import { useToast } from 'vue-toastification';

const toast = useToast();
const page = usePage();

// Akses activePeriod dari middleware
const activePeriod = computed(() => page.props.activePeriod);

console.log("activePeriod:", activePeriod.value);

// Tambahkan props
const props = defineProps({
    borrowers: {
        type: Array,
        default: () => [],
    },
    aspectGroups: {
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
});

const formState = useFormStore();

// Set template_id dan period_id ke formStore saat komponen dimount
onMounted(() => {
    if (props.template_id) {
        formState.updateReportMeta({
            template_id: props.template_id
        });
    }
    
    // Set period_id dari activePeriod
    if (activePeriod.value?.id) {
        formState.updateReportMeta({
            period_id: activePeriod.value.id
        });
    }
});

// Form untuk submit semua data
const form = useForm({
    informationBorrower: {},
    facilitiesBorrower: [],
    aspectsBorrower: [],
    reportMeta: {},
});

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

        // Submit ke backend
        form.post(route('forms.submit'), {
            onSuccess: (page) => {
                toast.success('Data berhasil disimpan');
                console.log(page.props);
                const reportId = page.props.reportId || page.props.flash?.reportId;
                if (reportId) {
                    router.visit(route('summary', { reportId }));
                } else {
                    router.visit(route('dashboard'));
                }
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
            aspectGroups: props.aspectGroups,
        },
    },
];

const currentStep = computed(() => steps[formState.activeStep - 1]);
const currentComponent = computed(() => currentStep.value.component);
const currentProps = computed(() => currentStep.value.props || {});

// Add method to save step data
const saveStepData = async (stepType, data) => {
    try {
        const response = await axios.post(route('forms.save-step'), {
            step_type: stepType,
            data: data
        });
        
        // Jika ada template_id yang dikembalikan, redirect ke form dengan template_id
        if (response.data.template_id && response.data.redirect_url) {
            window.location.href = response.data.redirect_url;
        }
        
        return response.data;
    } catch (error) {
        console.error('Error saving step data:', error);
        throw error;
    }
};

// Modify step navigation to save data and reload form
const nextStep = async () => {
    if (formState.activeStep < steps.length) {
        try {
            // Save current step data
            if (formState.activeStep === 1) {
                await saveStepData('borrower', formState.informationBorrower);
            } else if (formState.activeStep === 2) {
                const result = await saveStepData('facility', formState.facilitiesBorrower);
                // Jika ada template_id, halaman akan di-redirect otomatis
                if (result.template_id) {
                    return; // Stop execution karena akan redirect
                }
            }
            
            formState.activeStep++;
        } catch (error) {
            toast.error('Gagal menyimpan data step');
        }
    }
};
</script>

<template>
    <div class="min-h-screen">
        <!-- Header -->

        <div class="bg-[#2E3192] p-4 text-white shadow-md dark:bg-[#1A1D68] dark:text-gray-200">
            <Label class="pl-2 text-2xl font-bold">Form Penilaian Debitur</Label>
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
                @next="formState.nextStep"
                @prev="formState.prevStep"
                class="container mx-auto mb-4 sm:mb-6"
            />

            <div class="mx-auto mt-6 flex max-w-4xl flex-col justify-end gap-4 sm:flex-row lg:max-w-6xl lg:gap-6 lg:px-8">
                <Link v-if="formState.activeStep === 1" :href="route('dashboard')">
                    <Button variant="outline" class="w-full min-w-2 sm:w-auto lg:min-w-32 lg:px-8 lg:py-3">Beranda</Button>
                </Link>

                <Button
                    v-if="formState.activeStep > 1"
                    @click="formState.prevStep"
                    variant="outline"
                    class="w-full min-w-24 sm:w-auto lg:min-w-32 lg:px-8 lg:py-3"
                    >Back</Button
                >

                <Button
                    v-if="formState.activeStep < formState.totalSteps"
                    @click="formState.nextStep"
                    class="w-full min-w-24 sm:w-auto lg:min-w-32 lg:px-8 lg:py-3"
                    >Next</Button
                >

                <!-- <Link :href="route('summary')"> -->
                <Button
                    v-if="formState.activeStep === formState.totalSteps"
                    @click="submitAllData"
                    :disabled="form.processing"
                    class="w-full min-w-24 sm:w-auto lg:min-w-32 lg:px-8 lg:py-3"
                >
                    {{ form.processing ? 'Menyimpan...' : 'Submit' }}
                </Button>
                <!-- </Link> -->
            </div>
        </div>
    </div>
</template>
