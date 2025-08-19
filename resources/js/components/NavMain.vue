<script setup lang="ts">
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    items: NavItem[];
}>();

const page = usePage();

// Group items by category for better organization
const groupedItems = computed(() => {
    const groups = {
        admin: [] as NavItem[],
        business: [] as NavItem[],
        approval: [] as NavItem[],
        other: [] as NavItem[]
    };
    
    props.items.forEach(item => {
        if (['User', 'Divisi', 'Periode', 'Template', 'Aspek'].includes(item.title)) {
            groups.admin.push(item);
        } else if (['Debitur', 'Laporan'].includes(item.title)) {
            groups.business.push(item);
        } else if (['Persetujuan', 'Laporan Persetujuan'].includes(item.title)) {
            groups.approval.push(item);
        } else {
            groups.other.push(item);
        }
    });
    
    return groups;
});
</script>

<template>
    <div class="space-y-2">
        <!-- Main/Dashboard Items -->
        <SidebarGroup v-if="groupedItems.other.length > 0" class="px-2 py-0">
            <SidebarGroupLabel>Platform</SidebarGroupLabel>
            <SidebarMenu>
                <SidebarMenuItem v-for="item in groupedItems.other" :key="item.title">
                    <SidebarMenuButton as-child :is-active="item.href === page.url" :tooltip="item.title">
                        <Link :href="item.href">
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroup>

        <!-- Business Items -->
        <SidebarGroup v-if="groupedItems.business.length > 0" class="px-2 py-0">
            <SidebarGroupLabel>Bisnis</SidebarGroupLabel>
            <SidebarMenu>
                <SidebarMenuItem v-for="item in groupedItems.business" :key="item.title">
                    <SidebarMenuButton as-child :is-active="item.href === page.url" :tooltip="item.title">
                        <Link :href="item.href">
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroup>

        <!-- Approval Items -->
        <SidebarGroup v-if="groupedItems.approval.length > 0" class="px-2 py-0">
            <SidebarGroupLabel>Persetujuan</SidebarGroupLabel>
            <SidebarMenu>
                <SidebarMenuItem v-for="item in groupedItems.approval" :key="item.title">
                    <SidebarMenuButton as-child :is-active="item.href === page.url" :tooltip="item.title">
                        <Link :href="item.href">
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroup>

        <!-- Admin Items -->
        <SidebarGroup v-if="groupedItems.admin.length > 0" class="px-2 py-0">
            <SidebarGroupLabel>Administrasi</SidebarGroupLabel>
            <SidebarMenu>
                <SidebarMenuItem v-for="item in groupedItems.admin" :key="item.title">
                    <SidebarMenuButton as-child :is-active="item.href === page.url" :tooltip="item.title">
                        <Link :href="item.href">
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroup>
    </div>
</template>
