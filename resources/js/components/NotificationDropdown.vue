<template>
    <div class="relative notification-dropdown">
        <!-- Notification Bell Button -->
        <button
            @click="toggleDropdown"
            class="relative p-2 text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg"
        >
            <BellIcon class="h-6 w-6" />
            <!-- Notification Badge -->
            <span
                v-if="unreadCount > 0"
                class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full"
            >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown Menu -->
        <div
            v-if="isOpen"
            class="absolute right-0 z-50 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200"
            @click.stop
        >
            <!-- Header -->
            <div class="px-4 py-3 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Notifikasi</h3>
                    <button
                        v-if="unreadCount > 0"
                        @click="markAllAsRead"
                        class="text-sm text-blue-600 hover:text-blue-800"
                    >
                        Tandai Semua Dibaca
                    </button>
                </div>
            </div>

            <!-- Notifications List -->
            <div class="max-h-96 overflow-y-auto">
                <div v-if="notifications.length === 0" class="px-4 py-8 text-center text-gray-500">
                    <BellIcon class="h-12 w-12 mx-auto mb-2 text-gray-300" />
                    <p>Tidak ada notifikasi</p>
                </div>
                
                <div v-else>
                    <div
                        v-for="notification in notifications"
                        :key="notification.id"
                        :class="[
                            'px-4 py-3 border-b border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors',
                            notification.read_at ? 'opacity-75' : 'bg-blue-50'
                        ]"
                        @click="handleNotificationClick(notification)"
                    >
                        <div class="flex items-start space-x-3">
                            <!-- Icon -->
                            <div class="flex-shrink-0">
                                <div
                                    :class="[
                                        'w-8 h-8 rounded-full flex items-center justify-center',
                                        getNotificationIconClass(notification.type)
                                    ]"
                                >
                                    <component :is="getNotificationIcon(notification.type)" class="h-4 w-4" />
                                </div>
                            </div>
                            
                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">
                                    {{ getNotificationTitle(notification) }}
                                </p>
                                <p class="text-sm text-gray-600 mt-1">
                                    {{ getNotificationMessage(notification) }}
                                </p>
                                <p class="text-xs text-gray-400 mt-1">
                                    {{ formatDate(notification.created_at) }}
                                </p>
                            </div>
                            
                            <!-- Unread Indicator -->
                            <div v-if="!notification.read_at" class="flex-shrink-0">
                                <div class="w-2 h-2 bg-blue-600 rounded-full"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-4 py-3 border-t border-gray-200">
                <Link
                    :href="route('notifications.index')"
                    class="block text-center text-sm text-blue-600 hover:text-blue-800"
                    @click="closeDropdown"
                >
                    Lihat Semua Notifikasi
                </Link>
            </div>
        </div>

        <!-- Backdrop -->
        <div
            v-if="isOpen"
            class="fixed inset-0 z-40"
            @click="closeDropdown"
        ></div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { 
    BellIcon, 
    AlertCircleIcon, 
    CheckCircleIcon, 
    InfoIcon,
    XCircleIcon
} from 'lucide-vue-next'

const props = defineProps({
    notifications: {
        type: Array,
        default: () => []
    }
})

const isOpen = ref(false)

// Computed properties
const unreadCount = computed(() => {
    return props.notifications.filter(n => !n.read_at).length
})

// Methods
const toggleDropdown = () => {
    isOpen.value = !isOpen.value
}

const closeDropdown = () => {
    isOpen.value = false
}

const markAllAsRead = async () => {
    try {
        await router.post(route('notifications.mark-all-read'))
        // Refresh page to update notifications
        router.reload({ only: ['notifications'] })
    } catch (error) {
        console.error('Error marking notifications as read:', error)
    }
}

const handleNotificationClick = async (notification) => {
    // Mark as read if unread
    if (!notification.read_at) {
        try {
            await router.patch(route('notifications.mark-read', notification.id))
        } catch (error) {
            console.error('Error marking notification as read:', error)
        }
    }
    
    // Navigate to action URL if exists
    if (notification.data?.action_url) {
        router.visit(notification.data.action_url)
    }
    
    closeDropdown()
}

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
    const now = new Date()
    const diffInMinutes = Math.floor((now - date) / (1000 * 60))
    
    if (diffInMinutes < 1) {
        return 'Baru saja'
    } else if (diffInMinutes < 60) {
        return `${diffInMinutes} menit yang lalu`
    } else if (diffInMinutes < 1440) {
        const hours = Math.floor(diffInMinutes / 60)
        return `${hours} jam yang lalu`
    } else {
        const days = Math.floor(diffInMinutes / 1440)
        return `${days} hari yang lalu`
    }
}
</script>

<style scoped>
.notification-dropdown {
    /* Add any custom styles here */
}
</style>