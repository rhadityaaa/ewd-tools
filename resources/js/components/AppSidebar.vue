<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Button } from '@/components/ui/button';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Building, ClipboardList, Clock, FileText, Folder, LayoutGrid, PlusCircle, UserIcon, CheckSquare, BarChart3, PaperclipIcon } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);


const hasRole = (roles: string[]) => {
    if (!user.value?.role) {
        return false;
    };
    return roles.includes(user.value.role.name);
};

const baseNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
        icon: LayoutGrid,
    },
];

const adminNavItems: NavItem[] = [
    {
        title: 'User',
        href: route('users.index'),
        icon: UserIcon,
    },
    {
        title: 'Divisi',
        href: route('divisions.index'),
        icon: Building,
    },
    {
        title: 'Debitur',
        href: route('borrowers.index'),
        icon: Folder,
    }, 
    {
        title: 'Template',
        href: route('templates.index'),
        icon: FileText,
    },
    {
        title: 'Aspek',
        href: route('aspects.index'),
        icon: ClipboardList,
    },
    {
        title: 'Periode',
        href: route('periods.index'),
        icon: Clock,
    },
];

const businessNavItems: NavItem[] = [
    
    {
        title: 'Laporan',
        href: route('reports.index'),
        icon: PaperclipIcon,
    },
];

const approvalNavItems: NavItem[] = [
    {
        title: 'Persetujuan',
        href: route('approvals.index'),
        icon: CheckSquare,
    },
];

const approvalReportNavItems: NavItem[] = [
    {
        title: 'Laporan Persetujuan',
        href: route('approval-reports.index'),
        icon: BarChart3,
    },
];

const mainNavItems = computed(() => {
    let items = [...baseNavItems];
    
    if (hasRole(['super_admin'])) {
        items.push(...adminNavItems);
    }
    
    if (hasRole(['unit_bisnis', 'risk_analyst', 'kadept_bisnis', 'kadept_risk', 'super_admin'])) {
        items.push(...businessNavItems);
    }
    
    if (hasRole(['risk_analyst', 'kadept_bisnis', 'kadept_risk', 'super_admin'])) {
        items.push(...approvalNavItems);
    }
    
    if (hasRole(['kadept_bisnis', 'kadept_risk', 'super_admin'])) {
        items.push(...approvalReportNavItems);
    }
    
    return items;
});

const showAddReportButton = computed(() => {
    return hasRole(['unit_bisnis', 'super_admin']);
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <div v-if="showAddReportButton" class="px-2 py-1">
                <Link :href="route('forms.index')" class="w-full">
                    <Button class="w-full" size="sm">
                        <PlusCircle class="h-5 w-5" />
                        <span>Tambah Report Baru</span>
                    </Button>
                </Link>
            </div>

            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
