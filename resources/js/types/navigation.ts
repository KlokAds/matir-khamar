import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from '@lucide/vue';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
    /** Full page load instead of an Inertia visit (lets the service worker answer offline). */
    reload?: boolean;
    badge?: number;
    /** Sub-entries shown under this one in the sidebar. */
    children?: NavItem[];
};
