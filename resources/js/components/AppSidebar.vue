<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Button } from '@/components/ui/button';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Building, ClipboardList, Clock, FileText, Folder, LayoutGrid, PlusCircle, UserIcon } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
        icon: LayoutGrid,
    },
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
            <div class="px-2 py-1">
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
            <!-- <NavFooter :items="footerNavItems" /> -->
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
