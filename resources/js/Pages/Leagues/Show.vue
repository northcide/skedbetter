<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    league: Object,
    currentSeason: Object,
    userRole: String,
});

const isManager = ['superadmin', 'league_manager'].includes(props.userRole);

const navItems = [
    { label: 'Schedule Calendar', route: 'leagues.schedule.calendar' },
    { label: 'Schedule List', route: 'leagues.schedule.index' },
    { label: 'Seasons', route: 'leagues.seasons.index' },
    { label: 'Divisions', route: 'leagues.divisions.index' },
    { label: 'Teams', route: 'leagues.teams.index' },
    { label: 'Locations & Fields', route: 'leagues.locations.index' },
    { label: 'Blackout Rules', route: 'leagues.blackouts.index' },
];
</script>

<template>
    <Head :title="league.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        {{ league.name }}
                    </h2>
                    <p v-if="league.description" class="mt-1 text-sm text-gray-500">
                        {{ league.description }}
                    </p>
                </div>
                <Link v-if="isManager" :href="route('leagues.edit', league.slug)" class="text-sm text-indigo-600 hover:text-indigo-900">
                    Edit League
                </Link>
            </div>
        </template>

        <FlashMessage />

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Stats -->
                <div class="mb-8 grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <div class="overflow-hidden rounded-lg bg-white p-6 shadow-sm">
                        <dt class="truncate text-sm font-medium text-gray-500">Teams</dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ league.teams_count }}</dd>
                    </div>
                    <div class="overflow-hidden rounded-lg bg-white p-6 shadow-sm">
                        <dt class="truncate text-sm font-medium text-gray-500">Locations</dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ league.locations_count }}</dd>
                    </div>
                    <div class="overflow-hidden rounded-lg bg-white p-6 shadow-sm">
                        <dt class="truncate text-sm font-medium text-gray-500">Divisions</dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ league.divisions_count }}</dd>
                    </div>
                </div>

                <!-- Current Season -->
                <div v-if="currentSeason" class="mb-8 overflow-hidden rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-medium text-gray-900">Current Season</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ currentSeason.name }} &mdash;
                        {{ new Date(currentSeason.start_date).toLocaleDateString() }} to
                        {{ new Date(currentSeason.end_date).toLocaleDateString() }}
                    </p>
                </div>

                <!-- Navigation Cards -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <Link
                        v-for="item in navItems"
                        :key="item.route"
                        :href="route(item.route, league.slug)"
                        class="flex items-center gap-4 rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md"
                    >
                        <div class="text-lg font-medium text-gray-900">{{ item.label }}</div>
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
