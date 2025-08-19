
<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useToast } from 'vue-toastification';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { 
    AlertTriangle, 
    CheckCircle, 
    Clock, 
    Plus, 
    Edit, 
    Trash2, 
    Copy, 
    Save, 
    X, 
    User, 
    Building2, 
    Calendar, 
    FileText,
    ClipboardList,
    Target,
    TrendingUp,
    AlertCircle,
    Info
} from 'lucide-vue-next';

interface User {
    id: number;
    name: string;
}

interface Borrower {
    id: number;
    name: string;
    code: string;
}

interface Period {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
}

interface Report {
    id: number;
    borrower: Borrower;
    period: Period;
    summary: ReportSummary;
}

interface ReportSummary {
    id: number;
    final_classification: 'safe' | 'watchlist';
    indicative_collectibility: number;
}

interface MonitoringNote {
    id: number;
    watchlist_reason: string;
    account_strategy: string;
    created_by: number;
    updated_by: number;
    createdBy: User;
    updatedBy: User;
    created_at: string;
    updated_at: string;
}

interface ActionItem {
    id: number;
    monitoring_note_id: number;
    action_description: string;
    item_type: 'previous_period' | 'current_progress' | 'next_period';
    progress_notes: string;
    people_in_charge: string;
    notes: string;
    due_date: string;
    completion_date: string;
    status: 'pending' | 'in_progress' | 'completed' | 'overdue';
    created_at: string;
    updated_at: string;
}

interface Watchlist {
    id: number;
    borrower_id: number;
    report_id: number;
    status: 'active' | 'resolved' | 'escalated';
    resolved_by?: number;
    resolved_at?: string;
    resolver_notes?: string;
}

interface Props {
    report: Report;
    monitoringNote: MonitoringNote;
    actionItems: Record<string, ActionItem[]>;
    watchlist: Watchlist;
    isNawRequired: boolean;
    canEdit: boolean;
}

const toast = useToast();
const props = defineProps<Props>();

// Reactive state
const isLoading = ref(false);
const expandedSections: any = ref({
    borrowerInfo: true,
    monitoringNote: true,
    actionItems: true,
    watchlistStatus: true
});

const showActionItemDialog = ref(false);
const editingActionItem = ref<ActionItem | null>(null);
const showWatchlistDialog = ref(false);

// Forms
const monitoringNoteForm = useForm({
    watchlist_reason: props.monitoringNote?.watchlist_reason || '',
    account_strategy: props.monitoringNote?.account_strategy || ''
});

const actionItemForm = useForm({
    action_description: '',
    item_type: 'current_progress' as 'previous_period' | 'current_progress' | 'next_period',
    progress_notes: '',
    people_in_charge: '',
    notes: '',
    due_date: '',
    status: 'pending' as 'pending' | 'in_progress' | 'completed' | 'overdue'
});

const watchlistForm = useForm({
    status: props.watchlist?.status || 'active',
    resolver_notes: ''
});

// Computed properties
const borrowerInfo = computed(() => ({
    name: props.report.borrower?.name || 'N/A',
    code: props.report.borrower?.code || 'N/A',
    period: props.report.period?.name || 'N/A',
    classification: props.report.summary?.final_classification || 'N/A'
}));

const actionItemsByType = computed(() => ({
    previous_period: props.actionItems.previous_period || [],
    current_progress: props.actionItems.current_progress || [],
    next_period: props.actionItems.next_period || []
}));

const totalActionItems = computed(() => {
    return Object.values(actionItemsByType.value).reduce((total, items) => total + items.length, 0);
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

const getStatusColor = (status: string) => {
    switch (status) {
        case 'completed': return 'text-green-600';
        case 'in_progress': return 'text-blue-600';
        case 'overdue': return 'text-red-600';
        case 'pending': return 'text-yellow-600';
        default: return 'text-gray-600';
    }
};

const getStatusBg = (status: string) => {
    switch (status) {
        case 'completed': return 'bg-green-100 border-green-200';
        case 'in_progress': return 'bg-blue-100 border-blue-200';
        case 'overdue': return 'bg-red-100 border-red-200';
        case 'pending': return 'bg-yellow-100 border-yellow-200';
        default: return 'bg-gray-100 border-gray-200';
    }
};

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'completed': return CheckCircle;
        case 'in_progress': return Clock;
        case 'overdue': return AlertTriangle;
        case 'pending': return AlertCircle;
        default: return Info;
    }
};

