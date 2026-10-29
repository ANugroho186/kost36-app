import { Link, usePage } from '@inertiajs/react';
import {
    BedDouble,
    Building2,
    KeyRound,
    LayoutGrid,
    Megaphone,
    Wrench,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as announcements } from '@/routes/announcements';
import { index as complaints } from '@/routes/complaints';
import { index as ownerAnnouncements } from '@/routes/owner/announcements';
import { index as ownerComplaints } from '@/routes/owner/complaints';
import { edit as editProperty } from '@/routes/owner/property';
import { index as rooms } from '@/routes/owner/rooms';
import { index as tenancies } from '@/routes/owner/tenancies';
import type { NavItem } from '@/types';

const dashboardItem: NavItem = {
    title: 'Dashboard',
    href: dashboard(),
    icon: LayoutGrid,
};

// Menu ini hanya soal tampilan. Hak akses tetap dicek di server lewat Policy.
const ownerNavItems: NavItem[] = [
    {
        title: 'Property',
        href: editProperty(),
        icon: Building2,
    },
    {
        title: 'Rooms',
        href: rooms(),
        icon: BedDouble,
    },
    {
        title: 'Tenancies',
        href: tenancies(),
        icon: KeyRound,
    },
    {
        title: 'Complaints',
        href: ownerComplaints(),
        icon: Wrench,
    },
    {
        title: 'Announcements',
        href: ownerAnnouncements(),
        icon: Megaphone,
    },
];

const tenantNavItems: NavItem[] = [
    {
        title: 'Announcements',
        href: announcements(),
        icon: Megaphone,
    },
    {
        title: 'Complaints',
        href: complaints(),
        icon: Wrench,
    },
];

export function AppSidebar() {
    const { auth } = usePage().props;
    const mainNavItems =
        auth.user.role === 'owner'
            ? [dashboardItem, ...ownerNavItems]
            : [dashboardItem, ...tenantNavItems];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
