import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface BlogPost {
    id: number;
    title: string;
    excerpt: string;
    content?: string;
    slug: string;
    featured_image: string | null;
    author: {
        id: number;
        name: string;
        avatar?: string;
    };
    published_at: string;
    reading_time: number;
    tags: string[];
    is_featured: boolean;
}

export interface Author {
    id: number;
    name: string;
}

export interface SearchFilters {
    search?: string | null;
    tag?: string | null;
    author?: string | null;
}

export type BreadcrumbItemType = BreadcrumbItem;
