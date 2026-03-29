<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-50">
            <nav class="border-b border-gray-200 bg-white">
                <div class="mx-auto max-w-screen-2xl px-3 sm:px-4 lg:px-6">
                    <div class="flex h-10 justify-between">
                        <div class="flex">
                            <div class="flex shrink-0 items-center gap-1.5">
                                <Link :href="route('dashboard')" class="flex items-center gap-1.5">
                                    <ApplicationLogo class="block h-6 w-6 text-brand-600" />
                                    <span class="text-sm font-bold tracking-tight text-brand-950">SkedBetter</span>
                                </Link>
                            </div>
                            <div class="hidden space-x-4 sm:-my-px sm:ms-6 sm:flex">
                                <NavLink :href="route('leagues.index')" :active="route().current('leagues.*')">Leagues</NavLink>
                                <NavLink :href="route('notifications.index')" :active="route().current('notifications.*')">Notifications</NavLink>
                                <NavLink v-if="$page.props.auth.user.is_superadmin" :href="route('admin.approvals')" :active="route().current('admin.*')">
                                    Admin
                                    <span v-if="$page.props.pendingApprovalCount" class="ml-1 rounded-full bg-red-500 px-1.5 py-0.5 text-[9px] font-bold text-white">{{ $page.props.pendingApprovalCount }}</span>
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:ms-4 sm:flex sm:items-center">
                            <div class="relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button type="button" class="inline-flex items-center gap-1 rounded px-1.5 py-1 text-xs font-medium text-gray-600 transition hover:text-gray-900 focus:outline-none">
                                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-brand-100 text-[10px] font-bold text-brand-700">
                                                {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                                            </span>
                                            <span class="max-w-[100px] truncate">{{ $page.props.auth.user.name }}</span>
                                            <svg class="h-3.5 w-3.5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button">Sign Out</DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <div class="-me-2 flex items-center sm:hidden">
                            <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="inline-flex items-center justify-center rounded-md p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-500">
                                <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    <path :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden">
                    <div class="space-y-1 pb-2 pt-1">
                        <ResponsiveNavLink :href="route('leagues.index')" :active="route().current('leagues.*')">Leagues</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('notifications.index')" :active="route().current('notifications.*')">Notifications</ResponsiveNavLink>
                        <ResponsiveNavLink v-if="$page.props.auth.user.is_superadmin" :href="route('admin.approvals')" :active="route().current('admin.*')">
                            Admin
                            <span v-if="$page.props.pendingApprovalCount" class="ml-1 rounded-full bg-red-500 px-1.5 py-0.5 text-[9px] font-bold text-white">{{ $page.props.pendingApprovalCount }}</span>
                        </ResponsiveNavLink>
                    </div>
                    <div class="border-t border-gray-200 py-2">
                        <div class="px-4 py-1">
                            <div class="text-sm font-medium text-gray-800">{{ $page.props.auth.user.name }}</div>
                            <div class="text-xs text-gray-500">{{ $page.props.auth.user.email }}</div>
                        </div>
                        <div class="mt-1 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">Profile</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button">Sign Out</ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <header class="border-b border-gray-100 bg-white" v-if="$slots.header">
                <div class="mx-auto max-w-screen-2xl px-3 py-1.5 sm:px-4 lg:px-5">
                    <slot name="header" />
                </div>
            </header>

            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
