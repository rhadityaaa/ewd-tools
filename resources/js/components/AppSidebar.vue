<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Button } from '@/components/ui/button';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Building, ClipboardList, Clock, FileText, Folder, LayoutGrid, PlusCircle, UserIcon, CheckSquare, BarChart3 } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

// Helper function to check if user has any of the specified roles
const hasAnyRole = (roles: string[]) => {
    if (!user.value?.roles) return false;
    return user.value.roles.some((role: any) => roles.includes(role.name));
};

// Helper function to check if user has specific role
const hasRole = (roleName: string) => {
    if (!user.value?.roles) return false;
    return user.value.roles.some((role: any) => role.name === roleName);
};

// Base navigation items (available to all authenticated users)
const baseNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
        icon: LayoutGrid,
    },
];

// Admin-only navigation items
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
        title: 'Periode',
        href: route('periods.index'),
        icon: Clock,
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
];

// Business user navigation items
const businessNavItems: NavItem[] = [
    {
        title: 'Debitur',
        href: route('borrowers.index'),
        icon: Folder,
    },
    {
        title: 'Laporan',
        href: route('reports.index'),
        icon: ClipboardList,
    },
];

// Approval navigation items (for approval roles)
const approvalNavItems: NavItem[] = [
    {
        title: 'Persetujuan',
        href: route('approvals.index'),
        icon: CheckSquare,
    },
];

// Approval report navigation items (for management roles)
const approvalReportNavItems: NavItem[] = [
    {
        title: 'Laporan Persetujuan',
        href: route('approval-reports.index'),
        icon: BarChart3,
    },
];

// Computed main navigation items based on user roles
const mainNavItems = computed(() => {
    let items = [...baseNavItems];
    
    // Add admin items for super_admin
    if (hasRole('super_admin')) {
        items.push(...adminNavItems);
    }
    
    // Add business items for unit_bisnis and above
    if (hasAnyRole(['unit_bisnis', 'risk_analyst', 'kadept_bisnis', 'kadept_risk', 'super_admin'])) {
        items.push(...businessNavItems);
    }
    
    // Add approval items for approval roles
    if (hasAnyRole(['risk_analyst', 'kadept_bisnis', 'kadept_risk', 'super_admin'])) {
        items.push(...approvalNavItems);
    }
    
    // Add approval report items for management roles
    if (hasAnyRole(['kadept_bisnis', 'kadept_risk', 'super_admin'])) {
        items.push(...approvalReportNavItems);
    }
    
    return items;
});

// Show "Tambah Report Baru" button only for unit_bisnis and above
const showAddReportButton = computed(() => {
    return hasAnyRole(['unit_bisnis', 'risk_analyst', 'kadept_bisnis', 'kadept_risk', 'super_admin']);
});

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
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
            <!-- Add Report Button - Only for business users -->
            <div v-if="showAddReportButton" class="px-2 py-1">
                <Link :href="route('forms.index')" class="w-full">
                    <Button class="w-full" size="sm">
                        <PlusCircle class="h-5 w-5" />
                        <span>Tambah Report Baru</span>
                    </Button>
                </Link>
            </div>

            <!-- Role-based Navigation -->
            <NavMain :items="mainNavItems" />
            
            <!-- Debug Info (Remove in production) -->
            <div v-if="user" class="px-2 py-1 text-xs text-gray-500">
                <p>Role: {{ user.roles?.map(r => r.name).join(', ') || 'No roles' }}</p>
            </div>
        </SidebarContent>

        <SidebarFooter>
            <!-- <NavFooter :items="footerNavItems" /> -->
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
