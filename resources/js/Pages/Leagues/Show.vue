<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    league: Object,
    currentSeason: Object,
    userRole: String,
});

const isManager = ['superadmin', 'league_manager'].includes(props.userRole);
</script>

<template>
    <Head :title="league.name" />

    <LeagueLayout :league="league" :userRole="userRole">
        <FlashMessage />

        <div class="space-y-6">
            <!-- Quick Stats -->
            <div class="grid grid-cols-3 gap-4">
                <div class="rounded-xl border border-gray-100 bg-white px-4 py-5">
                    <p class="text-2xl font-bold text-gray-900">{{ league.teams_count }}</p>
                    <p class="text-xs font-medium text-gray-500">Teams</p>
                </div>
                <div class="rounded-xl border border-gray-100 bg-white px-4 py-5">
                    <p class="text-2xl font-bold text-gray-900">{{ league.locations_count }}</p>
                    <p class="text-xs font-medium text-gray-500">Locations</p>
                </div>
                <div class="rounded-xl border border-gray-100 bg-white px-4 py-5">
                    <p class="text-2xl font-bold text-gray-900">{{ league.divisions_count }}</p>
                    <p class="text-xs font-medium text-gray-500">Divisions</p>
                </div>
            </div>

            <!-- Current Season -->
            <div v-if="currentSeason" class="rounded-xl border border-gray-100 bg-white p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Current Season</p>
                        <p class="mt-1 text-lg font-semibold text-gray-900">{{ currentSeason.name }}</p>
                        <p class="text-sm text-gray-500">
                            {{ new Date(currentSeason.start_date).toLocaleDateString() }} &ndash;
                            {{ new Date(currentSeason.end_date).toLocaleDateString() }}
                        </p>
                    </div>
                    <Link :href="route('leagues.schedule.calendar', league.slug)">
                        <PrimaryButton>Open Calendar</PrimaryButton>
                    </Link>
                </div>
            </div>

            <div v-else class="rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center">
                <p class="text-sm text-gray-500">No season set up yet.</p>
                <Link v-if="isManager" :href="route('leagues.onboarding', league.slug)" class="mt-2 inline-block text-sm font-medium text-brand-600 hover:text-brand-700">
                    Run setup wizard
                </Link>
            </div>

            <!-- Quick Actions -->
            <div v-if="isManager" class="rounded-xl border border-gray-100 bg-white p-5">
                <p class="mb-3 text-xs font-medium uppercase tracking-wider text-gray-400">Quick Actions</p>
                <div class="flex flex-wrap gap-2">
                    <Link :href="route('leagues.schedule.create', league.slug)" class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-50">
                        New Schedule Entry
                    </Link>
                    <Link :href="route('leagues.schedule.bulk', league.slug)" class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-50">
                        Bulk Schedule
                    </Link>
                    <Link :href="route('leagues.teams.create', league.slug)" class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-50">
                        Add Team
                    </Link>
                    <Link :href="route('leagues.members.index', league.slug)" class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-50">
                        Invite Member
                    </Link>
                </div>
            </div>
        </div>
    </LeagueLayout>
</template>
