<template>
    <AppLayout title="Notifikasi">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Notifikasi
                </h2>
                <div class="flex space-x-2">
                    <Button
                        v-if="hasUnreadNotifications"
                        @click="markAllAsRead"
                        variant="outline"
                    >
                        <CheckIcon class="h-4 w-4 mr-2" />
                        Tandai Semua Dibaca
                    </Button>
                    <Button
                        @click="deleteAll"
                        variant="destructive"
                        :disabled="notifications.data.length === 0"
                    >
                        <TrashIcon class="h-4 w-4 mr-2" />
                        Hapus Semua
                    </Button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Daftar Notifikasi</CardTitle>
                        <CardDescription>
                            Kelola semua notifikasi Anda di sini
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <!-- Empty State -->
                        <div v-if="notifications.data.length === 0" class="text-center py-12">
                            <BellIcon class="h-16 w-16 mx-auto mb-4 text-gray-300" />
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak ada notifikasi</h3>
                            <p class="text-gray-500">Notifikasi akan muncul di sini ketika ada aktivitas baru.</p>
                        </div>

                        <!-- Notifications List -->
                        <div v-else class="space-y-4">
                            <div
                                v-for="notification in notifications.data"
                                :key="notification.id"
                                :class="[
                                    'border rounded-lg p-4 transition-all duration-200 hover:shadow-md',
                                    notification.read_at ? 'bg-white border-gray-200' : 'bg-blue-50 border-blue-200'
                                ]"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start space-x-4 flex-1">
                                        <!-- Icon -->
                                        <div
                                            :class="[
                                                'w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0',
                                                getNotificationIconClass(notification.type)
                                            ]"
                                        >
                                            <component :is="getNotificationIcon(notification.type)" class="h-5 w-5" />
                                        </div>
                                        
                                        <!-- Content -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center space-x-2 mb-1">
                                                <h4 class="text-sm font-semibold text-gray-900">
                                                    {{ getNotificationTitle(notification) }}
                                                </h4>
                                                <Badge v-if="!notification.read_at" variant="secondary" class="text-xs">
                                                    Baru
                                                </Badge>
                                            </div>
                                            <p class="text-sm text-gray-600 mb-2">
                                                {{ getNotificationMessage(notification) }}
                                            </p>
                                            <div class="flex items-center space-x-4 text-xs text-gray-400">
                                                <span>{{ formatDate(notification.created_at) }}</span>
                                                <span v-if="notification.data?.borrower_name">
                                                    Debitur: {{ notification.data.borrower_name }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Actions -->
                                    <div class="flex items-center space-x-2 ml-4">
                                        <!-- Action Button -->
                                        <Button
                                            v-if="notification.data?.action_url"
                                            @click="handleNotificationAction(notification)"
                                            size="sm"
                                            variant="outline"
                                        >
                                            {{ notification.data?.action_text || 'Lihat' }}
                                        </Button>
                                        
                                        <!-- Mark as Read/Unread -->
                                        <Button
                                            @click="toggleReadStatus(notification)"
                                            size="sm"
                                            variant="ghost"
                                        >
                                            <component 
                                                :is="notification.read_at ? MailIcon : MailOpenIcon" 
                                                class="h-4 w-4" 
                                            />
                                        </Button>
                                        
                                        <!-- Delete -->
                                        <Button
                                            @click="deleteNotification(notification.id)"
                                            size="sm"
                                            variant="ghost"
                                            class="text-red-600 hover:text-red-800"
                                        >
                                            <TrashIcon class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="notifications.data.length > 0" class="mt-6">
                            <Pagination :links="notifications.links" />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import Pagination from '@/components/Pagination.vue'
import { 
    BellIcon, 
    CheckIcon,
    TrashIcon,
    AlertCircleIcon, 
    CheckCircleIcon, 
    InfoIcon,
    XCircleIcon,
    MailIcon,
    MailOpenIcon
} from 'lucide-vue-next'

const props = defineProps({
    notifications: {
        type: Object,
        required: true
    }
})

// Computed properties
const hasUnreadNotifications = computed(() => {
    return props.notifications.data.some(n => !n.read_at)
})

// Methods
const getNotificationIcon = (type) => {
    switch (type) {
        case 'App\\Notifications\\ReportRejectedNotification':
            return XCircleIcon
        case 'App\\Notifications\\ReportApprovedNotification':
            return CheckCircleIcon
        case 'App\\Notifications\\ReportSubmittedNotification':
            return InfoIcon
        default:
            return BellIcon
    }
}

const getNotificationIconClass = (type) => {
    switch (type) {
        case 'App\\Notifications\\ReportRejectedNotification':
            return 'bg-red-100 text-red-600'
        case 'App\\Notifications\\ReportApprovedNotification':
            return 'bg-green-100 text-green-600'
        case 'App\\Notifications\\ReportSubmittedNotification':
            return 'bg-blue-100 text-blue-600'
        default:
            return 'bg-gray-100 text-gray-600'
    }
}

const getNotificationTitle = (notification) => {
    switch (notification.type) {
        case 'App\\Notifications\\ReportRejectedNotification':
            return 'Laporan Ditolak'
        case 'App\\Notifications\\ReportApprovedNotification':
            return 'Laporan Disetujui'
        case 'App\\Notifications\\ReportSubmittedNotification':
            return 'Laporan Disubmit'
        default:
            return 'Notifikasi'
    }
}

const getNotificationMessage = (notification) => {
    return notification.data?.message || 'Tidak ada pesan'
}

const formatDate = (dateString) => {
    if (!dateString) return ''
    
    const date = new Date(dateString)
    return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const handleNotificationAction = async (notification) => {
    // Mark as read if unread
    if (!notification.read_at) {
        await markAsRead(notification.id)
    }
    
    // Navigate to action URL
    if (notification.data?.action_url) {
        router.visit(notification.data.action_url)
    }
}

const toggleReadStatus = async (notification) => {
    if (notification.read_at) {
        // Mark as unread (you might need to implement this endpoint)
        await router.patch(route('notifications.mark-unread', notification.id))
    } else {
        await markAsRead(notification.id)
    }
}

const markAsRead = async (id) => {
    try {
        await router.patch(route('notifications.mark-read', id))
    } catch (error) {
        console.error('Error marking notification as read:', error)
    }
}

const markAllAsRead = async () => {
    if (confirm('Tandai semua notifikasi sebagai sudah dibaca?')) {
        try {
            await router.post(route('notifications.mark-all-read'))
        } catch (error) {
            console.error('Error marking all notifications as read:', error)
        }
    }
}

const deleteNotification = async (id) => {
    if (confirm('Hapus notifikasi ini?')) {
        try {
            await router.delete(route('notifications.destroy', id))
        } catch (error) {
            console.error('Error deleting notification:', error)
        }
    }
}

const deleteAll = async () => {
    if (confirm('Hapus semua notifikasi? Tindakan ini tidak dapat dibatalkan.')) {
        try {
            await router.delete(route('notifications.destroy-all'))
        } catch (error) {
            console.error('Error deleting all notifications:', error)
        }
    }
}
</script>