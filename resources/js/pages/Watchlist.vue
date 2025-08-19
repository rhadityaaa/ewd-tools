<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { Label } from '@/components/ui/label';
import { MonitoringNoteProps } from '@/types/monitoring';
import { ref, computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useToast } from 'vue-toastification';

const props = defineProps<MonitoringNoteProps>();
const toast = useToast();

const expandedSections: any = ref({
    information: false,
    monitoring: false,
    actionItems: false,
    watchlist: false,
});

const isLoading = ref(false);
const editingIndex = ref<number | null>(null);
const editingType = ref<string>('');

// Form untuk monitoring note
const monitoringNoteForm = useForm({
    watchlist_reason: props.monitoringNote?.watchlist_reason || '',
    account_strategy: props.monitoringNote?.account_strategy || ''
});

// Forms for inline editing
const editForm = useForm({
    action_description: '',
    progress_notes: '',
    people_in_charge: '',
    notes: '',
    due_date: '',
    status: 'pending' as 'pending' | 'in_progress' | 'completed' | 'overdue'
});

// New item forms
const newPreviousItem = useForm({
    action_description: '',
    item_type: 'previous_period',
    progress_notes: '',
    people_in_charge: '',
    notes: '',
    due_date: '',
    status: 'pending' as 'pending' | 'in_progress' | 'completed' | 'overdue'
});

const newNextItem = useForm({
    action_description: '',
    item_type: 'next_period',
    progress_notes: '',
    people_in_charge: '',
    notes: '',
    due_date: '',
    status: 'pending' as 'pending' | 'in_progress' | 'completed' | 'overdue'
});

// Computed properties
const actionItemsByType = computed(() => ({
    previous_period: props.actionItems.previous_period || [],
    current_progress: props.actionItems.current_progress || [],
    next_period: props.actionItems.next_period || []
}));

const totalActionItems = computed(() => {
    return Object.values(actionItemsByType.value).reduce((total, items) => total + items.length, 0);
});

// Check completion status
const previousPeriodCompletionStatus = computed(() => {
    const items = actionItemsByType.value.previous_period;
    if (items.length === 0) return { completed: 0, total: 0, percentage: 0 };
    
    const completedItems = items.filter(item => item.progress_notes && item.progress_notes.trim() !== '');
    const percentage = Math.round((completedItems.length / items.length) * 100);
    
    return {
        completed: completedItems.length,
        total: items.length,
        percentage
    };
});

// Check NAW completion status
const nawCompletionStatus = computed(() => {
    const hasWatchlistReason = monitoringNoteForm.watchlist_reason && monitoringNoteForm.watchlist_reason.trim() !== '';
    const hasAccountStrategy = monitoringNoteForm.account_strategy && monitoringNoteForm.account_strategy.trim() !== '';
    const hasPreviousProgress = previousPeriodCompletionStatus.value.percentage === 100;
    const hasNextPlan = actionItemsByType.value.next_period.length > 0;
    
    const completedItems = [hasWatchlistReason, hasAccountStrategy, hasPreviousProgress, hasNextPlan].filter(Boolean).length;
    const totalItems = 4;
    const percentage = Math.round((completedItems / totalItems) * 100);
    
    return {
        hasWatchlistReason,
        hasAccountStrategy,
        hasPreviousProgress,
        hasNextPlan,
        completedItems,
        totalItems,
        percentage,
        isComplete: percentage === 100
    };
});

