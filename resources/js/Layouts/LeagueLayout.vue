<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    league: Object,
    userRole: { type: String, default: '' },
});

const page = usePage();
const currentRoute = computed(() => page.url);
const mobileMenuOpen = ref(false);
const isManager = computed(() => ['superadmin', 'league_admin', 'division_manager'].includes(props.userRole));

const navSections = computed(() => {
    const sections = [
        {
            label: 'Scheduling',
            items: [
                { label: 'Calendar', route: 'leagues.schedule.calendar', match: 'schedule/calendar' },
                { label: 'Schedule List', route: 'leagues.schedule.index', match: '/schedule', exact: false },
                { label: 'Blackout Rules', route: 'leagues.blackouts.index', match: 'blackouts' },
            ],
        },
        {
            label: 'League Setup',
            items: [
                { label: 'Seasons', route: 'leagues.seasons.index', match: 'seasons' },
                { label: 'Divisions & Teams', route: 'leagues.divisions.index', match: 'divisions' },
                { label: 'Locations & Fields', route: 'leagues.locations.index', match: 'locations' },
            ],
        },
    ];

    if (isManager.value) {
        sections.push({
            label: 'Admin',
            items: [
                { label: 'Members', route: 'leagues.members.index', match: 'members' },
                { label: 'Settings', route: 'leagues.edit', match: '/edit' },
            ],
        });
    }

    return sections;
});

function isActive(item) {
    const url = currentRoute.value;
    if (item.match === 'schedule/calendar') return url.includes('schedule/calendar');
    if (item.match === '/schedule') return url.includes('/schedule') && !url.includes('schedule/calendar');
    return url.includes(item.match);
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm">
                    <Link :href="route('leagues.index')" class="text-gray-400 hover:text-gray-600">Leagues</Link>
                    <span class="text-gray-300">/</span>
                    <Link :href="route('leagues.show', league.slug)" class="font-medium text-gray-900 hover:text-brand-600">{{ league.name }}</Link>
                </div>
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="rounded p-1.5 text-gray-400 lg:hidden">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
            </div>
        </template>

        <div class="mx-auto max-w-screen-2xl px-3 py-3 sm:px-4 lg:px-6">
            <div class="lg:grid lg:grid-cols-[180px_1fr] lg:gap-5">
                <!-- Sidebar -->
                <aside :class="mobileMenuOpen ? 'block' : 'hidden lg:block'" class="mb-4 lg:mb-0">
                    <nav class="space-y-4">
                        <div v-for="section in navSections" :key="section.label">
                            <h3 class="px-2 text-[10px] font-bold uppercase tracking-wider text-gray-400">{{ section.label }}</h3>
                            <ul class="mt-1 space-y-px">
                                <li v-for="item in section.items" :key="item.route">
                                    <Link
                                        :href="route(item.route, league.slug)"
                                        class="flex items-center rounded-md px-2 py-1.5 text-[13px] font-medium transition"
                                        :class="isActive(item) ? 'bg-brand-50 text-brand-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
                                        @click="mobileMenuOpen = false"
                                    >{{ item.label }}</Link>
                                </li>
                            </ul>
                        </div>
                    </nav>
                </aside>

                <div class="min-w-0">
                    <slot />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
