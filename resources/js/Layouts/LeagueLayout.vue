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

const isManager = computed(() => ['superadmin', 'league_manager'].includes(props.userRole));

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
                { label: 'Divisions', route: 'leagues.divisions.index', match: 'divisions' },
                { label: 'Teams', route: 'leagues.teams.index', match: 'teams' },
                { label: 'Locations & Fields', route: 'leagues.locations.index', match: 'locations' },
            ],
        },
    ];

    if (isManager.value) {
        sections.push({
            label: 'Administration',
            items: [
                { label: 'Members', route: 'leagues.members.index', match: 'members' },
                { label: 'League Settings', route: 'leagues.edit', match: '/edit' },
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
                <div class="flex items-center gap-3">
                    <Link :href="route('leagues.index')" class="text-sm text-gray-400 hover:text-gray-600">Leagues</Link>
                    <span class="text-gray-300">/</span>
                    <Link :href="route('leagues.show', league.slug)" class="text-sm font-medium text-gray-900 hover:text-brand-600">
                        {{ league.name }}
                    </Link>
                </div>

                <!-- Mobile sidebar toggle -->
                <button
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    class="rounded-lg border border-gray-200 p-2 text-gray-500 lg:hidden"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <div class="lg:grid lg:grid-cols-[220px_1fr] lg:gap-8">
                <!-- Sidebar -->
                <aside
                    :class="mobileMenuOpen ? 'block' : 'hidden lg:block'"
                    class="mb-6 lg:mb-0"
                >
                    <nav class="space-y-6">
                        <div v-for="section in navSections" :key="section.label">
                            <h3 class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">
                                {{ section.label }}
                            </h3>
                            <ul class="mt-2 space-y-0.5">
                                <li v-for="item in section.items" :key="item.route">
                                    <Link
                                        :href="route(item.route, league.slug)"
                                        class="flex items-center rounded-lg px-3 py-2 text-sm font-medium transition"
                                        :class="isActive(item)
                                            ? 'bg-brand-50 text-brand-700'
                                            : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
                                        @click="mobileMenuOpen = false"
                                    >
                                        {{ item.label }}
                                    </Link>
                                </li>
                            </ul>
                        </div>
                    </nav>
                </aside>

                <!-- Main content -->
                <div class="min-w-0">
                    <slot />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
