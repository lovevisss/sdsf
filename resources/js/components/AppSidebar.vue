<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
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
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import {
    ClipboardList,
    FileDown,
    Gauge,
    LayoutGrid,
    ShieldAlert,
    ShieldCheck,
    UserRoundSearch,
} from 'lucide-vue-next';

const mainNavItems: NavItem[] = [
    {
        title: '工作台',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: '师德治理中心',
        href: '/ethics/dashboard',
        icon: Gauge,
    },
    {
        title: '师德档案',
        href: '/ethics/profiles',
        icon: UserRoundSearch,
    },
    {
        title: '工作纪律录入',
        href: '/ethics/discipline-violations',
        icon: ShieldAlert,
    },
    {
        title: '投诉处置',
        href: '/ethics/cases',
        icon: ClipboardList,
    },
    {
        title: '报表导出',
        href: '/ethics/reports/export?type=profile_details',
        icon: FileDown,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader class="px-3 pt-3 pb-2">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        size="lg"
                        as-child
                        class="h-12 rounded-lg px-2 transition hover:bg-slate-100 data-[active=true]:bg-slate-100"
                    >
                        <Link
                            :href="dashboard()"
                            aria-label="师德监督预警工作台"
                        >
                            <span
                                class="flex size-8 shrink-0 items-center justify-center rounded-md bg-slate-950 text-white shadow-sm"
                            >
                                <ShieldCheck class="size-4" />
                            </span>
                            <span
                                class="min-w-0 leading-tight group-data-[collapsible=icon]:hidden"
                            >
                                <span
                                    class="block truncate text-sm font-semibold text-slate-950"
                                >
                                    师德监督
                                </span>
                                <span
                                    class="block truncate text-xs font-medium text-slate-500"
                                >
                                    预警工作台
                                </span>
                            </span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
