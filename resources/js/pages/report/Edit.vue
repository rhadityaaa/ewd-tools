<template>
    <AppLayout title="Edit Laporan">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Edit Laporan - {{ report.borrower?.name }}
                </h2>
                <div class="flex space-x-2">
                    <Link :href="route('reports.show', report.id)" class="text-blue-600 hover:text-blue-800">
                        <Button variant="outline">
                            <ArrowLeftIcon class="h-4 w-4 mr-2" />
                            Kembali
                        </Button>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Rejection Reason Alert -->
                <div v-if="rejectionReason" class="mb-6">
                    <div class="bg-red-50 border border-red-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <AlertCircleIcon class="h-5 w-5 text-red-400" />
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">
                                    Laporan Ditolak
                                </h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <p><strong>Alasan Penolakan:</strong> {{ rejectionReason }}</p>
                                    <p class="mt-2">Silakan perbaiki data laporan sesuai dengan alasan penolakan di atas.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Edit -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Edit Form Laporan</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Silakan perbaiki data laporan sesuai dengan alasan penolakan.
                        </p>
                    </div>
                    
                    <form @submit.prevent="submitForm" class="p-6">
                        <!-- Step 1: Information Borrower -->
                        <div class="mb-8">
                            <h4 class="text-lg font-semibold mb-4">1. Informasi Debitur</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Debitur</label>
                                    <select v-model="form.informationBorrower.borrowerId" required class="w-full border border-gray-300 rounded-md px-3 py-2">
                                        <option value="">Pilih debitur</option>
                                        <option v-for="borrower in borrowers" :key="borrower.id" :value="borrower.id">
                                            {{ borrower.name }}
                                        </option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Grup Debitur</label>
                                    <input 
                                        v-model="form.informationBorrower.borrowerGroup" 
                                        type="text"
                                        required 
                                        class="w-full border border-gray-300 rounded-md px-3 py-2"
                                    />
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Tujuan</label>
                                    <select v-model="form.informationBorrower.purpose" required class="w-full border border-gray-300 rounded-md px-3 py-2">
                                        <option value="kie">KIE</option>
                                        <option value="kmke">KMKE</option>
                                        <option value="both">Both</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Sektor Ekonomi</label>
                                    <input 
                                        v-model="form.informationBorrower.economicSector" 
                                        type="text"
                                        required 
                                        class="w-full border border-gray-300 rounded-md px-3 py-2"
                                    />
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Bidang Usaha</label>
                                    <input 
                                        v-model="form.informationBorrower.businessField" 
                                        type="text"
                                        required 
                                        class="w-full border border-gray-300 rounded-md px-3 py-2"
                                    />
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Usaha Debitur</label>
                                    <input 
                                        v-model="form.informationBorrower.borrowerBusiness" 
                                        type="text"
                                        required 
                                        class="w-full border border-gray-300 rounded-md px-3 py-2"
                                    />
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Kolektibilitas</label>
                                    <select v-model="form.informationBorrower.collectibility" required class="w-full border border-gray-300 rounded-md px-3 py-2">
                                        <option :value="1">1 - Lancar</option>
                                        <option :value="2">2 - DPK</option>
                                        <option :value="3">3 - Kurang Lancar</option>
                                        <option :value="4">4 - Diragukan</option>
                                        <option :value="5">5 - Macet</option>
                                    </select>
                                </div>
                                
                                <div class="flex items-center">
                                    <input 
                                        v-model="form.informationBorrower.restructuring" 
                                        type="checkbox"
                                        id="restructuring"
                                        class="mr-2"
                                    />
                                    <label for="restructuring" class="text-sm font-medium text-gray-700">Restrukturisasi</label>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Facilities -->
                        <div class="mb-8">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-semibold">2. Fasilitas</h4>
                                <button type="button" @click="addFacility" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                    <PlusIcon class="h-4 w-4 mr-2 inline" />
                                    Tambah Fasilitas
                                </button>
                            </div>
                            
                            <div v-for="(facility, index) in form.facilitiesBorrower" :key="index" class="border rounded-lg p-4 mb-4">
                                <div class="flex items-center justify-between mb-4">
                                    <h5 class="font-medium">Fasilitas {{ index + 1 }}</h5>
                                    <button 
                                        v-if="form.facilitiesBorrower.length > 1"
                                        type="button" 
                                        @click="removeFacility(index)" 
                                        class="text-red-600 hover:text-red-800"
                                    >
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Fasilitas</label>
                                        <input v-model="facility.name" type="text" required class="w-full border border-gray-300 rounded-md px-3 py-2" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Limit</label>
                                        <input v-model="facility.limit" type="number" min="0" required class="w-full border border-gray-300 rounded-md px-3 py-2" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Outstanding</label>
                                        <input v-model="facility.outstanding" type="number" min="0" required class="w-full border border-gray-300 rounded-md px-3 py-2" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Suku Bunga (%)</label>
                                        <input v-model="facility.interestRate" type="number" min="0" step="0.01" required class="w-full border border-gray-300 rounded-md px-3 py-2" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Tunggakan Pokok</label>
                                        <input v-model="facility.principalArrears" type="number" min="0" required class="w-full border border-gray-300 rounded-md px-3 py-2" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Tunggakan Bunga</label>
                                        <input v-model="facility.interestArrears" type="number" min="0" required class="w-full border border-gray-300 rounded-md px-3 py-2" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">PDO (hari)</label>
                                        <input v-model="facility.pdo" type="number" min="0" required class="w-full border border-gray-300 rounded-md px-3 py-2" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Jatuh Tempo</label>
                                        <input v-model="facility.maturityDate" type="date" required class="w-full border border-gray-300 rounded-md px-3 py-2" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Aspects -->
                        <div class="mb-8">
                            <h4 class="text-lg font-semibold mb-4">3. Penilaian Aspek</h4>
                            
                            <div v-for="aspectGroup in formData.aspectGroups" :key="aspectGroup.id" class="mb-6">
                                <h5 class="font-medium mb-4">{{ aspectGroup.name }}</h5>
                                
                                <div v-for="question in aspectGroup.questions" :key="question.id" class="border rounded-lg p-4 mb-4">
                                    <h6 class="font-medium mb-2">{{ question.text }}</h6>
                                    
                                    <div class="space-y-2">
                                        <div v-for="option in question.options" :key="option.id" class="flex items-center space-x-2">
                                            <input 
                                                :id="`question_${question.id}_option_${option.id}`"
                                                :name="`question_${question.id}`"
                                                type="radio" 
                                                :value="option.id"
                                                v-model="getQuestionAnswer(question.id).selectedOptionId"
                                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                            />
                                            <label :for="`question_${question.id}_option_${option.id}`" class="text-sm">
                                                {{ option.text }} ({{ option.score }} poin)
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (opsional)</label>
                                        <textarea 
                                            v-model="getQuestionAnswer(question.id).notes"
                                            placeholder="Tambahkan catatan jika diperlukan"
                                            rows="2"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2"
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex space-x-4 pt-6 border-t border-gray-200">
                            <button type="submit" :disabled="form.processing" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 disabled:opacity-50">
                                <SaveIcon class="h-4 w-4 mr-2 inline" />
                                {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                            </button>
                            
                            <button type="button" @click="submitAndResubmit" :disabled="form.processing" class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700 disabled:opacity-50">
                                <SendIcon class="h-4 w-4 mr-2 inline" />
                                {{ form.processing ? 'Memproses...' : 'Simpan & Submit Ulang' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Link } from '@inertiajs/vue3'
import { 
    ArrowLeftIcon,
    AlertCircleIcon,
    PlusIcon,
    TrashIcon,
    SaveIcon,
    SendIcon
} from 'lucide-vue-next'

const props = defineProps({
    report: {
        type: Object,
        required: true
    },
    formData: {
        type: Object,
        required: true
    },
    existingAnswers: {
        type: Object,
        default: () => ({})
    },
    borrowers: {
        type: Array,
        default: () => []
    },
    activePeriod: {
        type: Object,
        default: () => null
    },
    rejectionReason: {
        type: String,
        default: ''
    }
})

// Form data
const form = useForm({
    informationBorrower: {
        borrowerId: props.report.borrower_id,
        borrowerGroup: props.report.borrower?.borrower_detail?.borrower_group || '',
        purpose: props.report.borrower?.borrower_detail?.purpose || 'kie',
        economicSector: props.report.borrower?.borrower_detail?.economic_sector || '',
        businessField: props.report.borrower?.borrower_detail?.business_field || '',
        borrowerBusiness: props.report.borrower?.borrower_detail?.borrower_business || '',
        collectibility: props.report.borrower?.borrower_detail?.collectibility || 1,
        restructuring: props.report.borrower?.borrower_detail?.restructuring || false,
    },
    facilitiesBorrower: props.report.borrower?.facilities?.map(facility => ({
        name: facility.facility_name,
        limit: facility.limit,
        outstanding: facility.outstanding,
        interestRate: facility.interest_rate,
        principalArrears: facility.principal_arrears,
        interestArrears: facility.interest_arrears,
        pdo: facility.pdo_days,
        maturityDate: facility.maturity_date,
    })) || [{
        name: '',
        limit: 0,
        outstanding: 0,
        interestRate: 0,
        principalArrears: 0,
        interestArrears: 0,
        pdo: 0,
        maturityDate: new Date().toISOString().split('T')[0],
    }],
    aspectsBorrower: []
})

// Initialize aspects answers
const initializeAspects = () => {
    const aspects = []
    
    if (props.formData.aspectGroups) {
        props.formData.aspectGroups.forEach(group => {
            group.questions.forEach(question => {
                const existingAnswer = props.existingAnswers[question.id]
                aspects.push({
                    questionId: question.id,
                    selectedOptionId: existingAnswer?.selectedOptionId || null,
                    notes: existingAnswer?.notes || ''
                })
            })
        })
    }
    
    form.aspectsBorrower = aspects
}

// Get question answer helper
const getQuestionAnswer = (questionId) => {
    let answer = form.aspectsBorrower.find(a => a.questionId === questionId)
    if (!answer) {
        answer = {
            questionId: questionId,
            selectedOptionId: null,
            notes: ''
        }
        form.aspectsBorrower.push(answer)
    }
    return answer
}

// Facility management
const addFacility = () => {
    form.facilitiesBorrower.push({
        name: '',
        limit: 0,
        outstanding: 0,
        interestRate: 0,
        principalArrears: 0,
        interestArrears: 0,
        pdo: 0,
        maturityDate: new Date().toISOString().split('T')[0],
    })
}

const removeFacility = (index) => {
    if (form.facilitiesBorrower.length > 1) {
        form.facilitiesBorrower.splice(index, 1)
    }
}

// Form submission
const submitForm = () => {
    form.put(route('reports.update', props.report.id), {
        onSuccess: () => {
            // Success handled by redirect
        },
        onError: (errors) => {
            console.error('Update errors:', errors)
        }
    })
}

const submitAndResubmit = () => {
    form.put(route('reports.update', props.report.id), {
        onSuccess: () => {
            // After successful update, resubmit for approval
            form.post(route('reports.resubmit', props.report.id))
        },
        onError: (errors) => {
            console.error('Update errors:', errors)
        }
    })
}

// Initialize on mount
onMounted(() => {
    initializeAspects()
})
</script>