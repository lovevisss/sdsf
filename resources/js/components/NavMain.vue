<script setup lang="ts">
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { urlIsActive } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';

defineProps<{
    items: NavItem[];
}>();

const page = usePage();
</script>

<template>
    <SidebarGroup class="px-3 py-1">
        <SidebarGroupLabel
            class="px-2 text-[11px] font-semibold tracking-normal text-slate-500"
        >
            业务导航
        </SidebarGroupLabel>
        <SidebarMenu class="gap-1">
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="urlIsActive(item.href, page.url)"
                    :tooltip="item.title"
                    class="relative h-9 rounded-lg px-2.5 text-[13px] font-medium text-slate-600 transition-colors group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:px-2 before:absolute before:top-2 before:bottom-2 before:left-0 before:w-0.5 before:rounded-full before:bg-transparent hover:bg-slate-100 hover:text-slate-950 data-[active=true]:bg-slate-950 data-[active=true]:font-semibold data-[active=true]:text-white data-[active=true]:shadow-sm data-[active=true]:before:bg-white/80 group-data-[collapsible=icon]:data-[active=true]:before:bg-transparent [&>svg]:size-4 [&>svg]:text-slate-500 [&>svg]:transition-colors data-[active=true]:[&>svg]:text-white"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span class="truncate">{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
