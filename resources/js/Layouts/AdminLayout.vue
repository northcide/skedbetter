<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const currentRoute = computed(() => page.url);
const mobileMenuOpen = ref(false);

const navItems = [
    { label: 'Leagues', route: 'admin.leagues', match: 'admin/leagues' },
    { label: 'Users', route: 'admin.users', match: 'admin/users' },
    { label: 'Audit Log', route: 'admin.audit-log', match: 'admin/audit-log' },
    { label: 'Settings', route: 'admin.settings', match: 'admin/settings' },
];

function isActive(item) {
    if (item.match === '/leagues') return currentRoute.value === '/leagues';
    return currentRoute.value.includes(item.match);
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-900">Platform Admin</h2>
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="rounded p-2.5 text-gray-400 lg:hidden">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
            </div>
        </template>

        <div class="mx-auto max-w-screen-2xl px-3 py-2 sm:px-4 lg:px-5">
            <div class="lg:grid lg:grid-cols-[160px_1fr] lg:gap-4">
                <!-- Mobile backdrop -->
                <div v-if="mobileMenuOpen" class="fixed inset-0 z-30 bg-black/30 lg:hidden" @click="mobileMenuOpen = false"></div>

                <aside :class="mobileMenuOpen ? 'block' : 'hidden lg:block'" class="mb-3 lg:mb-0 relative z-40">
                    <nav>
                        <ul class="space-y-px">
                            <li v-for="item in navItems" :key="item.route">
                                <Link
                                    :href="route(item.route)"
                                    class="flex items-center rounded px-2 py-2.5 lg:py-1 text-sm lg:text-[12px] font-medium transition"
                                    :class="isActive(item) ? 'bg-brand-50 text-brand-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
                                    @click="mobileMenuOpen = false"
                                >
                                    {{ item.label }}
                                    <span v-if="item.route === 'admin.leagues' && $page.props.pendingLeagueCount"
                                        class="ml-auto rounded-full bg-red-500 px-1.5 py-0.5 text-[9px] font-bold text-white">
                                        {{ $page.props.pendingLeagueCount }}
                                    </span>
                                </Link>
                            </li>
                        </ul>
                    </nav>
                </aside>

                <div class="min-w-0">
                    <slot />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
