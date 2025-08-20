<script setup lang="ts">
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    items: NavItem[];
}>();

const page = usePage();

const groupedItems = computed(() => {
    const groups: Record<string, NavItem[]> = {};
    props.items.forEach(item => {
        const group = item.group || 'Lainnya';
        if (!groups[group]) {
            groups[group] = [];
        }
        groups[group].push(item);
    })
    return groups;
});
</script>

<template>
    <div v-for="(items, group) in groupedItems" :key="group" class="px-2 py-0">
        <SidebarGroup>
            <SidebarMenu>
                <SidebarMenuItem v-for="item in items" :key="item.title">
                    <SidebarMenuButton as-child :is-active="item.href === page.url" :tooltip="item.title">
                        <Link :href="item.href" class="flex items-center gap-2">
                            <component :is="item.icon" class="h-4 w-4" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroup>
    </div>
</template>