// Utility functions
const formatDate = (date: string) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const getStatusBadgeClass = (status: string) => {
    switch (status) {
        case 'completed': return 'bg-green-100 text-green-800';
        case 'in_progress': return 'bg-blue-100 text-blue-800';
        case 'overdue': return 'bg-red-100 text-red-800';
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const getItemTypeLabel = (type: string) => {
    switch (type) {
        case 'previous_period': return 'Progress Periode Sebelumnya';
        case 'current_progress': return 'Progress Saat Ini';
        case 'next_period': return 'Rencana Periode Berikutnya';
        default: return type;
    }
};

const getItemTypeBadgeClass = (type: string) => {
    switch (type) {
        case 'previous_period': return 'bg-orange-100 text-orange-800';
        case 'current_progress': return 'bg-blue-100 text-blue-800';
        case 'next_period': return 'bg-green-100 text-green-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const toggleSection = (section: any) => {
    expandedSections.value[section] = !expandedSections.value[section];
};

// Save monitoring note
const saveMonitoringNote = () => {
    monitoringNoteForm.patch(`/watchlist/${props.monitoringNote.id}`, {
        onSuccess: () => {
            toast.success('Monitoring note berhasil disimpan!');
        },
        onError: () => {
            toast.error('Terjadi kesalahan saat menyimpan monitoring note.');
        }
    });
};

const startEdit = (item: any, index: number, type: string) => {
    editingIndex.value = index;
    editingType.value = type;
    editForm.action_description = item.action_description;
    editForm.progress_notes = item.progress_notes;
    editForm.people_in_charge = item.people_in_charge;
    editForm.notes = item.notes;
    editForm.due_date = item.due_date;
    editForm.status = item.status;
};

const cancelEdit = () => {
    editingIndex.value = null;
    editingType.value = '';
    editForm.reset();
};

const saveEdit = (item: any) => {
    // For previous period items, only allow updating progress_notes and status
    const updateData = editingType.value === 'previous_period' ? {
        progress_notes: editForm.progress_notes,
        status: editForm.status
    } : {
        action_description: editForm.action_description,
        progress_notes: editForm.progress_notes,
        people_in_charge: editForm.people_in_charge,
        notes: editForm.notes,
        due_date: editForm.due_date,
        status: editForm.status
    };
    
    router.patch(`/watchlist/action-items/${item.id}`, updateData, {
        onSuccess: () => {
            editingIndex.value = null;
            editingType.value = '';
            toast.success('Action item berhasil diperbarui!');
        },
        onError: () => {
            toast.error('Terjadi kesalahan saat menyimpan action item.');
        }
    });
};

const addNewPreviousItem = () => {
    newPreviousItem.post(`/watchlist/${props.monitoringNote.id}/action-items`, {
        onSuccess: () => {
            newPreviousItem.reset();
            toast.success('Action item berhasil ditambahkan!');
        },
        onError: () => {
            toast.error('Terjadi kesalahan saat menambah action item.');
        }
    });
};

const addNewNextItem = () => {
    newNextItem.post(`/watchlist/${props.monitoringNote.id}/action-items`, {
        onSuccess: () => {
            newNextItem.reset();
            toast.success('Rencana tindak lanjut berhasil ditambahkan!');
        },
        onError: () => {
            toast.error('Terjadi kesalahan saat menambah rencana.');
        }
    });
};

const deleteActionItem = (item: any) => {
    if (confirm('Apakah Anda yakin ingin menghapus action item ini?')) {
        router.delete(`/watchlist/action-items/${item.id}`, {
            onSuccess: () => {
                toast.success('Action item berhasil dihapus!');
            },
            onError: () => {
                toast.error('Terjadi kesalahan saat menghapus action item.');
            }
        });
    }
};

const copyFromPrevious = () => {
    isLoading.value = true;
    router.post(`/watchlist/${props.monitoringNote.id}/copy-previous`, {}, {
        onSuccess: () => {
            toast.success('Action item dari periode sebelumnya berhasil disalin!');
        },
        onError: () => {
            toast.error('Terjadi kesalahan saat menyalin action item.');
        },
        onFinish: () => {
            isLoading.value = false;
        }
    });
};

// Submit NAW completion
const submitNAW = () => {
    if (!nawCompletionStatus.value.isComplete) {
        toast.error('Harap lengkapi semua bagian NAW sebelum submit.');
        return;
    }
    
    router.post(`/watchlist/${props.monitoringNote.id}/submit`, {}, {
        onSuccess: () => {
            toast.success('NAW berhasil disubmit!');
        },
        onError: () => {
            toast.error('Terjadi kesalahan saat submit NAW.');
        }
    });
};

// Back to summary function
const backToSummary = () => {
    window.close(); // Close current tab
    // Or redirect to summary
    // window.location.href = `/summary?reportId=${props.report.id}`;
};
</script>

<template>
    <div class="min-h-screen">
        <Head title="Nota Analisa Watchlist"/>
        
        <!-- Header -->
        <div class="bg-orange-600 p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <!-- Back Button -->
                    <Button @click="backToSummary" variant="outline" class="bg-white text-orange-600 hover:bg-gray-100">
                        ← Kembali ke Summary
                    </Button>
                    <div>
                        <Label class="text-2xl font-bold">Nota Analisa Watchlist</Label>
                        <div class="flex items-center mt-2 space-x-4">
                            <Badge :class="nawCompletionStatus.isComplete ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800'">
                                Progress: {{ nawCompletionStatus.percentage }}%
                            </Badge>
                            <Badge class="bg-white text-orange-600">
                                {{ nawCompletionStatus.completedItems }}/{{ nawCompletionStatus.totalItems }} Selesai
                            </Badge>
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-xs text-orange-100">Dibuat oleh:</div>
                    <div class="font-bold">{{ report.creator?.name }}</div>
                    <div class="mt-2">
                        <Button 
                            @click="submitNAW" 
                            :disabled="!nawCompletionStatus.isComplete" 
                            :class="nawCompletionStatus.isComplete ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-400 cursor-not-allowed'"
                            class="text-white px-4 py-2 rounded"
                        >
                            {{ nawCompletionStatus.isComplete ? 'Submit NAW' : 'Lengkapi NAW' }}
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-7xl px-4 py-8 space-y-6">
            <!-- Informasi Debitur -->
            <div class="rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div @click="toggleSection('information')" class="px-6 py-4 border-b border-gray-200 cursor-pointer">
                    <div class="w-full flex items-center justify-between text-left rounded-lg">
                        <Label class="text-lg font-semibold text-gray-800 flex-items-center">Informasi Debitur</Label>
                        <div class="transform transition-transform" :class="{ 'rotate-180': !expandedSections.information }">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div v-show="expandedSections.information" class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <Label class="text-sm font-medium text-gray-500">Debitur</Label>
                            <p class="text-lg font-semibold text-gray-900">{{ report.borrower?.name }}</p>
                        </div>
                        <div>
                            <Label class="text-sm font-medium text-gray-500">Periode</Label>
                            <p class="text-lg font-semibold text-gray-900">{{ report.period?.name }}</p>
                        </div>
                        <div>
                            <Label class="text-sm font-medium text-gray-500">Klasifikasi Final</Label>
                            <div class="mt-1">
                                <Badge class="bg-red-100 text-red-800">WATCHLIST</Badge>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nota Monitoring -->
            <div class="rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div @click="toggleSection('monitoring')" class="px-6 py-4 border-b border-gray-200 cursor-pointer">
                    <div class="w-full flex items-center justify-between text-left rounded-lg">
                        <Label class="text-lg font-semibold text-gray-800 flex-items-center">
                            Nota Monitoring
                            <Badge :class="nawCompletionStatus.hasWatchlistReason && nawCompletionStatus.hasAccountStrategy ? 'bg-green-100 text-green-800 ml-2' : 'bg-red-100 text-red-800 ml-2'">
                                {{ nawCompletionStatus.hasWatchlistReason && nawCompletionStatus.hasAccountStrategy ? 'Lengkap' : 'Belum Lengkap' }}
                            </Badge>
                        </Label>
                        <div class="transform transition-transform" :class="{ 'rotate-180': !expandedSections.monitoring }">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div v-show="expandedSections.monitoring" class="px-6 py-4">
                    <form @submit.prevent="saveMonitoringNote" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <Label class="text-sm font-medium text-gray-500">Alasan Watchlist <span class="text-red-500">*</span></Label>
                                <Textarea
                                    v-model="monitoringNoteForm.watchlist_reason"
                                    class="w-full min-h-[100px] p-4 bg-gray-50 rounded-lg border resize-y mt-2"
                                    placeholder="Jelaskan alasan mengapa debitur masuk dalam kategori watchlist..."
                                    required
                                ></Textarea>
                                <div v-if="monitoringNoteForm.errors.watchlist_reason" class="text-sm text-red-600 mt-1">
                                    {{ monitoringNoteForm.errors.watchlist_reason }}
                                </div>
                            </div>
                            <div>
                                <Label class="text-sm font-medium text-gray-500">Account Strategy <span class="text-red-500">*</span></Label>
                                <Textarea
                                    v-model="monitoringNoteForm.account_strategy"
                                    class="w-full min-h-[100px] p-4 bg-gray-50 rounded-lg border resize-y mt-2"
                                    placeholder="Jelaskan strategi penanganan account ini..."
                                    required
                                ></Textarea>
                                <div v-if="monitoringNoteForm.errors.account_strategy" class="text-sm text-red-600 mt-1">
                                    {{ monitoringNoteForm.errors.account_strategy }}
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <Button type="submit" :disabled="monitoringNoteForm.processing" class="bg-blue-600 hover:bg-blue-700 text-white">
                                {{ monitoringNoteForm.processing ? 'Menyimpan...' : 'Simpan Monitoring Note' }}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Action Plan -->
            <div class="rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div @click="toggleSection('actionItems')" class="px-6 py-4 border-b border-gray-200 cursor-pointer">
                    <div class="w-full flex items-center justify-between text-left rounded-lg">
                        <Label class="text-lg font-semibold text-gray-800 flex-items-center">
                            Action Plan
                            <Badge :class="nawCompletionStatus.hasPreviousProgress && nawCompletionStatus.hasNextPlan ? 'bg-green-100 text-green-800 ml-2' : 'bg-red-100 text-red-800 ml-2'">
                                {{ nawCompletionStatus.hasPreviousProgress && nawCompletionStatus.hasNextPlan ? 'Lengkap' : 'Belum Lengkap' }}
                            </Badge>
                        </Label>
                        <div class="transform transition-transform" :class="{ 'rotate-180': !expandedSections.actionItems }">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div v-show="expandedSections.actionItems" class="px-6 py-4">
                    <!-- Hapus atau comment bagian Action Buttons -->
                    <!-- 
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <div class="flex gap-3">
                            <button @click="copyFromPrevious" :disabled="isLoading" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors" :class="{ 'opacity-50 cursor-not-allowed': isLoading }">
                                {{ isLoading ? 'Menyalin...' : 'Salin dari Periode Sebelumnya' }}
                            </button>
                        </div>
                    </div>
                    -->
                    
                    <!-- Progress Periode Sebelumnya -->
                    <div class="mb-8">
                        <div class="mb-4">
                            <Label class="text-lg font-semibold text-gray-800">{{ getItemTypeLabel('previous_period') }}</Label>
                            <div class="flex items-center space-x-2 mt-1">
                                <p class="text-sm text-orange-600 font-medium">📝 Data otomatis dimuat dari periode sebelumnya. Lengkapi progress untuk setiap item.</p>
                                <Badge :class="nawCompletionStatus.hasPreviousProgress ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                    {{ previousPeriodCompletionStatus.percentage }}% Selesai
                                </Badge>
                            </div>
                        </div>
                        
                        <!-- Form Tambah Item Baru (Read-only fields for previous period) -->
                        <div class="mb-4 p-4 bg-orange-50 rounded-lg border border-orange-200">
                            <Label class="text-sm font-semibold text-orange-800 mb-3 block">Tambah Action Item Periode Sebelumnya</Label>
                            <div class="space-y-3">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <Label class="text-xs font-medium text-gray-500">Deskripsi Action</Label>
                                        <Textarea v-model="newPreviousItem.action_description" placeholder="Deskripsi action dari periode sebelumnya..." rows="2" class="mt-1" />
                                    </div>
                                    <div>
                                        <Label class="text-xs font-medium text-gray-500">Progress Notes (Wajib)</Label>
                                        <Textarea v-model="newPreviousItem.progress_notes" placeholder="Jelaskan progress yang telah dicapai..." rows="2" class="mt-1" />
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div>
                                        <Label class="text-xs font-medium text-gray-500">PIC</Label>
                                        <Input v-model="newPreviousItem.people_in_charge" placeholder="Nama PIC" class="mt-1" />
                                    </div>
                                    <div>
                                        <Label class="text-xs font-medium text-gray-500">Due Date</Label>
                                        <Input v-model="newPreviousItem.due_date" type="date" class="mt-1" />
                                    </div>
                                    <div>
                                        <Label class="text-xs font-medium text-gray-500">Status</Label>
                                        <Select v-model="newPreviousItem.status">
                                            <SelectTrigger class="mt-1">
                                                <SelectValue placeholder="Pilih status" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="pending">Pending</SelectItem>
                                                <SelectItem value="in_progress">In Progress</SelectItem>
                                                <SelectItem value="completed">Completed</SelectItem>
                                                <SelectItem value="overdue">Overdue</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                                <div>
                                    <Label class="text-xs font-medium text-gray-500">Catatan Tambahan</Label>
                                    <Textarea v-model="newPreviousItem.notes" placeholder="Catatan tambahan..." rows="2" class="mt-1" />
                                </div>
                                <button @click="addNewPreviousItem" :disabled="newPreviousItem.processing || !newPreviousItem.action_description || !newPreviousItem.progress_notes" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                    {{ newPreviousItem.processing ? 'Menyimpan...' : 'Tambah Item' }}
                                </button>
                            </div>
                        </div>
                        
                        <div v-if="actionItemsByType.previous_period.length === 0" class="text-center py-8 text-gray-500 bg-gray-50 rounded-lg">
                            <p>Belum ada action item dari periode sebelumnya</p>
                        </div>
                        
                        <div v-else class="space-y-4">
                            <div v-for="(item, index) in actionItemsByType.previous_period" :key="item.id" class="p-4 border rounded-lg" :class="item.progress_notes && item.progress_notes.trim() !== '' ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'">
                                <div v-if="editingIndex === index && editingType === 'previous_period'">
                                    <!-- Edit Mode - Only progress_notes and status editable for previous period -->
                                    <div class="space-y-3">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                            <div>
                                                <Label class="text-xs font-medium text-gray-500">Deskripsi Action (Read-only)</Label>
                                                <Textarea v-model="editForm.action_description" rows="2" class="mt-1 bg-gray-100" disabled />
                                            </div>
                                            <div>
                                                <Label class="text-xs font-medium text-gray-500">Progress Notes (Wajib)</Label>
                                                <Textarea v-model="editForm.progress_notes" placeholder="Jelaskan progress yang telah dicapai..." rows="2" class="mt-1" />
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                            <div>
                                                <Label class="text-xs font-medium text-gray-500">PIC (Read-only)</Label>
                                                <Input v-model="editForm.people_in_charge" class="mt-1 bg-gray-100" disabled />
                                            </div>
                                            <div>
                                                <Label class="text-xs font-medium text-gray-500">Due Date (Read-only)</Label>
                                                <Input v-model="editForm.due_date" type="date" class="mt-1 bg-gray-100" disabled />
                                            </div>
                                            <div>
                                                <Label class="text-xs font-medium text-gray-500">Status</Label>
                                                <Select v-model="editForm.status">
                                                    <SelectTrigger class="mt-1">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="pending">Pending</SelectItem>
                                                        <SelectItem value="in_progress">In Progress</SelectItem>
                                                        <SelectItem value="completed">Completed</SelectItem>
                                                        <SelectItem value="overdue">Overdue</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        </div>
                                        <div>
                                            <Label class="text-xs font-medium text-gray-500">Catatan Tambahan (Read-only)</Label>
                                            <Textarea v-model="editForm.notes" rows="2" class="mt-1 bg-gray-100" disabled />
                                        </div>
                                        <div class="flex gap-2">
                                            <button @click="saveEdit(item)" :disabled="editForm.processing || !editForm.progress_notes" class="px-3 py-1 text-sm bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50">
                                                {{ editForm.processing ? 'Menyimpan...' : 'Simpan Progress' }}
                                            </button>
                                            <button @click="cancelEdit" class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50">
                                                Batal
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div v-else>
                                    <!-- View Mode -->
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-gray-800 mb-2">{{ item.action_description }}</h4>
                                            <div class="flex gap-2">
                                                <Badge :class="getStatusBadgeClass(item.status)">{{ item.status.replace('_', ' ').toUpperCase() }}</Badge>
                                                <Badge :class="getItemTypeBadgeClass(item.item_type)">{{ getItemTypeLabel(item.item_type) }}</Badge>
                                            </div>
                                        </div>
                                        <div class="flex gap-2">
                                            <button @click="startEdit(item, index, 'previous_period')" class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50">
                                                {{ item.progress_notes && item.progress_notes.trim() !== '' ? 'Edit Progress' : 'Isi Progress' }}
                                            </button>
                                            <button @click="deleteActionItem(item)" class="px-3 py-1 text-sm border border-red-300 text-red-600 rounded hover:bg-red-50">
                                                Hapus
                                            </button>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                        <div v-if="item.progress_notes">
                                            <Label class="text-xs font-medium text-gray-500">Progress Notes</Label>
                                            <p class="text-gray-700 mt-1">{{ item.progress_notes }}</p>
                                        </div>
                                        <div v-else class="col-span-3">
                                            <p class="text-red-600 font-medium">⚠️ Progress notes belum diisi - Wajib dilengkapi</p>
                                        </div>
                                        <div v-if="item.people_in_charge">
                                            <Label class="text-xs font-medium text-gray-500">PIC</Label>
                                            <p class="text-gray-700 mt-1">{{ item.people_in_charge }}</p>
                                        </div>
                                        <div v-if="item.due_date">
                                            <Label class="text-xs font-medium text-gray-500">Due Date</Label>
                                            <p class="text-gray-700 mt-1">{{ formatDate(item.due_date) }}</p>
                                        </div>
                                        <div v-if="item.notes" class="md:col-span-3">
                                            <Label class="text-xs font-medium text-gray-500">Notes</Label>
                                            <p class="text-gray-700 mt-1">{{ item.notes }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rencana Tindak Lanjut Periode Berikutnya -->
                    <div>
                        <div class="mb-4">
                            <Label class="text-lg font-semibold text-gray-800">{{ getItemTypeLabel('next_period') }}</Label>
                            <div class="flex items-center space-x-2 mt-1">
                                <p class="text-sm text-green-600 font-medium">🎯 Wajib diisi: Buat rencana tindak lanjut untuk periode berikutnya</p>
                                <Badge :class="nawCompletionStatus.hasNextPlan ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                    {{ actionItemsByType.next_period.length }} Rencana
                                </Badge>
                            </div>
                        </div>
                        
                        <!-- Form Tambah Rencana Baru -->
                        <div class="mb-4 p-4 bg-green-50 rounded-lg border border-green-200">
                            <Label class="text-sm font-semibold text-green-800 mb-3 block">Tambah Rencana Tindak Lanjut</Label>
                            <div class="space-y-3">
                                <div>
                                    <Label class="text-xs font-medium text-gray-500">Deskripsi Rencana Tindak Lanjut</Label>
                                    <Textarea v-model="newNextItem.action_description" placeholder="Jelaskan rencana tindak lanjut yang akan dilakukan..." rows="3" class="mt-1" />
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div>
                                        <Label class="text-xs font-medium text-gray-500">PIC</Label>
                                        <Input v-model="newNextItem.people_in_charge" placeholder="Nama PIC" class="mt-1" />
                                    </div>
                                    <div>
                                        <Label class="text-xs font-medium text-gray-500">Target Tanggal</Label>
                                        <Input v-model="newNextItem.due_date" type="date" class="mt-1" />
                                    </div>
                                    <div>
                                        <Label class="text-xs font-medium text-gray-500">Status</Label>
                                        <Select v-model="newNextItem.status">
                                            <SelectTrigger class="mt-1">
                                                <SelectValue placeholder="Pilih status" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="pending">Pending</SelectItem>
                                                <SelectItem value="in_progress">In Progress</SelectItem>
                                                <SelectItem value="completed">Completed</SelectItem>
                                                <SelectItem value="overdue">Overdue</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                                <div>
                                    <Label class="text-xs font-medium text-gray-500">Catatan</Label>
                                    <Textarea v-model="newNextItem.notes" placeholder="Catatan tambahan..." rows="2" class="mt-1" />
                                </div>
                                <button @click="addNewNextItem" :disabled="newNextItem.processing || !newNextItem.action_description" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                    {{ newNextItem.processing ? 'Menyimpan...' : 'Tambah Rencana' }}
                                </button>
                            </div>
                        </div>
                        
                        <div v-if="actionItemsByType.next_period.length === 0" class="text-center py-8 text-gray-500 bg-gray-50 rounded-lg">
                            <p>Belum ada rencana tindak lanjut untuk periode berikutnya</p>
                        </div>
                        
                        <div v-else class="space-y-4">
                            <div v-for="(item, index) in actionItemsByType.next_period" :key="item.id" class="p-4 border border-green-200 rounded-lg bg-white">
                                <div v-if="editingIndex === index && editingType === 'next_period'">
                                    <!-- Edit Mode - All fields editable for next period -->
                                    <div class="space-y-3">
                                        <div>
                                            <Label class="text-xs font-medium text-gray-500">Deskripsi Rencana</Label>
                                            <Textarea v-model="editForm.action_description" rows="3" class="mt-1" />
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                            <div>
                                                <Label class="text-xs font-medium text-gray-500">PIC</Label>
                                                <Input v-model="editForm.people_in_charge" class="mt-1" />
                                            </div>
                                            <div>
                                                <Label class="text-xs font-medium text-gray-500">Target Tanggal</Label>
                                                <Input v-model="editForm.due_date" type="date" class="mt-1" />
                                            </div>
                                            <div>
                                                <Label class="text-xs font-medium text-gray-500">Status</Label>
                                                <Select v-model="editForm.status">
                                                    <SelectTrigger class="mt-1">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="pending">Pending</SelectItem>
                                                        <SelectItem value="in_progress">In Progress</SelectItem>
                                                        <SelectItem value="completed">Completed</SelectItem>
                                                        <SelectItem value="overdue">Overdue</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        </div>
                                        <div>
                                            <Label class="text-xs font-medium text-gray-500">Catatan</Label>
                                            <Textarea v-model="editForm.notes" rows="2" class="mt-1" />
                                        </div>
                                        <div class="flex gap-2">
                                            <button @click="saveEdit(item)" :disabled="editForm.processing" class="px-3 py-1 text-sm bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50">
                                                {{ editForm.processing ? 'Menyimpan...' : 'Simpan' }}
                                            </button>
                                            <button @click="cancelEdit" class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50">
                                                Batal
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div v-else>
                                    <!-- View Mode -->
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-gray-800 mb-2">{{ item.action_description }}</h4>
                                            <div class="flex gap-2">
                                                <Badge :class="getStatusBadgeClass(item.status)">{{ item.status.replace('_', ' ').toUpperCase() }}</Badge>
                                                <Badge :class="getItemTypeBadgeClass(item.item_type)">{{ getItemTypeLabel(item.item_type) }}</Badge>
                                            </div>
                                        </div>
                                        <div class="flex gap-2">
                                            <button @click="startEdit(item, index, 'next_period')" class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50">
                                                Edit
                                            </button>
                                            <button @click="deleteActionItem(item)" class="px-3 py-1 text-sm border border-red-300 text-red-600 rounded hover:bg-red-50">
                                                Hapus
                                            </button>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                        <div v-if="item.people_in_charge">
                                            <Label class="text-xs font-medium text-gray-500">PIC</Label>
                                            <p class="text-gray-700 mt-1">{{ item.people_in_charge }}</p>
                                        </div>
                                        <div v-if="item.due_date">
                                            <Label class="text-xs font-medium text-gray-500">Target Tanggal</Label>
                                            <p class="text-gray-700 mt-1">{{ formatDate(item.due_date) }}</p>
                                        </div>
                                        <div v-if="item.notes" class="md:col-span-3">
                                            <Label class="text-xs font-medium text-gray-500">Catatan</Label>
                                            <p class="text-gray-700 mt-1">{{ item.notes }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
