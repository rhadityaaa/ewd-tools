import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    email: string;
    division_id: number;
    division: Division;
    role_id: number;
    role: Role;
    created_at: string;
    updated_at: string;
}

export interface Role {
    id: number;
    name: string;
}

export interface Division {
    id: number;
    code: string;
    name: string;
    created_at: string;
    updated_at: string;
}

export interface Period {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    status: 'draft' | 'active' | 'ended' | 'expired';
    created_by: number;
    created_by_user?: User;
    created_at: string;
    updated_at: string;
    is_active: boolean;
    is_expired: boolean;
    remaining_time: string;
}

export interface Borrower {
    id: number;
    name: string;
    division_id: number;
    division: Division;
    created_at: string;
    updated_at: string;
}

export interface Template {
    id: number;
    name: string;
}

export type BreadcrumbItemType = BreadcrumbItem;
