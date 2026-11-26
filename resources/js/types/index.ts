import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    /** 未ログイン時は null */
    user: User | null;
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

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: {
        location: string;
        url: string;
        port: null | number;
        defaults: Record<string, unknown>;
        routes: Record<string, string>;
    };
    // Inertia の usePage<T> は T が PageProps（インデックスシグネチャ必須）であることを要求する
    [key: string]: unknown;
}

/** 共有 props の auth.user（AuthUserResource が返す形） */
export interface User {
    id: number;
    name: string;
    handle: string;
    email: string;
    user_image_url: string | null;
    email_verified_at: string | null;
}

export type BreadcrumbItemType = BreadcrumbItem;