const getWatchlistStatusColor = (status: string) => {
    switch (status) {
        case 'active': return 'text-red-600';
        case 'resolved': return 'text-green-600';
        case 'escalated': return 'text-orange-600';
        default: return 'text-gray-600';
    }
};

const getWatchlistStatusBg = (status: string) => {
    switch (status) {
        case 'active': return 'bg-red-100 border-red-200';
        case 'resolved': return 'bg-green-100 border-green-200';
        case 'escalated': return 'bg-orange-100 border-orange-200';
        default: return 'bg-gray-100 border-gray-200';
    }
};

const getItemTypeLabel = (type: string) => {
    switch (type) {
        case 'previous_period': return 'Periode Sebelumnya';
        case 'current_progress': return 'Progress Saat Ini';
        case 'next_period': return 'Periode Berikutnya';
        default: return type;
    }
};

const getItemTypeColor = (type: string) => {
    switch (type) {
        case 'previous_period': return 'bg-gray-100 text-gray-800';
        case 'current_progress': return 'bg-blue-100 text-blue-800';
        case 'next_period': return 'bg-purple-100 text-purple-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

// Toggle section expansion
const toggleSection = (section: string) => {
    expandedSections.value[section] = !expandedSections.value[section];
};

// Form handlers
const updateMonitoringNote = () => {
    if (!props.canEdit) {
        toast.error('Anda tidak memiliki izin untuk mengedit.');
        return;
    }

    isLoading.value = true;
    monitoringNoteForm.patch(route('naw.update', props.monitoringNote.id), {
        onSuccess: () => {
            toast.success('Monitoring note berhasil diperbarui');
        },
        onError: () => {
            toast.error('Gagal memperbarui monitoring note');
        },
        onFinish: () => {
            isLoading.value = false;
        }
    });
};

const openActionItemDialog = (actionItem?: ActionItem) => {
    if (actionItem) {
        editingActionItem.value = actionItem;
        Object.assign(actionItemForm, {
            action_description: actionItem.action_description,
            item_type: actionItem.item_type,
            progress_notes: actionItem.progress_notes,
            people_in_charge: actionItem.people_in_charge,
            notes: actionItem.notes,
            due_date: actionItem.due_date,
            status: actionItem.status
        });
    } else {
        editingActionItem.value = null;
        actionItemForm.reset();
    }
    showActionItemDialog.value = true;
};

const saveActionItem = () => {
    if (!props.canEdit) {
        toast.error('Anda tidak memiliki izin untuk mengedit.');
        return;
    }

    const isEditing = editingActionItem.value !== null;
    
    if (isEditing) {
        actionItemForm.patch(route('naw.action-items.update', editingActionItem.value!.id), {
            onSuccess: () => {
                toast.success('Action item berhasil diperbarui');
                showActionItemDialog.value = false;
            },
            onError: () => {
                toast.error('Gagal memperbarui action item');
            }
        });
    } else {
        actionItemForm.post(route('naw.action-items.store', props.monitoringNote.id), {
            onSuccess: () => {
                toast.success('Action item berhasil ditambahkan');
                showActionItemDialog.value = false;
                actionItemForm.reset();
            },
            onError: () => {
                toast.error('Gagal menambahkan action item');
            }
        });
    }
};

const deleteActionItem = (actionItem: ActionItem) => {
    if (!props.canEdit) {
        toast.error('Anda tidak memiliki izin untuk mengedit.');
        return;
    }

    if (confirm('Apakah Anda yakin ingin menghapus action item ini?')) {
        router.delete(route('naw.action-items.destroy', actionItem.id), {
            onSuccess: () => {
                toast.success('Action item berhasil dihapus');
            },
            onError: () => {
                toast.error('Gagal menghapus action item');
            }
        });
    }
};

const copyFromPrevious = () => {
    if (!props.canEdit) {
        toast.error('Anda tidak memiliki izin untuk mengedit.');
        return;
    }

    isLoading.value = true;
    router.post(route('naw.copy-previous', props.monitoringNote.id), {}, {
        onSuccess: () => {
            toast.success('Action items dari periode sebelumnya berhasil disalin');
        },
        onError: () => {
            toast.error('Gagal menyalin action items dari periode sebelumnya');
        },
        onFinish: () => {
            isLoading.value = false;
        }
    });
};

const updateWatchlistStatus = () => {
    if (!props.canEdit) {
        toast.error('Anda tidak memiliki izin untuk mengedit.');
        return;
    }

    watchlistForm.patch(route('naw.watchlist-status.update', props.monitoringNote.id), {
        onSuccess: () => {
            toast.success('Status watchlist berhasil diperbarui');
            showWatchlistDialog.value = false;
        },
        onError: () => {
            toast.error('Gagal memperbarui status watchlist');
        }
    });
};
</script>

<template>
    <div class="min-h-screen">
        <Head title="Nota Analisa Watchlist"/>
        
        <!-- Header -->
        <div class="bg-[#2e3192] p-6 text-white shadow-lg">
            <div class="max-w-7xl mx-auto">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold mb-2">Nota Analisa Watchlist</h1>
                        <p class="text-blue-100">Monitoring dan tindak lanjut debitur watchlist</p>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-blue-100">Report ID</div>
                        <div class="text-xl font-mono font-bold">#{{ report.id }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-7xl px-4 py-8 space-y-8">
            <!-- Quick Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Borrower Name -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Debitur</p>
                            <p class="text-xl font-bold text-blue-600">{{ borrowerInfo.name }}</p>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-full">
                            <Building2 class="w-6 h-6 text-blue-600" />
                        </div>
                    </div>
                </div>

                <!-- Period -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Periode</p>
                            <p class="text-xl font-bold text-green-600">{{ borrowerInfo.period }}</p>
                        </div>
                        <div class="p-3 bg-green-100 rounded-full">
                            <Calendar class="w-6 h-6 text-green-600" />
                        </div>
                    </div>
                </div>

                <!-- Total Action Items -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Action Items</p>
                            <p class="text-2xl font-bold text-purple-600">{{ totalActionItems }}</p>
                        </div>
                        <div class="p-3 bg-purple-100 rounded-full">
                            <ClipboardList class="w-6 h-6 text-purple-600" />
                        </div>
                    </div>
                </div>

                <!-- Watchlist Status -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Status Watchlist</p>
                            <p class="text-xl font-bold" :class="getWatchlistStatusColor(watchlist.status)">{{ watchlist.status.toUpperCase() }}</p>
                        </div>
                        <div class="p-3 rounded-full" :class="getWatchlistStatusBg(watchlist.status)">
                            <AlertTriangle class="w-6 h-6" :class="getWatchlistStatusColor(watchlist.status)" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informasi Debitur -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-200">
                    <button @click="toggleSection('borrowerInfo')" 
                        class="w-full flex items-center justify-between text-left hover:bg-blue-100 rounded-lg p-2 -m-2 transition-colors">
                        <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                            <User class="w-6 h-6 mr-3 text-blue-600" />
                            Informasi Debitur
                        </h2>
                        <div class="transform transition-transform" :class="{ 'rotate-180': !expandedSections.borrowerInfo }">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </button>
                </div>
                
                <div v-show="expandedSections.borrowerInfo" class="p-6">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                        <div class="space-y-3">
                            <Label class="text-sm font-semibold text-gray-700 flex items-center">
                                <Building2 class="w-4 h-4 mr-2 text-blue-500" />
                                Nama Debitur
                            </Label>
                            <div class="bg-gradient-to-r from-blue-50 to-blue-100 border-2 border-blue-200 rounded-xl p-4 shadow-sm">
                                <div class="text-xl font-bold text-gray-800">{{ borrowerInfo.name }}</div>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <Label class="text-sm font-semibold text-gray-700 flex items-center">
                                <FileText class="w-4 h-4 mr-2 text-green-500" />
                                Kode Debitur
                            </Label>
                            <div class="bg-gradient-to-r from-green-50 to-green-100 border-2 border-green-200 rounded-xl p-4 shadow-sm">
                                <div class="text-xl font-bold text-gray-800">{{ borrowerInfo.code }}</div>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <Label class="text-sm font-semibold text-gray-700 flex items-center">
                                <Calendar class="w-4 h-4 mr-2 text-purple-500" />
                                Periode
                            </Label>
                            <div class="bg-gradient-to-r from-purple-50 to-purple-100 border-2 border-purple-200 rounded-xl p-4 shadow-sm">
                                <div class="text-xl font-bold text-gray-800">{{ borrowerInfo.period }}</div>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <Label class="text-sm font-semibold text-gray-700 flex items-center">
                                <TrendingUp class="w-4 h-4 mr-2 text-red-500" />
                                Klasifikasi
                            </Label>
                            <div class="bg-gradient-to-r from-red-50 to-red-100 border-2 border-red-200 rounded-xl p-4 shadow-sm">
                                <div class="text-xl font-bold text-gray-800">{{ borrowerInfo.classification.toUpperCase() }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monitoring Note -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-4 border-b border-gray-200">
                    <button @click="toggleSection('monitoringNote')" 
                        class="w-full flex items-center justify-between text-left hover:bg-green-100 rounded-lg p-2 -m-2 transition-colors">
                        <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                            <FileText class="w-6 h-6 mr-3 text-green-600" />
                            Monitoring Note
                        </h2>
                        <div class="transform transition-transform" :class="{ 'rotate-180': !expandedSections.monitoringNote }">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </button>
                </div>
                
                <div v-show="expandedSections.monitoringNote" class="p-6">
                    <form @submit.prevent="updateMonitoringNote" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            <div class="space-y-3">
                                <Label for="watchlist_reason" class="text-sm font-semibold text-gray-700">
                                    Alasan Watchlist
                                </Label>
                                <Textarea 
                                    id="watchlist_reason"
                                    v-model="monitoringNoteForm.watchlist_reason"
                                    placeholder="Jelaskan alasan debitur masuk watchlist..."
                                    rows="4"
                                    :disabled="!canEdit"
                                    class="w-full"
                                />
                                <div v-if="monitoringNoteForm.errors.watchlist_reason" class="text-sm text-red-600">
                                    {{ monitoringNoteForm.errors.watchlist_reason }}
                                </div>
                            </div>
                            
                            <div class="space-y-3">
                                <Label for="account_strategy" class="text-sm font-semibold text-gray-700">
                                    Strategi Akun
                                </Label>
                                <Textarea 
                                    id="account_strategy"
                                    v-model="monitoringNoteForm.account_strategy"
                                    placeholder="Jelaskan strategi penanganan akun..."
                                    rows="4"
                                    :disabled="!canEdit"
                                    class="w-full"
                                />
                                <div v-if="monitoringNoteForm.errors.account_strategy" class="text-sm text-red-600">
                                    {{ monitoringNoteForm.errors.account_strategy }}
                                </div>
                            </div>
                        </div>
                        
                        <div v-if="canEdit" class="flex justify-end">
                            <Button type="submit" :disabled="monitoringNoteForm.processing || isLoading" class="bg-green-600 hover:bg-green-700">
                                <Save class="w-4 h-4 mr-2" />
                                {{ monitoringNoteForm.processing ? 'Menyimpan...' : 'Simpan Monitoring Note' }}
                            </Button>
                        </div>
                    </form>
                    
                    <!-- Metadata -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="text-sm text-gray-600">
                                <span class="font-semibold">Dibuat oleh:</span> {{ monitoringNote.createdBy?.name || 'N/A' }}
                                <br>
                                <span class="font-semibold">Tanggal dibuat:</span> {{ formatDate(monitoringNote.created_at) }}
                            </div>
                            <div class="text-sm text-gray-600">
                                <span class="font-semibold">Diperbarui oleh:</span> {{ monitoringNote.updatedBy?.name || 'N/A' }}
                                <br>
                                <span class="font-semibold">Tanggal diperbarui:</span> {{ formatDate(monitoringNote.updated_at) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Items -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-red-50 to-orange-50 px-6 py-4 border-b border-gray-200">
                    <button @click="toggleSection('actionItems')" 
                        class="w-full flex items-center justify-between text-left hover:bg-red-100 rounded-lg p-2 -m-2 transition-colors">
                        <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                            <ClipboardList class="w-6 h-6 mr-3 text-red-600" />
                            Action Items
                            <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                {{ totalActionItems }} Items
                            </span>
                        </h2>
                        <div class="transform transition-transform" :class="{ 'rotate-180': !expandedSections.actionItems }">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </button>
                </div>
                
                <div v-show="expandedSections.actionItems" class="p-6">
                    <!-- Action Buttons -->
                    <div v-if="canEdit" class="flex flex-wrap gap-3 mb-6">
                        <Button @click="openActionItemDialog()" class="bg-blue-600 hover:bg-blue-700">
                            <Plus class="w-4 h-4 mr-2" />
                            Tambah Action Item
                        </Button>
                        <Button @click="copyFromPrevious" :disabled="isLoading" variant="outline">
                            <Copy class="w-4 h-4 mr-2" />
                            {{ isLoading ? 'Menyalin...' : 'Salin dari Periode Sebelumnya' }}
                        </Button>
                    </div>
                    
                    <!-- Action Items by Type -->
                    <div class="space-y-8">
                        <div v-for="(items, type) in actionItemsByType" :key="type" class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                                    <Target class="w-5 h-5 mr-2 text-gray-600" />
                                    {{ getItemTypeLabel(type) }}
                                    <Badge :class="getItemTypeColor(type)" class="ml-2">
                                        {{ items.length }} items
                                    </Badge>
                                </h3>
                            </div>
                            
                            <div v-if="items.length === 0" class="text-center py-8 text-gray-500">
                                <ClipboardList class="w-12 h-12 mx-auto mb-3 text-gray-300" />
                                <p>Belum ada action item untuk {{ getItemTypeLabel(type).toLowerCase() }}</p>
                            </div>
                            
                            <div v-else class="grid gap-4">
                                <Card v-for="item in items" :key="item.id" class="hover:shadow-md transition-shadow">
                                    <CardHeader class="pb-3">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <CardTitle class="text-base font-semibold text-gray-800 mb-2">
                                                    {{ item.action_description }}
                                                </CardTitle>
                                                <div class="flex flex-wrap gap-2">
                                                    <Badge :class="getStatusBg(item.status)" class="border">
                                                        <component :is="getStatusIcon(item.status)" class="w-3 h-3 mr-1" />
                                                        {{ item.status.replace('_', ' ').toUpperCase() }}
                                                    </Badge>
                                                    <Badge :class="getItemTypeColor(item.item_type)">
                                                        {{ getItemTypeLabel(item.item_type) }}
                                                    </Badge>
                                                </div>
                                            </div>
                                            <div v-if="canEdit" class="flex gap-2 ml-4">
                                                <Button @click="openActionItemDialog(item)" size="sm" variant="outline">
                                                    <Edit class="w-4 h-4" />
                                                </Button>
                                                <Button @click="deleteActionItem(item)" size="sm" variant="outline" class="text-red-600 hover:text-red-700">
                                                    <Trash2 class="w-4 h-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    </CardHeader>
                                    <CardContent class="pt-0">
                                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                                            <div v-if="item.progress_notes">
                                                <Label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Progress Notes</Label>
                                                <p class="text-sm text-gray-700 mt-1">{{ item.progress_notes }}</p>
                                            </div>
                                            <div v-if="item.people_in_charge">
                                                <Label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">PIC</Label>
                                                <p class="text-sm text-gray-700 mt-1">{{ item.people_in_charge }}</p>
                                            </div>
                                            <div v-if="item.due_date">
                                                <Label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Due Date</Label>
                                                <p class="text-sm text-gray-700 mt-1">{{ formatDate(item.due_date) }}</p>
                                            </div>
                                            <div v-if="item.completion_date">
                                                <Label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Completion Date</Label>
                                                <p class="text-sm text-gray-700 mt-1">{{ formatDate(item.completion_date) }}</p>
                                            </div>
                                            <div v-if="item.notes" class="md:col-span-2 lg:col-span-3">
                                                <Label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Notes</Label>
                                                <p class="text-sm text-gray-700 mt-1">{{ item.notes }}</p>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Watchlist Status -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-purple-50 to-pink-50 px-6 py-4 border-b border-gray-200">
                    <button @click="toggleSection('watchlistStatus')" 
                        class="w-full flex items-center justify-between text-left hover:bg-purple-100 rounded-lg p-2 -m-2 transition-colors">
                        <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                            <AlertTriangle class="w-6 h-6 mr-3 text-purple-600" />
                            Status Watchlist
                        </h2>
                        <div class="transform transition-transform" :class="{ 'rotate-180': !expandedSections.watchlistStatus }">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </button>
                </div>
                
                <div v-show="expandedSections.watchlistStatus" class="p-6">
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <div class="space-y-4">
                            <div class="text-center p-6 rounded-2xl border-4 shadow-lg" :class="getWatchlistStatusBg(watchlist.status)">
                                <div class="mb-4">
                                    <AlertTriangle class="w-12 h-12 mx-auto" :class="getWatchlistStatusColor(watchlist.status)" />
                                </div>
                                <Label class="text-sm font-bold text-gray-600 block mb-3 uppercase tracking-wide">Status Saat Ini</Label>
                                <div class="mt-2">
                                    <span class="inline-block rounded-2xl px-6 py-3 text-2xl font-bold border-4" 
                                        :class="[getWatchlistStatusColor(watchlist.status), getWatchlistStatusBg(watchlist.status)]">
                                        {{ watchlist.status.toUpperCase() }}
                                    </span>
                                </div>
                            </div>
                            
                            <div v-if="canEdit" class="text-center">
                                <Dialog v-model:open="showWatchlistDialog">
                                    <DialogTrigger asChild>
                                        <Button class="bg-purple-600 hover:bg-purple-700">
                                            <Edit class="w-4 h-4 mr-2" />
                                            Update Status
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogHeader>
                                            <DialogTitle>Update Status Watchlist</DialogTitle>
                                            <DialogDescription>
                                                Pilih status baru untuk watchlist ini.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <form @submit.prevent="updateWatchlistStatus" class="space-y-4">
                                            <div class="space-y-2">
                                                <Label for="status">Status</Label>
                                                <Select v-model="watchlistForm.status">
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Pilih status" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="active">Active</SelectItem>
                                                        <SelectItem value="resolved">Resolved</SelectItem>
                                                        <SelectItem value="escalated">Escalated</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                            <div class="space-y-2">
                                                <Label for="resolver_notes">Catatan</Label>
                                                <Textarea 
                                                    id="resolver_notes"
                                                    v-model="watchlistForm.resolver_notes"
                                                    placeholder="Tambahkan catatan untuk perubahan status..."
                                                    rows="3"
                                                />
                                            </div>
                                        </form>
                                        <DialogFooter>
                                            <Button type="button" variant="outline" @click="showWatchlistDialog = false">
                                                <X class="w-4 h-4 mr-2" />
                                                Batal
                                            </Button>
                                            <Button type="submit" @click="updateWatchlistStatus" :disabled="watchlistForm.processing">
                                                <Save class="w-4 h-4 mr-2" />
                                                {{ watchlistForm.processing ? 'Menyimpan...' : 'Simpan' }}
                                            </Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <div v-if="watchlist.resolved_at" class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                <Label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Tanggal Resolved</Label>
                                <div class="text-lg font-medium text-gray-800 mt-1">{{ formatDate(watchlist.resolved_at) }}</div>
                            </div>
                            <div v-if="watchlist.resolver_notes" class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                <Label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Catatan Resolver</Label>
                                <div class="text-sm text-gray-700 mt-1">{{ watchlist.resolver_notes }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Item Dialog -->
        <Dialog v-model:open="showActionItemDialog">
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>
                        {{ editingActionItem ? 'Edit Action Item' : 'Tambah Action Item Baru' }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ editingActionItem ? 'Perbarui informasi action item.' : 'Tambahkan action item baru untuk monitoring.' }}
                        
                        // Computed property untuk menentukan apakah field harus read-only
                        const isFieldReadOnly = computed(() => {
                            return editingActionItem.value?.item_type === 'previous_period';
                        });
                        
                        // Computed property untuk menentukan apakah progress notes wajib diisi
                        const isProgressRequired = computed(() => {
                            return editingActionItem.value?.item_type === 'previous_period';
                        });
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="saveActionItem" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="item_type">Tipe Item</Label>
                            <Select v-model="actionItemForm.item_type" :disabled="isFieldReadOnly">
                                <SelectTrigger :class="{ 'bg-gray-100 cursor-not-allowed': isFieldReadOnly }">
                                    <SelectValue placeholder="Pilih tipe" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="previous_period">Periode Sebelumnya</SelectItem>
                                    <SelectItem value="current_progress">Progress Saat Ini</SelectItem>
                                    <SelectItem value="next_period">Periode Berikutnya</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <Label for="status">Status</Label>
                            <Select v-model="actionItemForm.status" :disabled="!props.canEdit">
                                <SelectTrigger :class="{ 'bg-gray-100 cursor-not-allowed': isFieldReadOnly }">
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
                    <div class="space-y-2">
                        <Label for="action_description">Deskripsi Action</Label>
                        <Textarea 
                            id="action_description"
                            v-model="actionItemForm.action_description"
                            placeholder="Jelaskan action yang perlu dilakukan..."
                            rows="3"
                            :required="!isFieldReadOnly"
                            :disabled="isFieldReadOnly"
                            :class="{ 'bg-gray-100 cursor-not-allowed': isFieldReadOnly }"
                        />
                    </div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="people_in_charge">PIC</Label>
                            <Input 
                                id="people_in_charge"
                                v-model="actionItemForm.people_in_charge"
                                placeholder="Nama PIC"
                                :disabled="isFieldReadOnly"
                                :class="{ 'bg-gray-100 cursor-not-allowed': isFieldReadOnly }"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="due_date">Due Date</Label>
                            <Input 
                                id="due_date"
                                v-model="actionItemForm.due_date"
                                type="date"
                                :disabled="isFieldReadOnly"
                                :class="{ 'bg-gray-100 cursor-not-allowed': isFieldReadOnly }"
                            />
                        </div>
                    </div>
                    <div class="space-y-2">
                        <Label for="progress_notes" :class="{ 'text-red-600 font-semibold': isProgressRequired }">
                            Progress Notes
                            <span v-if="isProgressRequired" class="text-red-500">*</span>
                            <span v-if="isProgressRequired" class="text-sm text-red-600 block">
                                Wajib diisi untuk action item periode sebelumnya
                            </span>
                        </Label>
                        <Textarea 
                            id="progress_notes"
                            v-model="actionItemForm.progress_notes"
                            placeholder="Catatan progress..."
                            rows="3"
                            :required="isProgressRequired"
                            :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': isProgressRequired }"
                        />
                        <div v-if="isProgressRequired && !actionItemForm.progress_notes" class="text-sm text-red-600">
                            Progress notes wajib diisi untuk action item dari periode sebelumnya
                        </div>
                    </div>
                    <div class="space-y-2">
                        <Label for="notes">Notes</Label>
                        <Textarea 
                            id="notes"
                            v-model="actionItemForm.notes"
                            placeholder="Catatan tambahan..."
                            rows="2"
                            :disabled="isFieldReadOnly"
                            :class="{ 'bg-gray-100 cursor-not-allowed': isFieldReadOnly }"
                        />
                    </div>
                </form>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="showActionItemDialog = false">
                        <X class="w-4 h-4 mr-2" />
                        Batal
                    </Button>
                    <Button type="submit" @click="saveActionItem" :disabled="actionItemForm.processing || (isProgressRequired && !actionItemForm.progress_notes)">
                        <Save class="w-4 h-4 mr-2" />
                        {{ actionItemForm.processing ? 'Menyimpan...' : 'Simpan' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>